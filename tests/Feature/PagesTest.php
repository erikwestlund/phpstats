<?php

namespace Tests\Feature;

use App\Data\SimulatedRentals;
use App\Livewire\SimulatedRents;
use App\Models\SimulatedRental;
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
        if (! extension_loaded('php_stats')) {
            $this->markTestSkipped('php_stats is not loaded.');
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
        if (! extension_loaded('php_stats')) {
            $this->markTestSkipped('php_stats is not loaded.');
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

    public function test_neighborhood_summary_matches_the_sample(): void
    {
        if (! extension_loaded('php_stats')) {
            $this->markTestSkipped('php_stats is not loaded.');
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
