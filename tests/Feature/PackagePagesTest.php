<?php

namespace Tests\Feature;

use App\Content\Package;
use App\Content\Results;
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
            ->assertSee('Statistics, Modeling, &amp; Visualization', false)
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

    public function test_the_package_bar_lists_the_sections_in_order(): void
    {
        // Every package has Benchmarks and Verification, after Overview, Docs,
        // and Examples where it has them.
        foreach (Package::all() as $key => $package) {
            $html = $this->get($package->url())->assertOk()->getContent();
            preg_match('#<nav class="ml-6[^"]*"[^>]*>(.*?)</nav>#s', $html, $bar);
            preg_match_all('#>\s*(Overview|Docs|Examples|Benchmarks|Verification)\b#', $bar[1], $labels);
            $expected = array_values(array_filter(['Overview', $package->hasDocs() || $package->externalDocs() ? 'Docs' : null, $package->listedExamples() || $package->vignettes() ? 'Examples' : null, 'Benchmarks', 'Verification']));
            $this->assertSame($expected, $labels[1], $key);
            $this->assertStringContainsString('href="'.e($package->url('benchmarks')).'"', $bar[1], $key);
            $this->assertStringContainsString('href="'.e($package->url('verification')).'"', $bar[1], $key);
        }

        // A topic page marks its section as current.
        $phit = Package::find('phit');
        $html = $this->get($phit->url('benchmarks'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#href="'.preg_quote(e($phit->url('benchmarks')), '#').'"\s+aria-current="page"#', $html);
    }

    public function test_a_package_with_no_records_says_so_without_charts(): void
    {
        // A package with no records says so in one line, with no table or chart.
        $this->fixtureSite('phiz', []);
        $phiz = Package::find('phiz');
        $this->get($phiz->url('benchmarks'))->assertOk()
            ->assertSee('No benchmark measurements are published yet.')
            ->assertDontSee('data-phiz="', false)
            ->assertDontSee('<table', false);
        $this->get($phiz->url('verification'))->assertOk()
            ->assertSee('No verification checks are published yet.')
            ->assertDontSee('<table', false);

        // A gap the package names is shown with the empty state.
        $this->fixtureSite('phiz', ['evidence_gaps' => ['benchmarks' => 'No benchmark covers chart export yet.']]);
        $this->get(Package::find('phiz')->url('benchmarks'))->assertOk()
            ->assertSee('No benchmark measurements are published yet.')
            ->assertSee('No benchmark covers chart export yet.');
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
        // install and the kernel registration. The check, doctor, is on the
        // installation page.
        $this->assertStringContainsString('composer global require phpstats/juphyter', $text);
        $this->assertStringContainsString('juphyter -- install', $text);
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
            $this->assertDoesNotMatchRegularExpression('/\b1 (workloads|pages|short, runnable examples|comparisons pass)\b/', $text, $key);

            $examples = count($package->listedExamples());
            if ($examples > 0) {
                $this->assertStringContainsString($examples.' short, runnable '.($examples === 1 ? 'example' : 'examples'), $text, $key);
            }
            $benchmarks = count($package->benchmarks());
            if ($benchmarks > 0) {
                $this->assertStringContainsString($benchmarks.' '.($benchmarks === 1 ? 'workload' : 'workloads'), $text, $key);
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
            ->assertSee('Statistics, Modeling, &amp; Visualization', false)
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
        $cmdstan = Package::find('cmdstanphp');
        $text = html_entity_decode(strip_tags($this->get($cmdstan->url('benchmarks'))->assertOk()->getContent()));
        foreach (['Timing boundaries', 'Median over the timed runs', '7 timed runs', '2 AMD Zen 5 cores', 'Memory metric', 'Raw record', 'site/benchmarks/interfaces-workload.json'] as $fact) {
            $this->assertStringContainsString($fact, $text);
        }
        $this->assertStringNotContainsString('faster', $text);

        // A record that gives no statistic or memory gets none invented.
        $this->fixtureRecord('phiz', 'benchmarks', 'bare', [
            'title' => 'Bare: one chart',
            'unit' => 'ms',
            'environment' => ['repeats' => 5],
            'rows' => [['case' => 'One chart', 'implementation' => 'phiz', 'value' => 12.5]],
        ], only: true);
        $text = html_entity_decode(strip_tags($this->get(Package::find('phiz')->url('benchmarks'))->assertOk()->getContent()));
        $this->assertStringContainsString('5 timed runs', $text);
        $this->assertStringNotContainsString('Median', $text);
        $this->assertStringContainsString('Memory not measured.', $text);
    }

    public function test_each_memory_metric_is_labeled_by_its_own_name(): void
    {
        $html = $this->get(Package::find('phpolars')->url('benchmarks'))->assertOk()->getContent();
        $this->assertStringContainsString('>Peak anonymous memory</th>', $html);
        $this->assertStringContainsString('>Peak resident set size</th>', $html);
        $this->assertStringContainsString('Peak anonymous memory (MiB)', $html);
        $this->assertStringContainsString('Peak resident set size (MiB)', $html);
        $this->assertStringNotContainsString('>Memory</th>', $html);
    }

    public function test_a_missing_measurement_reads_not_measured_and_is_not_charted(): void
    {
        $this->fixtureRecord('phiz', 'benchmarks', 'export', [
            'title' => 'Export: one chart',
            'measure' => 'Elapsed time',
            'unit' => 'ms',
            'memory_measure' => 'peak anonymous memory',
            'rows' => [
                ['case' => 'One chart', 'implementation' => 'phiz', 'value' => 12.5, 'peak_memory_mib' => 30.0],
                ['case' => 'One chart', 'implementation' => 'ggplot2', 'value' => null, 'peak_memory_mib' => null],
            ],
        ], only: true);
        $html = $this->get(Package::find('phiz')->url('benchmarks'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'not measured'));
        // The charts hold phiz's bar only, and no zero for ggplot2.
        preg_match_all('/data-phiz="([^"]*)"/', $html, $charts);
        $this->assertCount(2, $charts[1]);
        foreach ($charts[1] as $chart) {
            $this->assertStringNotContainsString('ggplot2', html_entity_decode($chart));
        }
    }

    public function test_verification_topics_show_every_status_in_plain_text(): void
    {
        $phpolars = Package::find('phpolars');
        $text = html_entity_decode(strip_tags($this->get($phpolars->url('verification.topic', ['id' => 'integration']))->assertOk()->getContent()));
        $this->assertMatchesRegularExpression('/PHP-FPM.*skipped.*php-fpm or cgi-fcgi is not installed/s', $text);
        $this->assertStringContainsString('0 fail, 1 skipped', $text);
        $this->assertStringNotContainsString('No comparison failed, and none was skipped.', $text);

        // Cases with a category are summarized by it.
        $this->get($phpolars->url('verification.topic', ['id' => 'py-polars-fixtures']))->assertOk()->assertSee('By category');

        // The section summary counts skipped checks on their own.
        $this->get($phpolars->url('verification'))->assertOk()->assertSee('>Skipped</th>', false);

        // A relative error left null at a zero reference is undefined, not
        // zero, and a case with no status is never counted as a pass.
        $this->fixtureRecord('phiz', 'verifications', 'export', [
            'title' => 'Export: agreement with Vega-Lite',
            'cases' => [
                ['name' => 'zero', 'quantity' => 'value', 'max_abs_error' => 0.0, 'max_rel_error' => null, 'status' => 'pass'],
                ['name' => 'unknown', 'quantity' => 'value', 'max_abs_error' => 1e-3],
            ],
        ]);
        $text = html_entity_decode(strip_tags($this->get(Package::find('phiz')->url('verification.topic', ['id' => 'export']))->assertOk()->getContent()));
        $this->assertStringContainsString('undefined at r = 0', $text);
        $this->assertStringContainsString('1 pass, 0 fail, 0 skipped, 1 not reported', $text);
    }

    public function test_a_topic_states_its_note_symbols_and_errors_once(): void
    {
        $this->fixtureRecord('phiz', 'verifications', 'specs', [
            'title' => 'Specifications',
            'label' => 'Specifications',
            'note' => 'Values compared after JSON encoding.',
            'comparison' => [['id' => 'values', 'kind' => 'tolerance', 'applies_to' => 'Numbers', 'atol' => 1e-9, 'rtol' => 1e-9, 'rel_floor' => 1]],
            'cases' => [
                ['name' => 'bar', 'quantity' => 'spec', 'rule' => 'values', 'max_abs_error' => 0.0, 'max_rel_error' => 0.0, 'status' => 'pass'],
                ['name' => 'line', 'quantity' => 'spec', 'rule' => 'values', 'max_abs_error' => 0.0, 'max_rel_error' => 0.0, 'status' => 'pass'],
            ],
        ]);
        $text = html_entity_decode(strip_tags($this->get(Package::find('phiz')->url('verification.topic', ['id' => 'specs']))->assertOk()->getContent()));
        $this->assertSame(1, substr_count($text, 'Values compared after JSON encoding.'));
        $this->assertSame(1, substr_count($text, 'x: package value; r: reference value.'));
        $this->assertStringContainsString('Absolute error: |x − r|.', $text);
        $this->assertStringContainsString('Relative error: |x − r| / max(|r|, 1).', $text);
        // The cases show the rule's criterion, never its id.
        $this->assertStringContainsString('|x − r| ≤ 1e-9 + 1e-9 × max(|r|, 1)', $text);
        $this->assertDoesNotMatchRegularExpression('/\(values\)/', $text);
    }

    public function test_the_overview_links_the_functions_and_methods_section(): void
    {
        // juphyter's Reference documents commands and settings, not a PHP API.
        foreach (Package::all() as $key => $package) {
            $this->assertSame($key === 'juphyter' ? null : 'Functions and methods', $package->apiReference()['title'] ?? null, $key);
        }
        $phibble = Package::find('phibble');
        $api = $phibble->apiReference();
        $html = $this->get($phibble->url())->assertOk()->getContent();
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="'.e($api['url']).'"'));
        $this->assertStringContainsString('Functions and methods', $html);

        // Without such a section: no link, and the docs card reads Documentation.
        $root = $this->copyContent();
        $docs = json_decode(File::get("{$root}/phibble/docs.json"), true);
        File::put("{$root}/phibble/docs.json", json_encode([...$docs, 'nav' => array_values(array_filter($docs['nav'], fn (array $item) => ($item['section'] ?? null) !== 'Functions and methods'))]));
        Package::flush();
        $phibble = Package::find('phibble');
        $this->assertNull($phibble->apiReference());
        $this->get($phibble->url())->assertOk()->assertSee('Documentation')->assertDontSee('of guides and reference');
    }

    public function test_docs_pages_drop_the_section_label_and_group_on_this_page(): void
    {
        $phit = Package::find('phit');
        $path = collect($phit->docsPaths())->first(fn ($p) => collect($phit->docsPage($p)['headings'])->contains('level', 3) && count($phit->docsPage($p)['headings']) > 3);
        $page = $phit->docsPage($path);
        $html = $this->get($phit->url('docs', ['path' => $path]))->assertOk()->getContent();
        // No section label above the title.
        $this->assertDoesNotMatchRegularExpression('#<p class="text-sm font-semibold[^"]*">[^<]*</p>\s*<h1#', $html);
        // The right-hand list scrolls on its own, and narrow screens get a disclosure with the same entries.
        $this->assertStringContainsString('aria-label="On this page"', $html);
        $this->assertStringContainsString('xl:overflow-y-auto', $html);
        $this->assertMatchesRegularExpression('#<div class="mt-6 space-y-2 xl:hidden">\s*<details class="group/toc#', $html);
        $h3 = collect($page['headings'])->firstWhere('level', 3);
        $this->assertSame(2, substr_count($html, 'href="#'.$h3['id'].'"'));
    }

    public function test_output_checks_never_print_joined_keys(): void
    {
        $entries = Results::checkEntries(['max_abs_difference_vs_R_statsmodels' => 3.5e-6, 'max_abs_difference_vs_R_phit' => 3.4e-6, 'tolerance' => '1e-09 + 1e-07·|R|', 'posterior_mean_mu' => ['CmdStanPHP' => 4.25, 'CmdStanR' => 4.25], 'draws_identical' => true]);
        $this->assertSame([
            ['label' => 'Largest absolute difference from R', 'value' => null, 'lines' => ['statsmodels: 3.5e-6', 'phit: 3.4e-6']],
            ['label' => 'Tolerance', 'value' => '1e-09 + 1e-07·|R|', 'lines' => []],
            ['label' => 'Posterior mean μ', 'value' => null, 'lines' => ['CmdStanPHP: 4.25', 'CmdStanR: 4.25']],
            ['label' => 'Draws identical', 'value' => 'yes', 'lines' => []],
        ], $entries);

        // No page prints a record's raw check key.
        foreach (Package::all() as $key => $package) {
            if ($package->benchmarks() === []) {
                continue;
            }
            $text = html_entity_decode(strip_tags($this->get($package->url('benchmarks'))->assertOk()->getContent()));
            foreach ($package->benchmarks() as $record) {
                // A key that is one plain word, such as dplyr, is also its label.
                foreach (array_filter(array_keys($record['check'] ?? []), fn ($field) => str_contains((string) $field, '_')) as $field) {
                    $this->assertStringNotContainsString((string) $field, $text, "{$key}/{$record['id']}");
                }
            }
        }
    }

    public function test_phit_overview_lists_its_capabilities(): void
    {
        $phit = Package::find('phit');
        $capabilities = $phit->capabilities();
        $this->assertSame(['Supported models', 'Probability distributions'], array_column($capabilities['columns'], 'heading'));
        $this->assertSame('Other functions', $capabilities['other']['heading']);
        $html = $this->get($phit->url())->assertOk()->getContent();
        $this->assertStringContainsString('<code>fit_mixed_gaussian()</code>', $html);
        $this->assertStringContainsString('md:grid-cols-2', $html);
        $this->assertStringNotContainsString('Every function is in the Phit namespace', $html);
        foreach (array_merge($capabilities['columns'], [$capabilities['other']]) as $list) {
            $this->assertNotNull($list['url'], $list['heading']);
            $this->assertStringContainsString('href="'.e($list['url']).'"', $html);
        }
        // A package without the key shows no such lists.
        $this->assertNull(Package::find('phibble')->capabilities());
    }

    public function test_docs_pages_list_the_examples_that_use_their_functions(): void
    {
        // phit's models reference documents fit_ols(), which its linear model example calls.
        $phit = Package::find('phit');
        $this->assertContains('fit_ols', $phit->functionEntries()['functions/models']);
        $titles = array_column($phit->relatedExamples('functions/models'), 'title');
        $this->assertContains('Fit a linear model and predict', $titles);
        $this->assertLessThanOrEqual(5, count($titles));
        $html = $this->get($phit->url('docs', ['path' => 'functions/models']))->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Examples using these functions"', $html);
        $this->assertStringContainsString('>Fit a linear model and predict</a>', $html);
        // The same list opens from a disclosure below xl.
        $this->assertMatchesRegularExpression('#<details class="group/ex[^>]*>\s*<summary[^>]*>\s*Examples#', $html);

        // The overview, an example page itself, and a page with no documented calls list none.
        $this->assertSame([], $phit->relatedExamples('index'));
        $this->assertSame([], $phit->relatedExamples('examples/linear-model'));
        $juphyter = Package::find('juphyter');
        foreach ($juphyter->docsPaths() as $path) {
            $this->assertSame([], $juphyter->relatedExamples($path), $path);
        }
        $this->get($juphyter->url('docs'))->assertOk()->assertDontSee('Examples using these functions');
    }

    public function test_benchmarks_are_one_page_with_tabs_per_workload(): void
    {
        $phit = Package::find('phit');
        $html = $this->get($phit->url('benchmarks'))->assertOk()->getContent();
        foreach ($phit->benchmarks() as $record) {
            $id = $record['id'];
            // A section per workload, an anchor in the sidebar, and three tabs whose panels have deep-linkable ids.
            $this->assertStringContainsString('<section id="'.$id.'"', $html);
            $this->assertStringContainsString('data-toc="'.$id.'"', $html);
            foreach (['results', 'methods', 'reproduction'] as $tab) {
                $this->assertStringContainsString('id="'.$id.'-'.$tab.'" aria-labelledby="'.$id.'-tab-'.$tab.'"', $html);
            }
        }
        $this->assertStringContainsString('role="tablist"', $html);
        // The panels keep their headings for screen readers, print, and no-script reading.
        $this->assertSame(3 * count($phit->benchmarks()), substr_count($html, 'tab-heading'));
        // Workloads follow site.json's order, under their groups.
        $positions = array_map(fn ($r) => strpos($html, '<section id="'.$r['id'].'"'), $phit->benchmarks());
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
        $this->get('http://phit.phpstats.test/benchmarks/ols-workload')->assertStatus(301)->assertRedirect($phit->url('benchmarks').'#ols-workload');
        $this->get('http://phit.phpstats.test/benchmarks/not-a-workload')->assertNotFound();
    }

    public function test_package_implementations_are_colored_as_phpstats(): void
    {
        foreach (['DataFrame', 'SqlTable', 'PolarsTable', 'phit reader', 'PHP reader', 'juphyter kernel', 'PHP engine alone', 'phiz + vl-convert', 'SQLite', 'CmdStanPHP', 'phpolars, 2 threads (default)', 'phibble (PHP reader)', 'Rust core'] as $name) {
            $this->assertSame('PHPstats', \App\Content\BenchmarkChart::kind($name), $name);
        }
        foreach (['R', 'SciPy', 'statsmodels', 'GPBoost', 'dplyr', 'py-polars', 'python3 ipykernel', 'CmdStanR', 'CmdStanPy', 'CmdStan alone', 'plain PHP arrays', 'plain PHP', 'lm (R)', 'pandas (Python)', 'tibble (R)'] as $name) {
            $this->assertSame('Other', \App\Content\BenchmarkChart::kind($name), $name);
        }
        $this->assertSame('Polyfill', \App\Content\BenchmarkChart::kind('phit-polyfill'));
    }

    public function test_old_evidence_urls_redirect_to_their_topics(): void
    {
        $phpolars = Package::find('phpolars');
        $this->get('http://phpolars.phpstats.test/benchmarks/py-polars')->assertStatus(301)->assertRedirect($phpolars->benchmarkUrl('read-group-join'));
        $this->get('http://phpolars.phpstats.test/benchmarks/php-arrays')->assertStatus(301)->assertRedirect($phpolars->benchmarkUrl('read-group-join'));
        $this->get('http://phit.phpstats.test/verifications/ols')->assertStatus(301)->assertRedirect('http://phit.phpstats.test/verification/ols');
        $this->get('http://phit.phpstats.test/benchmarks/not-a-workload')->assertNotFound();
        $this->get('http://phit.phpstats.test/verification/not-a-topic')->assertNotFound();

        // Old #fragments go on to the topic pages, including renamed ones.
        $this->get($phpolars->url('benchmarks'))->assertOk()
            ->assertSee('\\u0022py-polars\\u0022:\\u0022read-group-join\\u0022', false);

        // A docs report that left the docs nav redirects to the matching
        // evidence page; one the package still lists keeps its page.
        $phit = Package::find('phit');
        $root = $this->copyContent();
        $docs = json_decode(File::get("{$root}/phit/docs.json"), true);
        File::put("{$root}/phit/docs.json", json_encode([...$docs, 'nav' => [...$docs['nav'], ['title' => 'OLS: performance', 'path' => 'benchmarks/ols']]]));
        Package::flush();
        $this->assertNull(Package::find('phit')->evidenceRedirect('benchmarks/ols'));
        File::put("{$root}/phit/docs.json", json_encode($docs));
        Package::flush();
        $phit = Package::find('phit');
        $this->get($phit->url('docs', ['path' => 'benchmarks/ols']))->assertStatus(301)->assertRedirect($phit->benchmarkUrl('ols'));
        $this->get($phit->url('docs', ['path' => 'benchmarks/ols.html']))->assertStatus(301)->assertRedirect($phit->benchmarkUrl('ols'));
        $this->get($phit->url('docs', ['path' => 'verifications/lmm']))->assertRedirect($phit->url('verification.topic', ['id' => 'lmm']));
        $this->get($phit->url('docs', ['path' => 'notebooks/lmm-public-verification']))->assertRedirect($phit->url('verification.topic', ['id' => 'lmm-public']));
        $this->get($phit->url('docs', ['path' => 'verification']))->assertRedirect($phit->url('verification'));
        $this->get($phit->url('docs', ['path' => 'notebooks/r-compatibility']))->assertRedirect($phit->url('verification.topic', ['id' => 'r-compatibility']));
        $this->assertSame($phit->benchmarkUrl('ols'), Package::resolveLinks('phpstats://phit/docs/benchmarks/ols'));
    }

    public function test_removed_cmdstanphp_docs_pages_redirect_to_verification(): void
    {
        $cmdstan = Package::find('cmdstanphp');
        $nav = array_column($cmdstan->docsNav(), 'path');
        foreach (['verification', 'testing'] as $path) {
            if (in_array($path, $nav, true)) {
                $this->get($cmdstan->url('docs', ['path' => $path]))->assertOk();
            } else {
                $this->get($cmdstan->url('docs', ['path' => $path]))->assertStatus(301)->assertRedirect($cmdstan->url('verification'));
            }
        }
    }

    public function test_a_floored_relative_rule_is_written_with_its_floor(): void
    {
        $this->assertSame('|x − r| ≤ 1e-8 × max(|r|, 1)', Results::ruleText(['kind' => 'tolerance', 'rtol' => 1e-8, 'rel_floor' => 1]));
        $this->assertSame('|x − r| ≤ 1e-9 × |r|; at r = 0, x = 0 exactly', Results::ruleText(['kind' => 'tolerance', 'rtol' => 1e-9]));
        $this->assertSame('|x − r| ≤ 1e-9 + 1e-7 × |r|', Results::ruleText(['kind' => 'tolerance', 'atol' => 1e-9, 'rtol' => 1e-7]));
        $this->assertSame('exact equality', Results::ruleText(['kind' => 'exact']));
    }

    public function test_moved_docs_sections_send_their_fragments_on(): void
    {
        $phplyr = Package::find('phplyr');
        $moved = $phplyr->movedFragments('performance');
        $this->assertSame($phplyr->benchmarkUrl('workloads'), $moved['methods']);
        $this->assertSame($phplyr->benchmarkUrl('backends'), $moved['backend-pipelines']);
        $this->get($phplyr->url('docs', ['path' => 'performance']))->assertOk()->assertSee('var moved = ', false);
        $this->get($phplyr->url('docs'))->assertOk()->assertDontSee('var moved = ', false);
    }

    /** A copy of the synced content for this test to change, as the content path. */
    private function copyContent(): string
    {
        $root = base_path('storage/framework/testing/content-'.uniqid());
        File::copyDirectory(config('phpstats.content_path'), $root);
        config(['phpstats.content_path' => $root]);
        Package::flush();
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($root));

        return $root;
    }

    /** One more record for a package, listed in its site.json, or its only one with $only. */
    private function fixtureRecord(string $key, string $kind, string $id, array $record, bool $only = false): void
    {
        $root = $this->copyContent();
        File::ensureDirectoryExists("{$root}/{$key}/{$kind}");
        File::put("{$root}/{$key}/{$kind}/{$id}.json", json_encode($record));
        $site = json_decode(File::get("{$root}/{$key}/site.json"), true);
        $site[$kind] = [...($only ? [] : $site[$kind] ?? []), "site/{$kind}/{$id}.json"];
        File::put("{$root}/{$key}/site.json", json_encode($site));
        Package::flush();
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
