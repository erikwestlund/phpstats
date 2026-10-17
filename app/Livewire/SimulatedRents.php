<?php

namespace App\Livewire;

use App\Data\SimulatedRentals;
use App\Models\SimulatedRental;
use Livewire\Component;
use Phibble\DataFrame;
use Phiz\Plot;

use function Phiz\aes;
use function Phplyr\{arrange, group_by, head, lm, mean, n, summarise};

/**
 * Vignette: fit rent ~ area_m2 + bedrooms + neighborhood on simulated data
 * and compare the estimates with the coefficients that generated it.
 *
 * phibble reads the table, phplyr summarises it, and phplyr's lm() fits the
 * model with Phit\fit_lm().
 */
class SimulatedRents extends Component
{
    public const SIZES = [100, 300, 1000, 3000];

    public int $n = 3000;

    public function render()
    {
        return view('livewire.simulated-rents', [
            'extensionLoaded' => function_exists('Phit\\fit_lm'),
            'fit' => $this->fit(),
        ]);
    }

    private function fit(): ?array
    {
        if (! function_exists('Phit\\fit_lm')) {
            return null;
        }

        $n = in_array($this->n, self::SIZES, true) ? $this->n : max(self::SIZES);

        // Riverside first, so it is the reference level.
        $rentals = DataFrame::fromQuery(SimulatedRental::query()->orderBy('id'))
            ->asFactor('neighborhood', array_keys(SimulatedRentals::NEIGHBORHOODS))
            |> head($n);

        $result = $rentals |> lm('monthly_rent ~ area_m2 + bedrooms + neighborhood');

        $byNeighborhood = $rentals
            |> group_by('neighborhood')
            |> summarise(rentals: n(), mean_area: mean('area_m2'), mean_rent: mean('monthly_rent'))
            |> arrange('neighborhood');

        $terms = [];
        foreach (SimulatedRentals::coefficients() as $term => $true) {
            $i = array_search($term, $result['columns'], true);
            $terms[] = [
                'term' => $term,
                'true' => $true,
                'estimate' => $result['coefficients'][$i],
                'std_error' => $result['std_errors'][$i],
                'lower' => $result['conf_int'][$i][0],
                'upper' => $result['conf_int'][$i][1],
            ];
        }

        return [
            'n' => $result['n_observations'],
            'by_neighborhood' => $byNeighborhood->toRows(),
            'sigma' => $result['sigma'],
            'terms' => $terms,
            'chart' => $this->chart(array_slice($terms, 3)),
        ];
    }

    /**
     * Neighborhood effects with 95% intervals, and the true values in red.
     */
    private function chart(array $terms): Plot
    {
        $values = array_map(fn ($t) => [...$t, 'term' => substr($t['term'], strlen('neighborhood'))], $terms);

        return Plot::of($values)
            ->aes(x: 'estimate', y: 'term', xmin: 'lower', xmax: 'upper')
            ->pointrange(color: '#4c78a8', linewidth: 2)
            ->point(aes(x: 'true'), shape: 'diamond', color: 'firebrick', size: 60)
            ->vline(0, color: 'gray', linetype: 'dashed')
            ->labs(x: 'Effect relative to Riverside (per month)', y: null)
            ->tooltip()
            ->size('container', 220);
    }
}
