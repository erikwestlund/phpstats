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
            foreach (['examples', 'benchmarks', 'verification'] as $section) {
                $this->get("http://{$key}.phpstats.test/{$section}")->assertOk();
            }
        }
    }

    public function test_every_package_is_in_exactly_one_menu(): void
    {
        $menus = [];
        foreach (config('phpstats.menus') as $menu) {
            foreach ($menu['layers'] as $layer) {
                foreach (config("phpstats.layers.{$layer}.packages") as $key) {
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
        $sections = ['docs', 'examples', 'benchmarks', 'verification'];

        // juphyter has docs, but no benchmarks or verification.
        $juphyter = Package::find('juphyter');
        $bar = $this->get($juphyter->url())->assertOk()->assertSee($juphyter->url('docs'), false);
        foreach (['benchmarks', 'verification'] as $section) {
            $bar->assertDontSee($juphyter->url($section), false);
        }

        // phibble has docs and examples, but it ships neither benchmarks nor
        // verification.
        $phibble = Package::find('phibble');
        $this->get($phibble->url())->assertOk()
            ->assertSee($phibble->url('examples'), false)
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
        // An example that left the live pages goes to the docs page that replaced it.
        $this->get('http://phpstats.test/vignettes/sql-pipeline')->assertRedirect('http://phibble.phpstats.test/docs/examples/database-table');
        $this->get('http://phibble.phpstats.test/vignettes/sql-pipeline')->assertStatus(301)->assertRedirect('http://phibble.phpstats.test/docs/examples/database-table');
        $this->get('http://phit.phpstats.test/vignettes')->assertStatus(301)->assertRedirect('http://phit.phpstats.test/examples');
        $this->get('http://phpstats.test/vignettes')->assertStatus(301)->assertRedirect('http://phpstats.test/examples');
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
            foreach ([...$package->requires(), ...array_column($package->optional(), 'name')] as $requirement) {
                $this->assertStringContainsString($requirement, $text, "{$key}: {$requirement}");
            }
        }

        $this->assertEqualsCanonicalizing(['phibble', 'phplyr', 'phpolars', 'phit', 'phiz', 'cmdstanphp', 'juphyter'], $cards);
    }

    public function test_juphyter_is_not_described_as_unfinished(): void
    {
        $package = Package::find('juphyter');
        $text = preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(
            $this->get($package->url())->assertOk()->getContent(),
        )));

        // The kernel installs and runs, so the page must not say it is
        // unfinished or that there is nothing to install.
        foreach (['in progress', 'nothing to install yet', 'as the work lands'] as $claim) {
            $this->assertStringNotContainsStringIgnoringCase($claim, $text, $claim);
        }

        // The label itself reaches no page: nothing renders status(), so the
        // config is pinned here instead.
        $this->assertSame('Working', $package->status());

        // How it is actually installed, from its own site.json: the global
        // install, the kernel registration, and the check.
        $this->assertStringContainsString('composer global require phpstats/juphyter', $text);
        $this->assertStringContainsString('juphyter install', $text);
        $this->assertStringContainsString('juphyter doctor', $text);
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
            ->assertSee('Load, transform, and summarize typed data frames in PHP.')
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

        $this->assertSame(['chain' => null, 'pipe' => '$table |> pipeOnlyFixture();', 'output' => null, 'chart' => null], $package->example());
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
        // With no synced content, juphyter has no docs, examples, benchmarks,
        // verification, or relations, so there is nothing to put beside the
        // overview.
        config(['phpstats.content_path' => storage_path('framework/testing/no-content')]);
        Package::flush();
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

    public function test_overview_counts_agree_with_their_numbers(): void
    {
        foreach (Package::all() as $key => $package) {
            $text = html_entity_decode(strip_tags($this->get($package->url())->assertOk()->getContent()));
            $this->assertDoesNotMatchRegularExpression('/\b1 (timing reports|pages|short, runnable examples|results agree)\b/', $text, $key);

            $examples = count($package->listedExamples());
            if ($examples > 0) {
                $this->assertStringContainsString($examples.' short, runnable '.($examples === 1 ? 'example' : 'examples'), $text, $key);
            }
            $benchmarks = count($package->benchmarks());
            if ($benchmarks > 0) {
                $this->assertStringContainsString($benchmarks.' timing '.($benchmarks === 1 ? 'report' : 'reports'), $text, $key);
            }
        }
    }

    public function test_the_overview_hero_keeps_only_what_installing_needs(): void
    {
        $package = Package::find('cmdstanphp');
        $html = $this->get($package->url())->assertOk()->getContent();
        $hero = substr($html, 0, strpos($html, '</section>'));

        $this->assertStringContainsString('Requires:', $hero);
        $this->assertStringContainsString('PHP 8.2–8.5', $hero);
        foreach ($package->optional() as $optional) {
            $this->assertStringNotContainsString(e($optional['name']), $hero);
        }
        // The optional items and build tools are still on the page, below it.
        $this->assertStringContainsString('Other dependencies', $html);
    }

    public function test_layers_show_their_name_without_a_tagline(): void
    {
        $this->get(Package::find('phit')->url())->assertOk()
            ->assertSee('Statistics, Modeling &amp; Visualization', false)
            ->assertDontSee('Models, inference, and charts');
        $this->get(Package::find('phibble')->url())->assertOk()
            ->assertDontSee('Tables, pipelines, and notebooks');
    }

    public function test_an_embedded_phiz_chart_is_drawn_instead_of_printed(): void
    {
        $spec = ['mark' => 'point', 'data' => ['values' => [['x' => 1, 'y' => 2]]]];
        $output = '<div data-phiz="'.e(json_encode($spec)).'"><div data-phiz-view wire:ignore></div></div>';
        $this->fixtureSite('phiz', ['example' => ['pipe' => 'echo $plot->embed();', 'output' => $output]]);

        $this->assertSame([...$spec, 'width' => 'container'], Package::find('phiz')->example()['chart']);
        $this->get(Package::find('phiz')->url())->assertOk()
            ->assertSee('data-phiz=', false)
            ->assertDontSee('&lt;div data-phiz', false);
    }

    public function test_benchmark_methods_come_from_each_record(): void
    {
        $page = $this->get(Package::find('cmdstanphp')->url('benchmarks'))->assertOk();
        // One machine is named once, and the record's own statistic and timing method show.
        $page->assertSee('Machine:')
            ->assertSee('Median of 7 runs')
            ->assertSee('What the time includes')
            ->assertDontSee('faster');

        $text = html_entity_decode(strip_tags($this->get(Package::find('phit')->url('benchmarks'))->getContent()));
        // phit's older records give no statistic, so none is invented for them.
        $this->assertStringContainsString('3 runs', $text);
        $this->assertStringNotContainsString('Median of 3 runs', $text);
    }

    public function test_a_note_every_verification_report_shares_is_shown_once(): void
    {
        $text = html_entity_decode(strip_tags($this->get(Package::find('phplyr')->url('verification'))->assertOk()->getContent()));
        $this->assertSame(1, substr_count($text, 'n_compared counts the assertions in each test.'));
        $this->assertStringContainsString('Max abs. error is the largest absolute difference', $text);
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
