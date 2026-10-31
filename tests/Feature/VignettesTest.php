<?php

namespace Tests\Feature;

use App\Content\Package;
use App\Data\SchoolPosterior;
use App\Data\SimulatedRentals;
use App\Livewire\BayesianSchools;
use App\Livewire\RentDashboard;
use App\Livewire\SchoolEffects;
use App\Livewire\ScriptVignette;
use App\Models\SimulatedRental;
use App\Models\StudentScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VignettesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function vignette(string $slug): string
    {
        return Package::find(config("phpstats.vignettes.{$slug}.packages.0"))->url('vignette', ['slug' => $slug]);
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

    public function test_simulated_rents_are_seeded_reproducibly(): void
    {
        $this->assertSame(SimulatedRentals::ROWS, SimulatedRental::count());
        $this->assertSame(SimulatedRentals::generate(), SimulatedRentals::generate());
        $this->assertEqualsCanonicalizing(
            array_keys(SimulatedRentals::NEIGHBORHOODS),
            SimulatedRental::distinct()->pluck('neighborhood')->all(),
        );
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
