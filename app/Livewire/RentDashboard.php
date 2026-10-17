<?php

namespace App\Livewire;

use App\Data\SimulatedRentals;
use App\Models\SimulatedRental;
use Livewire\Component;
use Phibble\DataFrame;
use Phiz\Plot;
use Throwable;

use function Phplyr\{arrange, col, filter, glm, group_by, lm, mean, median, mutate, n, summarise};

/**
 * Vignette: a reactive dashboard over the simulated rentals. Every input is a
 * Livewire property; each change reruns the phplyr pipeline, refits the glm,
 * and re-renders the tables and phiz charts on the server.
 */
class RentDashboard extends Component
{
    public const BUDGETS = [1750, 2000, 2250, 2500, 2750, 3000, 3250];

    /** @var list<string> */
    public array $neighborhoods = [];

    public int $minBedrooms = 1;

    public int $maxBedrooms = 4;

    public int $budget = 2500;

    /** What-if inputs: one hypothetical rental to predict. */
    public string $whatIfNeighborhood = 'Riverside';

    public int $whatIfArea = 75;

    public int $whatIfBedrooms = 2;

    public function mount(): void
    {
        $this->neighborhoods = array_keys(SimulatedRentals::NEIGHBORHOODS);
    }

    public function render()
    {
        return view('livewire.rent-dashboard', $this->dashboard());
    }

    private function dashboard(): array
    {
        $neighborhoods = array_values(array_intersect(array_keys(SimulatedRentals::NEIGHBORHOODS), $this->neighborhoods));
        $budget = in_array($this->budget, self::BUDGETS, true) ? $this->budget : 2500;
        [$low, $high] = [min($this->minBedrooms, $this->maxBedrooms), max($this->minBedrooms, $this->maxBedrooms)];

        if ($neighborhoods === []) {
            return ['rentals' => 0, 'budget' => $budget, 'summary' => [], 'model' => null, 'error' => 'Select at least one neighborhood.'];
        }

        $rentals = DataFrame::fromQuery(SimulatedRental::query()->orderBy('id'))
            |> filter(col('neighborhood')->isIn($neighborhoods), col('bedrooms')->between($low, $high))
            |> mutate(over_budget: col('monthly_rent')->gt($budget));

        $summary = $rentals
            |> group_by('neighborhood')
            |> summarise(
                rentals: n(),
                median_rent: median('monthly_rent'),
                mean_area: mean('area_m2'),
                share_over_budget: mean('over_budget'),
            )
            |> arrange('neighborhood');

        $model = null;
        $error = null;
        $over = array_sum(array_map(intval(...), $rentals->column('over_budget')));
        // With one bedroom count selected, bedrooms is constant and leaves the model.
        $formula = $low === $high ? 'over_budget ~ area_m2' : 'over_budget ~ area_m2 + bedrooms';

        if ($over === 0 || $over === $rentals->nrow()) {
            $error = 'Every selected rental is '.($over === 0 ? 'within' : 'over').' the budget, so there is no outcome to model.';
        } else {
            try {
                $model = $rentals |> glm($formula, family: 'binomial');
                if (! $model['converged']) {
                    [$model, $error] = [null, 'The logistic model did not converge for this selection.'];
                }
            } catch (Throwable $e) {
                $error = 'The logistic model cannot be fitted to this selection: '.$e->getMessage();
            }
        }

        return [
            'whatIf' => $this->whatIf($rentals, $neighborhoods, $low, $high, $model),
            'rentals' => $rentals->nrow(),
            'budget' => $budget,
            'summary' => $summary->toRows(),
            'model' => $model,
            'formula' => $formula,
            'error' => $error,
            'scatter' => $this->scatter($rentals->toRows(), $budget),
            'curves' => $model ? $this->curves($model, $low, $high) : null,
        ];
    }

