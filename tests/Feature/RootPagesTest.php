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

    protected function tearDown(): void
    {
        // Back to phpunit.xml's value. Unsetting it would let a local .env
        // in path mode decide the next test's routes.
        putenv('PHPSTATS_PATHS=false');
        $_ENV['PHPSTATS_PATHS'] = $_SERVER['PHPSTATS_PATHS'] = 'false';

        parent::tearDown();
    }

    public function test_installation_renders_on_the_root_host(): void
    {
        $html = $this->get('http://phpstats.test/installation')->assertOk()->content();

        // Collapsed, because CommonMark keeps the source's line breaks inside
        // a paragraph and the copy is wrapped.
        $text = $this->collapse(html_entity_decode(strip_tags($html)));

        $this->assertStringContainsString('Installation', $text);
        $this->assertStringContainsString('Every package supports PHP 8.3, 8.4, and 8.5.', $text);
        // Libraries install with Composer and the extensions with PIE.
        $this->assertStringContainsString('Composer installs the PHP libraries from Packagist.', $text);
        // Syntax guidance belongs to the usage docs. The menus' taglines can
        // mention the pipe, so only the article is checked.
        preg_match('#<article.*?</article>#s', $html, $article);
        $this->assertStringNotContainsString('pipe operator', strip_tags($article[0]));
        $this->assertStringContainsString('composer require phpstats/phplyr', $text);
        $this->assertStringContainsString('pie install phpstats/phit', $text);
        $this->assertStringNotContainsString('dev-main', $text);
    }

    public function test_the_example_index_renders_on_the_root_host(): void
    {
        $this->get('http://phpstats.test/examples')->assertOk()->assertSee('Examples');
        $this->get('http://phpstats.test/vignettes')->assertStatus(301)->assertRedirect('http://phpstats.test/examples');
    }

    public function test_the_example_index_lists_every_curated_example_once_under_its_owner(): void
    {
        $response = $this->get('http://phpstats.test/examples')->assertOk();
        $html = $response->content();
        preg_match('#<nav aria-label="Sections".*?</nav>#s', $html, $nav);

        $owners = 0;
        foreach (Package::all() as $key => $package) {
            if ($package->examples() === []) {
                $this->assertStringNotContainsString('href="#'.$key.'"', $nav[0], $key);

                continue;
            }
            $owners++;
            $this->assertStringContainsString('href="#'.$key.'"', $nav[0], $key);
            foreach ($package->examples() as $example) {
                // Linked once, to its page in the owner's docs, with its description.
                $this->assertSame(1, substr_count($html, 'href="'.e($example['url']).'"'), "{$key}/{$example['path']}");
                $this->assertStringContainsString('/docs/examples/', $example['url']);
                $response->assertSee($example['title'])->assertSee($example['description']);
            }
        }
        $this->assertGreaterThanOrEqual(3, $owners);

        // No live badges, package chips, or notes on how the site runs examples.
        $response->assertDontSee('>Live<', false)->assertDontSee('runs on the server');
    }

    public function test_old_example_urls_redirect_to_their_docs_pages(): void
    {
        $this->get('http://phpstats.test/vignettes/sql-pipeline')
            ->assertRedirect('http://phibble.phpstats.test/docs/examples/database-table');
        $this->get('http://phpstats.test/vignettes/school-effects')
            ->assertRedirect('http://phit.phpstats.test/vignettes/school-shrinkage');
        $this->get('http://phit.phpstats.test/vignettes/simulated-rents')
            ->assertRedirect('http://phit.phpstats.test/docs/examples/linear-model');
        $this->get('http://phplyr.phpstats.test/vignettes/reshaping')
            ->assertRedirect('http://phplyr.phpstats.test/docs/examples/pivot');
        $this->get('http://phpstats.test/vignettes/not-a-vignette')->assertNotFound();
    }

    public function test_the_catch_all_still_serves_markdown_pages_and_not_the_vignette_index(): void
    {
        $this->get('http://phpstats.test/about')->assertOk()->assertSee('About PHPstats');
        $this->get('http://phpstats.test/installation')->assertOk()->assertSee('Installation');

        // /examples is a route of its own, not a Markdown page.
        $this->assertFileDoesNotExist(resource_path('pages/examples.md'));
        $this->get('http://phpstats.test/no-such-page')->assertNotFound();
    }

    public function test_both_pages_work_in_path_mode(): void
    {
        $this->enterPathMode();

        $this->get('http://preview.test/installation')->assertOk()->assertSee('installs the PHP libraries from Packagist.');
        $this->get('http://preview.test/get-started')->assertStatus(301)->assertRedirect('http://preview.test/installation');
        $response = $this->get('http://preview.test/examples')->assertOk()->assertSee('Examples');

        // Example links point into /{package} on the host the request came in on.
        $response->assertSee('http://preview.test/phit/docs/examples/linear-model', false);
        $this->assertStringNotContainsString('phit.phpstats.test', $response->content());
    }

    public function test_installation_has_an_install_command_and_the_requirements_for_every_package(): void
    {
        $text = preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(
            $this->get('http://phpstats.test/installation')->assertOk()->content(),
        )));

        foreach (Package::all() as $package) {
            // A package with no synced content points at its repository.
            if ($package->install() === []) {
                $this->assertStringContainsString('The install steps are in the repository.', $text, $package->key);

                continue;
            }

            $this->assertMatchesRegularExpression(
                '/(composer (global )?require|pie install) phpstats\/'.preg_quote($package->key, '/').'\b/',
                $text,
                $package->key,
            );

            foreach ([...$package->requires(), ...$package->build()] as $requirement) {
                $this->assertStringContainsString(preg_replace('/\s+/', ' ', $requirement), $text, $package->key.' requirement');
            }
        }
    }

    public function test_installation_describes_the_version_check_and_leaves_optional_features_to_the_guides(): void
    {
        $text = preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(
            $this->get('http://phpstats.test/installation')->assertOk()->content(),
        )));

        // No promised version number: the check prints whichever is installed.
        $this->assertStringContainsString('Prints the installed version.', $text);
        $this->assertStringNotContainsString(Package::find('phit')->verify()['output'], $text);
        $this->assertStringContainsString('Full installation guide →', $text);
        $this->assertStringContainsString('aria-label="Full installation guide for phit"', $this->get('http://phpstats.test/installation')->content());

        $this->assertStringNotContainsString('Optional', $text);
        $this->assertStringNotContainsString('Supported platforms', $text);
        foreach (Package::find('phplyr')->optional() as $item) {
            $this->assertStringNotContainsString($item['for'], $text);
        }
    }

    public function test_get_started_redirects_to_installation(): void
    {
        $this->get('http://phpstats.test/get-started')->assertStatus(301)->assertRedirect('http://phpstats.test/installation');
    }

    public function test_installation_has_a_section_and_a_sidebar_link_for_every_package(): void
    {
        $html = $this->get('http://phpstats.test/installation')->assertOk()->content();
        $this->assertSame(1, preg_match('#<nav aria-label="Sections".*?</nav>#s', $html, $nav));

        $this->assertStringContainsString('href="#requirements"', $nav[0]);
        $this->assertStringContainsString('id="requirements"', $html);
        foreach (Package::all() as $package) {
            $this->assertStringContainsString('id="'.$package->key.'"', $html, $package->key);
            $this->assertStringContainsString('href="#'.$package->key.'"', $nav[0], $package->key);
        }
        // Requirements and the seven packages, with no category headings.
        $this->assertStringNotContainsString('</p>', $nav[0]);
        $this->assertStringContainsString('On this page', $html);
    }

    public function test_every_command_block_has_a_copy_button_and_juphyter_doctor_is_its_check(): void
    {
        $html = $this->get('http://phpstats.test/installation')->assertOk()->content();

        foreach (Package::all() as $package) {
            if ($package->install() === []) {
                continue;
            }
            $this->assertStringContainsString('aria-label="Copy install command for '.$package->name().'"', $html, $package->key);
            if ($package->verify()) {
                $this->assertStringContainsString('aria-label="Copy verify command for '.$package->name().'"', $html, $package->key);
            }
        }

        // juphyter doctor is the check, so it leaves the install block.
        preg_match('#<section id="juphyter".*?</section>#s', $html, $juphyter);
        $this->assertSame(1, substr_count(strip_tags($juphyter[0]), 'juphyter doctor'));
        $this->assertStringContainsString('Copy verify command for juphyter', $juphyter[0]);
    }

    public function test_cmdstanphp_lists_cmdstan_as_required_and_its_compilers_apart(): void
    {
        $text = $this->collapse(html_entity_decode(strip_tags(
            $this->get('http://phpstats.test/installation')->assertOk()->content(),
        )));

        $this->assertStringContainsString('CmdStan 2.37 or later', $text);
        $this->assertStringContainsString('To compile models', $text);
        $this->assertStringContainsString('To build from source', $text);
    }

    public function test_package_names_are_monospace_on_both_root_pages(): void
    {
        foreach (['http://phpstats.test/installation', 'http://phpstats.test/examples'] as $url) {
            $html = $this->get($url)->assertOk()->content();

            // The Examples index names only the packages that own an example.
            $packages = str_ends_with($url, 'examples') ? array_filter(Package::all(), fn (Package $package) => $package->examples() !== []) : Package::all();
            foreach ($packages as $package) {
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
        foreach (['Installation', 'Examples', 'About'] as $label) {
            $this->assertSame(2, substr_count($this->bar($this->get('http://phpstats.test/about')->content()), ">{$label}</a>"), $label);
        }

        // Exactly the current page is marked, in both rows, and only there.
        $pages = [
            'http://phpstats.test/about' => 'About',
            'http://phpstats.test/installation' => 'Installation',
            'http://phpstats.test/examples' => 'Examples',
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

        // The CSV options page is not in the bar, and the landing page marks
        // none of the pages that are.
        $this->assertStringNotContainsString('CSV options', $this->bar($this->get('http://phpstats.test/csv-options')->assertOk()->content()));
        $this->assertStringNotContainsString('aria-current="page"', $this->bar($this->get('http://phpstats.test/')->assertOk()->content()));
    }

    public function test_the_new_pages_carry_a_description_and_a_canonical_url_and_are_in_the_sitemap(): void
    {
        foreach (['http://phpstats.test/installation', 'http://phpstats.test/examples', 'http://phpstats.test/csv-options'] as $url) {
            $html = $this->get($url)->assertOk()->content();

            $this->assertStringContainsString('<link rel="canonical" href="'.$url.'">', $html);
            $this->assertMatchesRegularExpression('/<meta name="description" content="[^"]+">/', $html);
        }

        $sitemap = $this->get('http://phpstats.test/sitemap.xml')->assertOk()->content();
        $this->assertStringContainsString('<loc>http://phpstats.test/installation</loc>', $sitemap);
        $this->assertStringNotContainsString('<loc>http://phpstats.test/get-started</loc>', $sitemap);
        $this->assertStringContainsString('<loc>http://phpstats.test/examples</loc>', $sitemap);
        $this->assertStringContainsString('<loc>http://phpstats.test/csv-options</loc>', $sitemap);
    }

    /**
     * The CSV options page maps the readr-named options of phibble and phit
     * onto the Polars-named ones of phpolars, and leaves none of them out. The
     * two option lists are committed here rather than parsed out of
     * phibble/src/Io/CsvOptions.php and phpolars/src/io.rs, because a test
     * that parses PHP and Rust to check a documentation table is more fragile
     * than the table it guards. Each list's source and count is in its
     * comment, so a reader can check it against the package.
     */
    public function test_the_csv_options_page_names_every_option_of_both_readers(): void
    {
        // The 17 constructor parameters of Phibble\Io\CsvOptions.
        $phibble = [
            'types', 'delimiter', 'na', 'colNames', 'select', 'skip', 'nMax', 'quote',
            'escapeDouble', 'escapeBackslash', 'comment', 'trimWs', 'skipEmptyRows',
            'guessMax', 'nameRepair', 'decimalMark', 'groupingMark',
        ];

        // The 23 fields of struct CsvArgs in phpolars, in the camelCase names
        // that Phpolars\read_csv() takes them under.
        $phpolars = [
            'hasHeader', 'separator', 'nullValues', 'schema', 'schemaOverrides', 'nRows',
            'skipRows', 'skipRowsAfterHeader', 'inferSchemaLength', 'tryParseDates',
            'commentPrefix', 'quoteChar', 'eolChar', 'encoding', 'ignoreErrors',
            'missingUtf8IsEmptyString', 'truncateRaggedLines', 'decimalComma',
            'rowIndexName', 'rowIndexOffset', 'newColumns', 'raiseIfEmpty', 'glob',
        ];

        $this->assertCount(17, $phibble, 'the committed phibble list is no longer 17 options');
        $this->assertCount(23, $phpolars, 'the committed phpolars list is no longer 23 options');

        $rows = $this->tableRows($this->get('http://phpstats.test/csv-options')->assertOk()->content());
        $this->assertGreaterThan(20, count($rows), 'the options table is shorter than the two readers');

        // Every option is named in a cell of its own, as a code span.
        foreach ([...$phibble, ...$phpolars] as $option) {
            $this->assertNotSame(
                [],
                array_values(array_filter($rows, fn (string $row) => str_contains($row, '<code>'.$option.'</code>'))),
                "{$option} is not in the table",
            );
        }

        // An option with no counterpart is marked absent rather than left out,
        // so both of the other columns say so in its row.
        foreach (['nameRepair', 'glob'] as $option) {
            $row = array_values(array_filter($rows, fn (string $row) => str_contains($row, '<code>'.$option.'</code>')))[0];

            $this->assertSame(2, substr_count($row, '>absent</td>'), "{$option} is not shown as absent on both other sides");
        }

        // The column headers name packages, so they are labels.
        foreach (['phibble', 'phit', 'phpolars'] as $package) {
            $this->assertMatchesRegularExpression(
                '/<span[^>]*\bfont-mono\b[^>]*>\s*'.$package.'\s*<\/span>/',
                $rows[0],
                "{$package} is not monospace in the table head",
            );
        }
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
        $_ENV['PHPSTATS_PATHS'] = $_SERVER['PHPSTATS_PATHS'] = 'true';
        $this->refreshApplication();
        $this->artisan('migrate:fresh', ['--seed' => true]);
        Package::flush();

        $this->assertTrue(config('phpstats.paths'), 'path mode did not take effect');
    }

    /** The markup with runs of whitespace collapsed, for matching attributes. */
    private function collapse(string $html): string
    {
        return preg_replace('/\s+/', ' ', $html);
    }

    /**
     * The rows of the page's first table, head row first, each with its runs
     * of whitespace collapsed.
     *
     * @return list<string>
     */
    private function tableRows(string $html): array
    {
        preg_match('#<table.*?</table>#s', $html, $table);
        preg_match_all('#<tr.*?</tr>#s', $table[0] ?? '', $rows);

        return array_map(fn (string $row) => $this->collapse($row), $rows[0]);
    }

    /** Just the site bar, so the assertions do not see the page body. */
    private function bar(string $html): string
    {
        preg_match('#<header.*?</header>#s', $html, $match);

        return $match[0] ?? '';
    }
}
