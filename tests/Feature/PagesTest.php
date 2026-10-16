<?php

namespace Tests\Feature;

use App\Data\Benchmarks;
use App\Data\SchoolPosterior;
use App\Livewire\BenchmarkResults;
use App\Data\SimulatedRentals;
use App\Livewire\BayesianSchools;
use App\Livewire\RentDashboard;
use App\Livewire\SchoolEffects;
use App\Livewire\SimulatedRents;
use App\Livewire\SqlPipeline;
use App\Models\SimulatedRental;
use App\Models\StudentScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_landing_page_lists_every_project(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        foreach (config('projects') as $project) {
            $response->assertSee($project['name'])->assertSee($project['status_label']);
        }
    }

    public function test_each_project_has_a_page(): void
    {
        foreach (array_keys(config('projects')) as $key) {
            $this->get("/{$key}")->assertOk()->assertSee(config("projects.{$key}.summary"));
        }
    }

    public function test_unknown_project_is_not_found(): void
    {
        $this->get('/not-a-project')->assertNotFound();
    }

    public function test_school_scores_vignette_fits_the_model(): void
    {
        if (! function_exists('stats_lm')) {
            $this->markTestSkipped('php_stats with stats_lm is not loaded.');
        }

        $this->get('/vignettes/school-scores')
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
        if (! function_exists('stats_lm')) {
            $this->markTestSkipped('php_stats with stats_lm is not loaded.');
        }

        $this->get('/vignettes/simulated-rents')
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
        if (! function_exists('stats_lmer')) {
            $this->markTestSkipped('php_stats with stats_lmer is not loaded.');
        }

        $this->get('/vignettes/school-effects')
            ->assertOk()
            ->assertSee('Variance components')
            ->assertSee('REML fit on 250 students in 12 schools');

        $ml = Livewire::test(SchoolEffects::class)->set('reml', false)->viewData('fit');
        $this->assertSame('ML', $ml['mixed']['method']);
    }

    public function test_school_intercepts_shrink_by_the_stated_share(): void
    {
        if (! function_exists('stats_lmer')) {
            $this->markTestSkipped('php_stats with stats_lmer is not loaded.');
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
        if (! function_exists('stats_lmer')) {
            $this->markTestSkipped('php_stats with stats_lmer is not loaded.');
        }

        $this->get('/vignettes/bayesian-schools')->assertOk()->assertSee('School SD (τ)');

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
        if (! function_exists('stats_glm')) {
            $this->markTestSkipped('php_stats with stats_glm is not loaded.');
        }

        $this->get('/vignettes/rent-dashboard')->assertOk()->assertSee('3,000 rentals selected');

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

    public function test_benchmarks_page_shows_every_model(): void
    {
        $this->get('/benchmarks')->assertOk()->assertSee('Agrees with R')
            ->assertSee('Plain PHP, normal equations (coefficients only)')
            ->assertSee('Polyfill agrees')
            ->assertSee('phpolars PolarsTable');

        $data = Benchmarks::load();
        $this->assertSame(array_keys(Benchmarks::MODELS), array_keys($data['models']));

        foreach (array_keys(Benchmarks::MODELS) as $key) {
            Livewire::test(BenchmarkResults::class)->set('model', $key)
                ->assertSee($data['models'][$key]['label'])
                ->assertSee(number_format(end($data['models'][$key]['cases'])['n']));
        }
    }

    public function test_sql_pipeline_matches_the_in_memory_result(): void
    {
        $this->get('/vignettes/sql-pipeline')
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
        if (! function_exists('stats_lm')) {
            $this->markTestSkipped('php_stats with stats_lm is not loaded.');
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
}
