<?php

namespace App\Livewire;

use App\Data\SimulatedRentals;
use App\Models\SimulatedRental;
use Illuminate\Support\Str;
use Livewire\Component;
use Phibble\Frame;

use function Phplyr\{arrange, group_by, head, mean, mutate, n, summarise};

/**
 * Vignette: fit rent ~ area_m2 + bedrooms + neighborhood on simulated data
 * and compare the estimates with the coefficients that generated it.
 *
 * The data steps use phibble and phplyr. The neighborhood indicators are built
 * with mutate() and the fit calls stats_ols() until php_stats provides
 * stats_lm(), which phplyr's lm() wraps.
 */
class SimulatedRents extends Component
{
    public const SIZES = [100, 300, 1000, 3000];

    public int $n = 3000;

    public function render()
    {
        return view('livewire.simulated-rents', [
            'extensionLoaded' => extension_loaded('php_stats'),
            'fit' => $this->fit(),
        ]);
    }

    private function fit(): ?array
    {
        if (! extension_loaded('php_stats')) {
            return null;
        }

        $n = in_array($this->n, self::SIZES, true) ? $this->n : max(self::SIZES);
        $levels = array_slice(array_keys(SimulatedRentals::NEIGHBORHOODS), 1);

        // One indicator column per non-reference neighborhood, e.g. is_old_town.
        $indicators = [];
        foreach ($levels as $level) {
            $indicators['is_'.Str::snake($level)] = fn ($r) => $r->neighborhood === $level;
        }

        $rentals = Frame::fromQuery(SimulatedRental::query()->orderBy('id'))
            |> head($n);

        $design = $rentals |> mutate(...$indicators);

        $result = stats_ols(
            x: $design->toMatrix(['area_m2', 'bedrooms', ...array_keys($indicators)]),
            y: $design->toVector('monthly_rent'),
        );

        $byNeighborhood = $rentals
            |> group_by('neighborhood')
            |> summarise(rentals: n(), mean_area: mean('area_m2'), mean_rent: mean('monthly_rent'))
            |> arrange('neighborhood');

        $terms = [];
        foreach (array_keys(SimulatedRentals::coefficients()) as $i => $term) {
            $terms[] = [
                'term' => $term,
                'true' => SimulatedRentals::coefficients()[$term],
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
     * Vega-Lite spec: neighborhood effects with 95% intervals and the true values.
     */
    private function chart(array $terms): array
    {
        $values = array_map(fn ($t) => [...$t, 'term' => substr($t['term'], strlen('neighborhood: '))], $terms);
        $y = ['field' => 'term', 'type' => 'nominal', 'title' => null, 'sort' => null];

        return [
            '$schema' => 'https://vega.github.io/schema/vega-lite/v6.json',
            'width' => 'container',
            'height' => 220,
            'data' => ['values' => $values],
            'encoding' => ['y' => $y],
            'layer' => [
                [
                    'mark' => 'rule',
                    'encoding' => [
                        'x' => ['field' => 'lower', 'type' => 'quantitative', 'title' => 'Effect relative to Riverside (per month)'],
                        'x2' => ['field' => 'upper'],
                    ],
                ],
                [
                    'mark' => ['type' => 'point', 'filled' => true, 'size' => 60, 'tooltip' => true],
                    'encoding' => ['x' => ['field' => 'estimate', 'type' => 'quantitative']],
                ],
                [
                    'mark' => ['type' => 'tick', 'color' => 'firebrick', 'thickness' => 2],
                    'encoding' => ['x' => ['field' => 'true', 'type' => 'quantitative']],
                ],
            ],
        ];
    }
}
