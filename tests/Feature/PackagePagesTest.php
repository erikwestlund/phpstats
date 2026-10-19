<?php

namespace Tests\Feature;

use App\Content\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PackagePagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_package_pages_link_the_family_and_related_packages(): void
    {
        $response = $this->get('http://phplyr.phpstats.test/')->assertOk();

        // The two menus link every package, and the mark links the landing page.
        foreach (Package::keys() as $key) {
            $response->assertSee("http://{$key}.phpstats.test", false);
        }
        $response->assertSee('Data &amp; Workflow', false)
            ->assertSee('Statistics, Modeling &amp; Visualization', false)
            ->assertSee('aria-label="PHPstats home"', false)
            ->assertSee('Connected packages')
            ->assertSee('phplyr functions take phibble data frames and return new ones.');

        // The other side of the relation lists it too.
        $this->get('http://phibble.phpstats.test/')->assertSee('Used by')->assertSee('phplyr functions take phibble data frames');
    }

    public function test_each_package_has_an_overview_on_its_own_host(): void
    {
        foreach (Package::keys() as $key) {
            $package = Package::find($key);
            $this->get("http://{$key}.phpstats.test/")->assertOk()->assertSee($package->tagline());
            foreach (['vignettes', 'benchmarks', 'verification'] as $section) {
                $this->get("http://{$key}.phpstats.test/{$section}")->assertOk();
            }
        }
    }

    public function test_every_package_is_in_exactly_one_menu(): void
    {
        $menus = [];
        foreach (config('phpstats.menus') as $menu) {
            foreach ($menu['layers'] as $layer) {
                foreach (array_keys(config("phpstats.layers.{$layer}.packages")) as $key) {
                    $menus[$key][] = $menu['title'];
                }
            }
        }

        $this->assertEqualsCanonicalizing(Package::keys(), array_keys($menus));
        foreach ($menus as $key => $titles) {
            $this->assertCount(1, $titles, $key);
        }
    }

    public function test_the_package_bar_hides_sections_with_no_content(): void
    {
        $sections = ['docs', 'vignettes', 'benchmarks', 'verification'];

        // juphyter has no docs, vignettes, benchmarks, or verification yet.
        $juphyter = Package::find('juphyter');
        $bar = $this->get($juphyter->url())->assertOk();
        foreach ($sections as $section) {
            $bar->assertDontSee($juphyter->url($section), false);
        }

        // phibble has docs and vignettes since task 10 pinned its content, but
        // it ships neither benchmarks nor verification.
        $phibble = Package::find('phibble');
        $this->get($phibble->url())->assertOk()
            ->assertSee($phibble->url('vignettes'), false)
            ->assertSee($phibble->url('docs'), false)
            ->assertDontSee($phibble->url('benchmarks'), false)
            ->assertDontSee($phibble->url('verification'), false);

        // phit has all four.
        $phit = Package::find('phit');
        $all = $this->get($phit->url())->assertOk();
        foreach ($sections as $section) {
            $all->assertSee($phit->url($section), false);
        }
    }

    public function test_unknown_hosts_and_paths_are_not_found(): void
    {
        $this->get('http://not-a-package.phpstats.test/')->assertNotFound();
        $this->get('http://phit.phpstats.test/vignettes/not-a-vignette')->assertNotFound();
        $this->get('http://phit.phpstats.test/docs/not-a-page')->assertNotFound();
        $this->get('http://phpstats.test/not-a-project')->assertNotFound();
    }

    public function test_companions_are_part_of_their_parent(): void
    {
        $this->assertArrayNotHasKey('phit-polyfill', Package::all());
        $this->get('http://phit-polyfill.phpstats.test/docs')->assertRedirect('http://phit.phpstats.test');
        $this->get('http://phpstats.test/php-stats-polyfill')->assertRedirect('http://phit.phpstats.test');
        $this->get('http://phit.phpstats.test/')->assertSee('defines the same functions in plain PHP');
        $this->get('http://phpstats.test/')->assertDontSee('phit-polyfill.phpstats.test');
    }

    public function test_old_paths_redirect_to_package_hosts(): void
    {
        $this->get('http://phpstats.test/php-stats')->assertRedirect('http://phit.phpstats.test');
        $this->get('http://phpstats.test/phibble')->assertRedirect('http://phibble.phpstats.test');
        $this->get('http://phpstats.test/vignettes/school-effects')->assertRedirect('http://phit.phpstats.test/vignettes/school-shrinkage');
        $this->get('http://phpstats.test/vignettes/sql-pipeline')->assertRedirect('http://phibble.phpstats.test/vignettes/sql-pipeline');
        $this->get('http://phpstats.test/benchmarks')->assertRedirect('http://phit.phpstats.test/benchmarks');
    }

    // Task 10: the install card each package publishes in its own site.json.

    public function test_each_synced_package_shows_its_install_lines(): void
    {
        $cards = [];
        foreach (Package::all() as $key => $package) {
            if ($package->install() === []) {
                continue;
            }
            $cards[] = $key;

            // The lines are highlighted, so the markup splits them into spans:
            // compare the text of the page rather than its HTML.
            $text = html_entity_decode(strip_tags($this->get($package->url())->assertOk()->getContent()));
            foreach ($package->install() as $line) {
                $this->assertStringContainsString($line, $text, "{$key}: {$line}");
            }
            foreach ($package->requires() as $requirement) {
                $this->assertStringContainsString($requirement, $text, "{$key}: {$requirement}");
            }
        }

        // juphyter is the only package with no content to install from.
        $this->assertEqualsCanonicalizing(['phibble', 'phplyr', 'phpolars', 'phit', 'phiz', 'cmdstanphp'], $cards);
    }

    // Overview content (task 11a): summary lists, pipe-only examples, the
    // tibble header, and the aside that used to render empty.

    public function test_a_site_summary_list_becomes_the_overview_paragraphs(): void
    {
        $this->fixtureSite('phibble', ['summary' => ['The first paragraph.', 'The second paragraph.']]);
        $package = Package::find('phibble');

        // A paragraph of "Array" is what a list reaching the view as one string
        // would print; the bare word also sits in phiz's loader on every page.
        $this->assertSame(['The first paragraph.', 'The second paragraph.'], $package->summary());
        $this->get($package->url())->assertOk()
            ->assertSeeInOrder(['The first paragraph.', 'The second paragraph.'])
            ->assertDontSee('<p>Array</p>', false);
    }

    public function test_a_site_summary_string_keeps_the_shaped_paragraphs(): void
    {
        $this->fixtureSite('phibble', ['summary' => 'One dense paragraph.']);
        $package = Package::find('phibble');

        $this->assertSame(config('projects.phibble.overview'), $package->summary());
        $this->get($package->url())->assertOk()
            ->assertSee('phibble is the shared table type')
            ->assertDontSee('One dense paragraph.')
            ->assertDontSee('<p>Array</p>', false);
    }

    public function test_a_package_with_no_site_content_keeps_the_shaped_paragraphs(): void
    {
        $this->fixtureSite('phibble', [], synced: false);
        $package = Package::find('phibble');

        $this->assertSame(config('projects.phibble.overview'), $package->summary());
        $this->get($package->url())->assertOk()->assertDontSee('<p>Array</p>', false);
    }

    public function test_an_example_with_only_a_pipe_renders_one_plain_code_block(): void
    {
        $this->fixtureSite('phibble', ['example' => ['pipe' => '$table |> pipeOnlyFixture();']]);
        $package = Package::find('phibble');

        $this->assertSame(['chain' => null, 'pipe' => '$table |> pipeOnlyFixture();', 'output' => null], $package->example());
        $this->get($package->url())->assertOk()
            ->assertSee('pipeOnlyFixture')
            ->assertDontSee('aria-label="Code syntax"', false);
    }

    public function test_an_example_with_only_a_chain_renders_one_plain_code_block(): void
    {
        $this->fixtureSite('phibble', ['example' => ['chain' => '$table->chainOnlyFixture();']]);
        $package = Package::find('phibble');

        $this->assertSame('$table->chainOnlyFixture();', $package->example()['chain']);
        $this->assertNull($package->example()['pipe']);
        $this->get($package->url())->assertOk()
            ->assertSee('chainOnlyFixture')
            ->assertDontSee('aria-label="Code syntax"', false);
    }

    public function test_an_example_with_both_syntaxes_keeps_the_tabs(): void
    {
        $this->fixtureSite('phibble', ['example' => ['pipe' => '$table |> head(3);', 'chain' => '$table->head(3);']]);

        $this->get(Package::find('phibble')->url())->assertOk()
            ->assertSee('aria-label="Code syntax"', false);
    }

    public function test_no_overview_shows_a_tibble_header(): void
    {
        foreach (Package::all() as $package) {
            $this->get($package->url())->assertOk()->assertDontSee('# A tibble');
        }
    }

    public function test_an_example_output_shows_its_size_instead_of_the_tibble_header(): void
    {
        $this->fixtureSite('phibble', ['example' => [
            'pipe' => '$table |> head(2);',
            'output' => "# A tibble: 2 × 3\n  id    city   rent\n  <int> <chr> <dbl>",
        ]]);

        $this->get(Package::find('phibble')->url())->assertOk()
            ->assertDontSee('# A tibble')
            ->assertSee('Output · 2 × 3')
            ->assertSee('&lt;int&gt;', false);
    }

    public function test_a_package_with_an_empty_aside_has_no_second_column(): void
    {
        // juphyter has no docs, vignettes, benchmarks, verification, relations,
        // or synced content, so there is nothing to put beside the overview.
        $package = Package::find('juphyter');
        $this->assertSame([], $package->vignettes());
        $this->assertSame([], $package->relations());
        $this->assertNull($package->sync());

        $this->get($package->url())->assertOk()
            ->assertDontSee('<aside', false)
            ->assertDontSee('lg:grid-cols-[minmax(0,1fr)_22rem]', false);
    }

    public function test_a_package_with_content_keeps_its_aside(): void
    {
        $this->get(Package::find('phit')->url())->assertOk()
            ->assertSee('<aside', false)
            ->assertSee('lg:grid-cols-[minmax(0,1fr)_22rem]', false);
    }

    /**
     * Point the content path at a copy whose site.json for one package is this
     * fixture, so a partial or list-shaped manifest can be rendered without
     * touching the real synced content.
     */
    private function fixtureSite(string $key, array $site, bool $synced = true): void
    {
        $root = base_path('storage/framework/testing/overview-'.uniqid());
        File::copyDirectory(config('phpstats.content_path'), $root);
        File::deleteDirectory("{$root}/{$key}");

        if ($synced) {
            File::ensureDirectoryExists("{$root}/{$key}");
            File::put("{$root}/{$key}/site.json", json_encode(array_merge([
                'package' => $key,
                'title' => $key,
                'tagline' => 'A tagline from the fixture.',
            ], $site)));
        }

        config(['phpstats.content_path' => $root]);
        Package::flush();
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));
    }
}
