<?php

namespace Tests\Feature;

use App\Content\Markdown;
use App\Content\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The landing host's own pages: the Markdown pages in resources/pages and the
 * index of every vignette.
 */
class RootPagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Package caches its config and synced content statically, and the
        // fixture tests below rewrite both.
        Package::flush();
    }

    protected function tearDown(): void
    {
        Package::flush();
        putenv('PHPSTATS_PATHS');
        unset($_ENV['PHPSTATS_PATHS'], $_SERVER['PHPSTATS_PATHS']);

        parent::tearDown();
    }

    public function test_get_started_renders_on_the_root_host(): void
    {
        $html = $this->get('http://phpstats.test/get-started')->assertOk()->content();

        // Collapsed, because CommonMark keeps the source's line breaks inside
        // a paragraph and the copy is wrapped.
        $text = $this->collapse(html_entity_decode(strip_tags($html)));

        $this->assertStringContainsString('Get started', $text);
        $this->assertStringContainsString('Use PHP 8.5.', $text);
        // The one home for the Composer notes the brief asks for.
        $this->assertStringContainsString('published on Packagist yet', $text);
        $this->assertStringContainsString('run composer init first', $text);
        $this->assertStringContainsString('anonymous requests reach their rate limit', $text);
        // The extensions are built, not installed from a package.
        $this->assertStringContainsString('the php.ini that php --ini reports', $text);
        // The example runs here, because phplyr needs no extension.
        $this->assertStringContainsString('summarize(', $text);
        $this->assertStringContainsString('revenue', $text);
    }

    public function test_the_vignette_index_renders_on_the_root_host(): void
    {
        $this->get('http://phpstats.test/vignettes')
            ->assertOk()
            ->assertSee('Vignettes')
            ->assertSee('Every vignette on the site');
    }

    public function test_the_vignette_index_lists_every_vignette_once_with_a_link_to_its_owner(): void
    {
        $response = $this->get('http://phpstats.test/vignettes')->assertOk();
        $html = $response->content();

        // The expected set, computed the way the page computes it: the Livewire
        // vignettes in config plus every synced script vignette.
        $expected = [];
        foreach (Package::all() as $key => $package) {
            foreach ($package->vignettes() as $vignette) {
                if ($vignette['owner'] === $key) {
                    $expected[] = [$key, $vignette['slug'], $vignette['title']];
                }
            }
        }
        $livewire = count(config('phpstats.vignettes'));
        $scripts = count($expected) - $livewire;

        $this->assertSame(10, $livewire, 'the Livewire vignettes in config changed');
        $this->assertGreaterThan(0, $scripts, 'no synced script vignette was found');
        $owners = count(array_unique(array_column($expected, 0)));
        $this->assertStringContainsString(count($expected).' vignettes across '.$owners.' packages.', $html);

        foreach ($expected as [$key, $slug, $title]) {
            $url = Package::find($key)->url('vignette', ['slug' => $slug]);
            $this->assertSame(1, substr_count($html, 'href="'.e($url).'"'), "{$key}/{$slug} is not linked exactly once");
            $response->assertSee($title);
        }
    }

    public function test_the_vignette_index_does_not_swallow_a_vignette_redirect(): void
    {
        $this->get('http://phpstats.test/vignettes/sql-pipeline')
            ->assertRedirect('http://phibble.phpstats.test/vignettes/sql-pipeline');
        $this->get('http://phpstats.test/vignettes/school-effects')
            ->assertRedirect('http://phit.phpstats.test/vignettes/school-shrinkage');
        $this->get('http://phpstats.test/vignettes/not-a-vignette')->assertNotFound();
    }

    public function test_the_catch_all_still_serves_markdown_pages_and_not_the_vignette_index(): void
    {
        $this->get('http://phpstats.test/about')->assertOk()->assertSee('About PHPstats');
        $this->get('http://phpstats.test/get-started')->assertOk()->assertSee('Get started');

        // /vignettes is a route of its own, not a Markdown page.
        $this->assertFileDoesNotExist(resource_path('pages/vignettes.md'));
        $this->get('http://phpstats.test/no-such-page')->assertNotFound();
    }

    public function test_both_pages_work_in_path_mode(): void
    {
        $this->enterPathMode();

        $this->get('http://preview.test/get-started')->assertOk()->assertSee('Use PHP 8.5.');
        $response = $this->get('http://preview.test/vignettes')->assertOk()->assertSee('Every vignette on the site');

        // Vignette links point into /{package} on the host the request came in on.
        $response->assertSee('http://preview.test/phit/vignettes/school-scores', false);
        $this->assertStringNotContainsString('phit.phpstats.test', $response->content());
    }

    public function test_install_steps_show_the_lines_each_package_publishes_and_invent_none(): void
    {
        $html = $this->get('http://phpstats.test/get-started')->assertOk()->content();
        $text = html_entity_decode(strip_tags($html));

        $withLines = 0;
        foreach (Package::all() as $package) {
            $this->assertStringContainsString($package->name(), $text, $package->key);

            foreach ($package->install() as $line) {
                $this->assertStringContainsString($line, $text, $package->key.' install line');
                $withLines++;
            }

            if ($package->install() === []) {
                // Either "does not carry install lines" or "nothing to install",
                // and the repository in place of lines nobody published.
                $this->assertMatchesRegularExpression(
                    '/(does not carry install lines for\s+'.preg_quote($package->name(), '/').'|'.preg_quote($package->name(), '/').'\s+has nothing to install yet)/',
                    preg_replace('/\s+/', ' ', $text),
                    $package->key,
                );
                $this->assertStringContainsString($package->repository(), $html, $package->key.' repository');
            }
        }

        $this->assertGreaterThan(0, $withLines, 'no package published any install line');

        // No install command for a package that published none.
        $invented = array_values(array_filter(
            Package::all(),
            fn (Package $package) => $package->install() === []
                && preg_match('/(composer require|pie install)\s+\S*'.preg_quote($package->key, '/').'/', $text) === 1,
        ));
        $this->assertSame([], $invented, 'an install line was invented');
    }

    public function test_install_steps_cover_a_synced_package_an_unsynced_one_and_one_not_built_yet(): void
    {
        // A content directory holding one synced fixture, so the three cases
        // hold whatever the real packages have synced by now.
        $content = $this->fixtureContent('synced-fixture', [
            'title' => 'synced-fixture',
            'tagline' => 'A synced fixture.',
            'install' => ['composer config repositories.synced-fixture vcs https://example.test/synced-fixture', 'composer require phpstats/synced-fixture:dev-main'],
            'requires' => ['PHP 8.5'],
        ]);

        $keys = ['synced-fixture', 'unsynced-fixture', 'unbuilt-fixture'];

        config([
            'phpstats.content_path' => $content,
            'phpstats.companions' => [],
            'projects' => [
                'synced-fixture' => $this->fixtureProject('synced-fixture', 'working'),
                'unsynced-fixture' => $this->fixtureProject('unsynced-fixture', 'working'),
                'unbuilt-fixture' => $this->fixtureProject('unbuilt-fixture', 'in-progress'),
            ],
            // The bar and the footer walk the layers, so they have to name the
            // fixtures too, or they look for packages that are no longer there.
            'phpstats.layers' => ['fixtures' => [
                'title' => 'Fixtures',
                'role' => 'Fixtures',
                'color' => 'violet',
                'icon' => 'table-cells',
                'packages' => array_combine($keys, array_map(fn (string $key) => "The {$key} package.", $keys)),
            ]],
            'phpstats.menus' => ['fixtures' => ['title' => 'Fixtures', 'short' => 'Fixtures', 'layers' => ['fixtures']]],
        ]);
        Package::flush();

        $text = preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(
            $this->get('http://phpstats.test/get-started')->assertOk()->content(),
        )));

        // Synced: its own lines and its own requirements, nothing added.
        $this->assertStringContainsString('composer config repositories.synced-fixture vcs https://example.test/synced-fixture', $text);
        $this->assertStringContainsString('composer require phpstats/synced-fixture:dev-main', $text);
        $this->assertStringContainsString('PHP 8.5', $text);

        // Unsynced but usable: no lines, and a pointer to the repository.
        $this->assertStringContainsString('does not carry install lines for unsynced-fixture', $text);
        $this->assertStringNotContainsString('composer require phpstats/unsynced-fixture', $text);

        // Not built yet: nothing to install.
        $this->assertStringContainsString('unbuilt-fixture has nothing to install yet', $text);
        $this->assertStringNotContainsString('composer require phpstats/unbuilt-fixture', $text);
    }

    public function test_package_names_are_monospace_on_both_root_pages(): void
    {
        foreach (['http://phpstats.test/get-started', 'http://phpstats.test/vignettes'] as $url) {
            $html = $this->get($url)->assertOk()->content();

            foreach (Package::all() as $package) {
                // Every name that labels something sits in a font-mono element.
                $this->assertMatchesRegularExpression(
                    '/<(a|span|p|h[1-6])[^>]*\bfont-mono\b[^>]*>\s*'.preg_quote($package->name(), '/').'\s*</',
                    $html,
                    "{$package->key} is not monospace on {$url}",
                );
            }
        }
    }

    public function test_the_site_bar_links_both_pages_and_marks_the_current_one(): void
    {
        // The bar carries all three links, in the bar itself and in the row
        // that replaces it on small screens.
        foreach (['Get started', 'Vignettes', 'About PHPstats'] as $label) {
            $this->assertSame(2, substr_count($this->bar($this->get('http://phpstats.test/about')->content()), ">{$label}</a>"), $label);
        }

        // Exactly the current page is marked, in both rows, and only there.
        $pages = [
            'http://phpstats.test/about' => 'About PHPstats',
            'http://phpstats.test/get-started' => 'Get started',
            'http://phpstats.test/vignettes' => 'Vignettes',
        ];

        foreach ($pages as $url => $label) {
            $bar = $this->bar($this->get($url)->assertOk()->content());

            $this->assertSame(2, preg_match_all('/aria-current="page"/', $bar), $url);
            foreach ($pages as $other) {
                $this->assertSame(
                    $other === $label ? 2 : 0,
                    preg_match_all('/aria-current="page"[^>]*>\s*'.preg_quote($other, '/').'\s*</', $this->collapse($bar)),
                    "{$other} on {$url}",
                );
            }
        }

        // The landing page marks none of them.
        $this->assertStringNotContainsString('aria-current="page"', $this->bar($this->get('http://phpstats.test/')->assertOk()->content()));
    }

    public function test_the_new_pages_carry_a_description_and_a_canonical_url_and_are_in_the_sitemap(): void
    {
        foreach (['http://phpstats.test/get-started', 'http://phpstats.test/vignettes'] as $url) {
            $html = $this->get($url)->assertOk()->content();

            $this->assertStringContainsString('<link rel="canonical" href="'.$url.'">', $html);
            $this->assertMatchesRegularExpression('/<meta name="description" content="[^"]+">/', $html);
        }

        $sitemap = $this->get('http://phpstats.test/sitemap.xml')->assertOk()->content();
        $this->assertStringContainsString('<loc>http://phpstats.test/get-started</loc>', $sitemap);
        $this->assertStringContainsString('<loc>http://phpstats.test/vignettes</loc>', $sitemap);
    }

    public function test_markdown_pages_render_blade_outside_code_only(): void
    {
        $html = Markdown::render(<<<'MD'
            Total: {{ 2 + 3 }}.

            <x-package-chip package="phit" />

            ```php
            echo "{{ not blade }}";
            ```
            MD);

        $this->assertStringContainsString('Total: 5.', $html);
        $this->assertStringContainsString('http://phit.phpstats.test', $html);
        $this->assertStringContainsString('{{ not blade }}', html_entity_decode(strip_tags($html)));

        $this->get('http://phpstats.test/about')->assertOk()->assertSee('About PHPstats')->assertSee('dplyr and tidyr');
        $this->get('http://phpstats.test/no-such-page')->assertNotFound();
    }

    /** Reboot in path mode, which routes/web.php reads as it registers routes. */
    private function enterPathMode(): void
    {
        putenv('PHPSTATS_PATHS=true');
        $this->refreshApplication();
        $this->artisan('migrate:fresh', ['--seed' => true]);
        Package::flush();

        $this->assertTrue(config('phpstats.paths'), 'path mode did not take effect');
    }

    /** A content directory holding one package's site.json. */
    private function fixtureContent(string $key, array $site): string
    {
        $path = sys_get_temp_dir().'/phpstats-fixture-content-'.getmypid();
        @mkdir($path.'/'.$key, 0777, true);
        file_put_contents($path.'/'.$key.'/site.json', json_encode($site));

        $this->beforeApplicationDestroyed(function () use ($path) {
            array_map('unlink', glob($path.'/*/*') ?: []);
            array_map('rmdir', glob($path.'/*') ?: []);
            @rmdir($path);
        });

        return $path;
    }

    private function fixtureProject(string $key, string $status): array
    {
        return [
            'name' => $key,
            'from_r' => null,
            'summary' => "The {$key} package.",
            'status' => $status,
            'status_label' => ucfirst($status),
            'repo' => "https://example.test/{$key}",
            'docs' => null,
            'overview' => ["The {$key} package."],
        ];
    }

    /** The markup with runs of whitespace collapsed, for matching attributes. */
    private function collapse(string $html): string
    {
        return preg_replace('/\s+/', ' ', $html);
    }

    /** Just the site bar, so the assertions do not see the page body. */
    private function bar(string $html): string
    {
        preg_match('#<header.*?</header>#s', $html, $match);

        return $match[0] ?? '';
    }
}
