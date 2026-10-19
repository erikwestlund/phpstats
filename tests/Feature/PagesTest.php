<?php

namespace Tests\Feature;

use App\Content\Example;
use App\Content\Markdown;
use App\Content\Package;
use App\Data\SchoolPosterior;
use App\Data\SimulatedRentals;
use App\Livewire\BayesianSchools;
use App\Livewire\CsvReader;
use App\Livewire\GlmModels;
use App\Livewire\PolarsPipeline;
use App\Livewire\RandomEffects;
use App\Livewire\RentDashboard;
use App\Livewire\SchoolEffects;
use App\Livewire\ScriptVignette;
use App\Livewire\SimulatedRents;
use App\Livewire\SqlPipeline;
use App\Models\SimulatedRental;
use App\Models\StudentScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Phiz\Plot;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function vignette(string $slug): string
    {
        return Package::find(config("phpstats.vignettes.{$slug}.packages.0"))->url('vignette', ['slug' => $slug]);
    }

    public function test_landing_page_links_every_package(): void
    {
        $response = $this->get('http://phpstats.test/')->assertOk()->assertSee('Modern statistical computing for PHP.');

        foreach (Package::keys() as $key) {
            $response->assertSee("http://{$key}.phpstats.test", false);
        }
    }

    public function test_landing_tabs_come_from_markdown_pages_that_call_php(): void
    {
        $response = $this->get('http://phpstats.test/')->assertOk()
            ->assertSee('Simplify Statistical Workflows in PHP Applications with Native PHP')
            ->assertSee('Read data from your app')
            ->assertSee('&lt;date&gt;', false)            // phibble's inferred column types
            ->assertSee('2,400 rows')
            ->assertDontSee('failed to run')
            ->assertDontSee('checked against R')
            ->assertDontSee('<!--blade:', false);

        if (function_exists('Phit\\fit_lm')) {
            $response->assertSee('customerReturning');      // the lm() coefficients
        }
    }

    public function test_every_package_has_code_on_the_landing_page_and_it_runs(): void
    {
        $requires = ['phibble' => 'phibble', 'phpolars' => 'phpolars', 'phplyr' => 'phplyr', 'phiz' => 'phiz', 'phit' => 'phit'];

        $response = $this->get('http://phpstats.test/')->assertOk();
        foreach (Package::keys() as $key) {
            $this->assertFileExists(Example::path("packages/{$key}"));
            $response->assertSee("package-code-{$key}", false);
        }

        // CmdStanPHP's snippet needs CmdStan, which pages never run.
        foreach ($requires as $key => $package) {
            if (ScriptVignette::available($package) && ScriptVignette::available('phplyr')) {
                $this->assertNotNull(Example::run("packages/{$key}"), $key);
            }
        }
    }

    public function test_examples_give_the_same_result_with_the_pipe_and_the_chain(): void
    {
        $requires = ['model' => 'phit', 'polars' => 'phpolars'];
        $compared = 0;

        foreach (glob(resource_path('examples/*/*.chain.php')) as $chain) {
            $name = basename(dirname($chain)).'/'.basename($chain, '.chain.php');
            if (! ScriptVignette::available($requires[basename($name)] ?? 'phplyr')) {
                continue;
            }
            $normalize = fn (mixed $result) => match (true) {
                $result instanceof Plot => $result->toArray(),
                is_object($result) => [$result->types(), $result->toRows()],
                default => $result,
            };

            $this->assertEquals($normalize(Example::run($name)), $normalize(Example::run($name, 'chain')), $name);
            $this->assertStringNotContainsString('use function', Example::code($name)['chain']);
            $compared++;
        }

        $this->assertGreaterThan(0, $compared);
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

    public function test_package_pages_link_the_family_and_related_packages(): void
    {
        $response = $this->get('http://phplyr.phpstats.test/')->assertOk();

        // The three menus link every package, and the mark links the landing page.
        foreach (Package::keys() as $key) {
            $response->assertSee("http://{$key}.phpstats.test", false);
        }
        $response->assertSee('Processing &amp; Visualization', false)
            ->assertSee('Statistical Modeling')
            ->assertSee('aria-label="PHPstats home"', false)
            ->assertSee('Connected packages')
            ->assertSee('phplyr functions take phibble frames and return new ones.');

        // The other side of the relation lists it too.
        $this->get('http://phibble.phpstats.test/')->assertSee('Used by')->assertSee('phplyr functions take phibble frames');
    }

    public function test_docs_nav_is_grouped_by_folder(): void
    {
        $groups = Package::find('phpolars')->docsGroups();

        $this->assertSame(['Guide', 'Reference'], array_keys($groups));
        $this->assertContains('reference/series', array_column($groups['Reference'], 'path'));
        $this->assertSame('Notebooks', Package::find('phit')->docsGroupOf('notebooks/lmm-math'));
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

    public function test_synced_docs_render_with_resolved_links(): void
    {
        $package = Package::find('phit');
        $this->assertTrue($package->hasDocs());

        $index = $this->get('http://phit.phpstats.test/docs')->assertOk();
        foreach ($package->docsNav() as $item) {
            $index->assertSee($item['title']);
            $page = $this->get($package->url('docs', ['path' => $item['path'] === 'index' ? null : $item['path']]))->assertOk();
            $this->assertStringNotContainsString('phpstats://', $page->getContent());
        }

        $this->get('http://phit.phpstats.test/docs/notebooks/lmm-math')->assertOk()
            ->assertSee('class="math inline"', false)
            ->assertSee('mathjax@4', false);
    }

    public function test_benchmarks_and_verification_render_every_file(): void
    {
        foreach (['phit', 'phpolars'] as $key) {
            $package = Package::find($key);
            $this->assertNotEmpty($package->benchmarks());
            $this->assertNotEmpty($package->verifications());

            $benchmarks = $this->get($package->url('benchmarks'))->assertOk();
            foreach ($package->benchmarks() as $benchmark) {
                $benchmarks->assertSee($benchmark['title']);
            }
            $verification = $this->get($package->url('verification'))->assertOk();
            foreach ($package->verifications() as $report) {
                $verification->assertSee($report['title']);
            }
        }
    }

    public function test_script_vignettes_run_or_explain_what_is_missing(): void
    {
        foreach (['phit', 'phpolars'] as $key) {
            $package = Package::find($key);
            foreach ($package->vignettes() as $vignette) {
                if ($vignette['kind'] !== 'script') {
                    continue;
                }
                $response = $this->get($package->url('vignette', ['slug' => $vignette['slug']]))->assertOk()->assertSee($vignette['title']);
                $missing = array_filter($vignette['uses'], fn ($use) => ! ScriptVignette::available($use));
                if ($missing !== []) {
                    $response->assertSee('which this server does not have');
                } else {
                    $response->assertDontSee('The vignette failed to run')->assertDontSee('bg-red-50', false);
                }
            }
        }
    }

    public function test_script_vignette_inputs_are_validated(): void
    {
        $inputs = [
            'rows' => ['type' => 'select', 'options' => [1000 => 'a', 5000 => 'b'], 'default' => 1000],
            'hours' => ['type' => 'number', 'min' => 0, 'max' => 20, 'default' => 8],
            'flag' => ['type' => 'checkbox', 'default' => false],
        ];
        $this->assertSame(['rows' => 5000, 'hours' => 20, 'flag' => true],
            ScriptVignette::cleanInputs($inputs, ['rows' => '5000', 'hours' => '999', 'flag' => '1']));
        $this->assertSame(['rows' => 1000, 'hours' => 8, 'flag' => false],
            ScriptVignette::cleanInputs($inputs, ['rows' => 'DROP', 'hours' => 'x']));
    }

    public function test_school_scores_vignette_fits_the_model(): void
    {
        if (! function_exists('Phit\\fit_lm')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('school-scores'))
            ->assertOk()
            ->assertSee('Study hours')
            ->assertSee('n = 250');
    }

    public function test_simulated_rents_are_seeded_reproducibly(): void
    {
        $this->assertSame(SimulatedRentals::ROWS, SimulatedRental::count());
        $this->assertSame(SimulatedRentals::generate(), SimulatedRentals::generate());
        $this->assertEqualsCanonicalizing(
            array_keys(SimulatedRentals::NEIGHBORHOODS),
            SimulatedRental::distinct()->pluck('neighborhood')->all(),
        );
    }

    public function test_simulated_rents_vignette_recovers_the_coefficients(): void
    {
        if (! function_exists('Phit\\fit_lm')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('simulated-rents'))
            ->assertOk()
            ->assertSee('simulated')
            ->assertSee('n = 3,000');

        $fit = Livewire::test(SimulatedRents::class)->set('n', 3000)->viewData('fit');

        foreach ($fit['terms'] as $term) {
            $this->assertEqualsWithDelta($term['true'], $term['estimate'], 4 * $term['std_error'], $term['term']);
        }
    }

    public function test_school_effects_vignette_reports_variance_components(): void
    {
        if (! function_exists('Phit\\fit_lmer')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('school-shrinkage'))
            ->assertOk()
            ->assertSee('Variance components')
            ->assertSee('REML fit on 250 students in 12 schools');

        $ml = Livewire::test(SchoolEffects::class)->set('reml', false)->viewData('fit');
        $this->assertSame('ML', $ml['mixed']['method']);
    }

    public function test_school_intercepts_shrink_by_the_stated_share(): void
    {
        if (! function_exists('Phit\\fit_lmer')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $fit = Livewire::test(SchoolEffects::class)->viewData('fit');
        $intercept = $fit['mixed']['named']['(Intercept)'];
        $residuals = [];
        foreach (StudentScore::query()->get() as $i => $student) {
            $residuals[$student->school][] = $fit['mixed']['marginal_residuals'][$i];
        }

        // The random intercept is the school's mean residual times (1 - shrinkage).
        foreach ($fit['schools'] as $school) {
            $mean = array_sum($residuals[$school['school']]) / count($residuals[$school['school']]);
            $this->assertEqualsWithDelta((1 - $school['shrinkage']) * $mean, $school['partial_pooling'] - $intercept, 1e-9);
        }
    }

    public function test_stored_posterior_is_complete_and_converged(): void
    {
        $posterior = SchoolPosterior::load();
        $meta = $posterior['meta'];

        $this->assertSame(SchoolPosterior::FORMULA, $meta['formula']);
        $this->assertSame(StudentScore::distinct()->orderBy('school')->pluck('school')->all(), $meta['schools']);
        $this->assertCount($meta['chains'] * $meta['iter_sampling'] / $meta['thin'], $posterior['draws']['tau']);
        $this->assertEquals(0, $meta['divergent']);
        $this->assertLessThan(1.01, max($posterior['convergence']['rhat']));
    }

    public function test_bayesian_vignette_compares_the_posterior_with_lmer(): void
    {
        if (! function_exists('Phit\\fit_lmer')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('bayesian-schools'))->assertOk()->assertSee('School SD (τ)');

        $fit = Livewire::test(BayesianSchools::class)->set('level', 95)->viewData('fit');
        foreach ($fit['parameters'] as $p) {
            $this->assertTrue($p['lower'] <= $p['lmer'] && $p['lmer'] <= $p['upper'], $p['name']);
        }

        $narrow = Livewire::test(BayesianSchools::class)->set('level', 50)->viewData('fit');
        $this->assertLessThan(
            $fit['parameters'][3]['upper'] - $fit['parameters'][3]['lower'],
            $narrow['parameters'][3]['upper'] - $narrow['parameters'][3]['lower'],
        );
    }

    public function test_rent_dashboard_reacts_to_its_filters(): void
    {
        if (! function_exists('Phit\\fit_glm')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('rent-dashboard'))->assertOk()->assertSee('3,000 rentals selected');

        $harbor = Livewire::test(RentDashboard::class)->set('neighborhoods', ['Harbor'])->set('budget', 2500);
        $this->assertSame(SimulatedRental::where('neighborhood', 'Harbor')->count(), $harbor->viewData('rentals'));
        $this->assertEqualsWithDelta(
            SimulatedRental::where('neighborhood', 'Harbor')->where('monthly_rent', '>', 2500)->count() / $harbor->viewData('rentals'),
            $harbor->viewData('summary')[0]['share_over_budget'],
            1e-12,
        );
        $this->assertGreaterThan(0, $harbor->viewData('model')['named']['area_m2']);

        $single = Livewire::test(RentDashboard::class)->set('minBedrooms', 2)->set('maxBedrooms', 2);
        $this->assertSame(['(Intercept)', 'area_m2'], $single->viewData('model')['columns']);

        $allOver = Livewire::test(RentDashboard::class)->set('minBedrooms', 4)->set('budget', 1750);
        $this->assertNull($allOver->viewData('model'));
        $this->assertStringContainsString('over the budget', $allOver->viewData('error'));
    }

    public function test_sql_pipeline_matches_the_in_memory_result(): void
    {
        $this->get($this->vignette('sql-pipeline'))
            ->assertOk()
            ->assertSee('GROUP BY &quot;neighborhood&quot;', false)
            ->assertSee('same rows in the same order');

        foreach (SqlPipeline::BEDROOMS as $bedrooms) {
            $view = Livewire::test(SqlPipeline::class)->set('minBedrooms', $bedrooms);
            $this->assertNotNull($view->viewData('maxDifference'), "min bedrooms {$bedrooms}");
            $this->assertLessThan(1e-9, $view->viewData('maxDifference'));
            $this->assertSame(
                SimulatedRental::where('bedrooms', '>=', $bedrooms)->count(),
                array_sum(array_column($view->viewData('sqlRows'), 'rentals')),
            );
        }
    }

    public function test_neighborhood_summary_matches_the_sample(): void
    {
        if (! function_exists('Phit\\fit_lm')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $summary = Livewire::test(SimulatedRents::class)->set('n', 300)->viewData('fit')['by_neighborhood'];
        $sample = SimulatedRental::query()->orderBy('id')->limit(300)->get();

        $this->assertSame(300, array_sum(array_column($summary, 'rentals')));

        foreach ($summary as $row) {
            $rents = $sample->where('neighborhood', $row['neighborhood'])->pluck('monthly_rent');
            $this->assertSame($rents->count(), $row['rentals']);
            $this->assertEqualsWithDelta($rents->avg(), $row['mean_rent'], 1e-9);
        }
    }

    public function test_glm_vignette_matches_r(): void
    {
        if (! function_exists('Phit\\fit_glm')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('glm-models'))->assertOk()->assertSee('Odds ratio');

        // R 4.6.1 glm() references from phit tests/fixtures/formula-r.json.
        $logit = Livewire::test(GlmModels::class)->viewData('fit')['result'];
        $this->assertEqualsWithDelta([-3.1709805238655515, 0.06367532072903229, 0.03424801009641754], $logit['coefficients'], 1e-9);
        $this->assertEqualsWithDelta(104.96865011458223, $logit['aic'], 1e-9);

        $probit = Livewire::test(GlmModels::class)->set('model', 'probit')->set('interaction', true)->viewData('fit')['result'];
        $this->assertSame(['(Intercept)', 'study_hours', 'practice_score', 'study_hours:practice_score'], $probit['columns']);
        $this->assertEqualsWithDelta([-1.5378267064499282, -0.03175571225494219, 0.014252493880737384, 0.001095948998577774], $probit['coefficients'], 1e-8);
        $this->assertEqualsWithDelta(98.87535805695676, $probit['deviance'], 1e-9);

        $poisson = Livewire::test(GlmModels::class)->set('model', 'poisson')->assertSee('Rate ratio')->viewData('fit')['result'];
        $this->assertSame('poisson', $poisson['family']);
        $this->assertSame(120, $poisson['n_observations']);
    }

    public function test_random_effect_intervals_use_the_conditional_variances(): void
    {
        if (! function_exists('Phit\\fit_lmer')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('random-effects'))->assertOk()->assertSee('Caterpillar plot');

        $fit = Livewire::test(RandomEffects::class)->viewData('fit');
        if (! isset($fit['term']['effects_cond_var'])) {
            $this->markTestSkipped('This phit build does not report conditional variances.');
        }
        foreach ($fit['schools'] as $school) {
            $this->assertEqualsWithDelta($school['closed_form_sd'], $school['effects']['(Intercept)']['sd'], 1e-9, $school['school']);
        }

        $slopes = Livewire::test(RandomEffects::class)->set('structure', 'correlated')->set('level', 80)->viewData('fit');
        $this->assertSame(['(Intercept)', 'study_hours'], $slopes['term']['columns']);
        $this->assertCount(2 * count($slopes['schools']), $slopes['chart']->data->rows);
    }

    public function test_csv_reader_reports_types_and_problems(): void
    {
        if (! function_exists('Phit\\read_csv')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('csv-reader'))->assertOk()->assertSee('expected 8 columns, got 5');

        $page = Livewire::test(CsvReader::class);
        $native = $page->viewData('native');
        $types = array_combine($native['names'], array_column($native['columns'], 'type'));
        $this->assertSame(['id' => 'int', 'zip' => 'string', 'signup' => 'date', 'last_seen' => 'datetime', 'plan' => 'string', 'active' => 'bool', 'monthly_spend' => 'float', 'visits' => 'int'], $types);
        $this->assertSame(1, $native['columns'][2]['failed']);
        $this->assertSame(['2026-01-15', '02139', '2026-10-01 08:30:00 UTC'], [$page->viewData('preview')[0][2], $page->viewData('preview')[0][1], $page->viewData('preview')[0][3]]);

        $parity = $page->viewData('parity');
        $this->assertTrue($parity['same_types'] && $parity['same_values'] && $parity['same_warnings']);

        $semicolon = Livewire::test(CsvReader::class)->set('csv', "a;b\n1,5;x\n")->set('delimiter', ';')->viewData('native');
        $this->assertSame(['a', 'b'], $semicolon['names']);

        $this->assertSame('The text is longer than 20,000 bytes.', Livewire::test(CsvReader::class)->set('csv', str_repeat('x', 20_001))->viewData('error'));
    }

    public function test_polars_pipeline_runs_with_or_without_polars(): void
    {
        $page = Livewire::test(PolarsPipeline::class)->set('copies', 1)->set('minBedrooms', 3);
        $this->assertSame(SimulatedRental::count(), $page->viewData('rowsIn'));
        $this->assertSame(
            SimulatedRental::where('bedrooms', '>=', 3)->count(),
            array_sum(array_column($page->viewData('frameRows'), 'rentals')),
        );

        if (! PolarsPipeline::polarsAvailable()) {
            $this->assertNull($page->viewData('polars'));
            $this->get($this->vignette('polars-pipeline'))->assertOk()->assertSee('runs only on the in-memory DataFrame');

            return;
        }

        $polars = Livewire::test(PolarsPipeline::class)->set('copies', 10)->viewData('polars');
        $this->assertNotNull($polars['max_difference']);
        $this->assertLessThan(1e-9, $polars['max_difference']);
    }

    public function test_what_if_predictions(): void
    {
        if (! function_exists('Phit\\predict')) {
            $this->markTestSkipped('phit is not loaded.');
        }

        $this->get($this->vignette('rent-dashboard'))->assertOk()->assertSee('Predicted rent');
        $this->get($this->vignette('school-shrinkage'))->assertOk()->assertSee('Population prediction');

        $page = Livewire::test(RentDashboard::class)->set('whatIfNeighborhood', 'Harbor')->set('whatIfArea', 80)->set('whatIfBedrooms', 3);
        $whatIf = $page->viewData('whatIf');
        $this->assertSame('monthly_rent ~ area_m2 + bedrooms + neighborhood', $whatIf['formula']);
        // The data were generated from this model, so the prediction lands near the true mean.
        $truth = SimulatedRentals::INTERCEPT + SimulatedRentals::AREA_M2 * 80 + SimulatedRentals::BEDROOMS * 3 + SimulatedRentals::NEIGHBORHOODS['Harbor'];
        $this->assertEqualsWithDelta($truth, $whatIf['rent']['fit'], 30);
        $this->assertEqualsWithDelta(2 * 1.96 * SimulatedRentals::SIGMA, $whatIf['rent']['upper'] - $whatIf['rent']['lower'], 30);
        $this->assertTrue($whatIf['over']['lower'] < $whatIf['over']['fit'] && $whatIf['over']['fit'] < $whatIf['over']['upper']);

        $single = Livewire::test(RentDashboard::class)->set('neighborhoods', ['Harbor'])->set('minBedrooms', 2)->set('maxBedrooms', 2)->set('whatIfBedrooms', 3)->viewData('whatIf');
        $this->assertSame('monthly_rent ~ area_m2', $single['formula']);
        $this->assertTrue($single['extrapolating']);

        $schools = Livewire::test(SchoolEffects::class)->set('whatIfSchool', 'school-07')->set('whatIfHours', 5)->viewData('fit');
        $school07 = collect($schools['schools'])->firstWhere('school', 'school-07');
        $hours = $schools['mixed']['named']['study_hours'];
        $this->assertEqualsWithDelta($school07['partial_pooling'] + 5 * $hours, $schools['what_if']['conditional'], 1e-9);
        $this->assertEqualsWithDelta($schools['mixed']['named']['(Intercept)'] + 5 * $hours, $schools['what_if']['population'], 1e-9);

        $new = Livewire::test(SchoolEffects::class)->viewData('fit')['what_if'];
        $this->assertSame($new['population'], $new['conditional']);
    }
}