    /**
     * Predictions for the what-if rental with stats_predict(): its rent from a
     * linear model, with a 95% prediction interval, and its chance of
     * exceeding the budget from the logistic model, with a 95% confidence
     * interval computed on the link scale.
     */
    private function whatIf(DataFrame $rentals, array $neighborhoods, int $low, int $high, ?array $model): ?array
    {
        if (! function_exists('stats_predict')) {
            return null;
        }

        $neighborhood = in_array($this->whatIfNeighborhood, $neighborhoods, true) ? $this->whatIfNeighborhood : $neighborhoods[0];
        $area = max(20, min(200, $this->whatIfArea));
        $bedrooms = max(1, min(4, $this->whatIfBedrooms));
        $newdata = ['neighborhood' => [$neighborhood], 'area_m2' => [$area], 'bedrooms' => [$bedrooms]];

        // Constant columns leave the model, as in the logistic formula above.
        $formula = 'monthly_rent ~ area_m2'
            .($low === $high ? '' : ' + bedrooms')
            .(count($neighborhoods) === 1 ? '' : ' + neighborhood');

        try {
            $rent = stats_predict($rentals |> lm($formula), $newdata, interval: 'prediction', level: 0.95);
        } catch (Throwable $e) {
            return ['error' => 'The rent model cannot be fitted to this selection: '.$e->getMessage()];
        }

        $over = null;
        if ($model) {
            $link = stats_predict($model, $newdata, se_fit: true);
            $logistic = fn (float $eta) => 1 / (1 + exp(-$eta));
            [$eta, $se] = [$link['fit'][0], $link['se_fit'][0]];
            $over = ['fit' => $logistic($eta), 'lower' => $logistic($eta - 1.959964 * $se), 'upper' => $logistic($eta + 1.959964 * $se)];
        }

        return [
            'neighborhood' => $neighborhood,
            'area' => $area,
            'bedrooms' => $bedrooms,
            'extrapolating' => $bedrooms < $low || $bedrooms > $high,
            'formula' => $formula,
            'rent' => ['fit' => $rent['fit'][0], 'lower' => $rent['lower'][0], 'upper' => $rent['upper'][0]],
            'over' => $over,
            'error' => null,
        ];
    }

    private function scatter(array $rows, int $budget): Plot
    {
        return Plot::of(array_map(fn ($r) => array_intersect_key($r, array_flip(['neighborhood', 'area_m2', 'bedrooms', 'monthly_rent'])), $rows))
            ->aes(x: 'area_m2', y: 'monthly_rent', color: 'neighborhood')
            ->point(alpha: 0.5, size: 14)
            ->hline($budget, color: 'gray', linetype: 'dashed')
            ->labs(x: 'Area (m²)', y: 'Monthly rent', color: null)
            ->theme(legendPosition: 'bottom', config: ['legend' => ['columns' => 3]])
            ->tooltip()
            ->size('container', 320);
    }

    /**
     * Predicted probability of exceeding the budget against area, one line per
     * bedroom count, from the fitted logistic model.
     */
    private function curves(array $model, int $low, int $high): Plot
    {
        $b = $model['named'];
        $points = [];
        foreach (range($low, $high) as $bedrooms) {
            for ($area = 30; $area <= 150; $area += 2) {
                $eta = $b['(Intercept)'] + $b['area_m2'] * $area + ($b['bedrooms'] ?? 0.0) * $bedrooms;
                $points[] = ['area_m2' => $area, 'probability' => 1 / (1 + exp(-$eta)), 'bedrooms' => "{$bedrooms} bedroom".($bedrooms > 1 ? 's' : '')];
            }
        }

        return Plot::of($points)
            ->aes(x: 'area_m2', y: 'probability', color: 'bedrooms')
            ->line(linewidth: 2)
            ->scaleY(domain: [0, 1])
            ->labs(x: 'Area (m²)', y: 'P(rent over budget)', color: null)
            ->theme(legendPosition: 'bottom', config: ['legend' => ['columns' => 3]])
            ->tooltip()
            ->size('container', 320);
    }
}
