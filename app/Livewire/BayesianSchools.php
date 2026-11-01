<?php

namespace App\Livewire;

use App\Data\SchoolPosterior;
use App\Models\StudentScore;
use Livewire\Component;
use Phibble\DataFrame;
use Phiz\Plot;

use function Phiz\aes;
use function Phit\fit_lmer;
use function Phit\stat_mean;
use function Phit\stat_quantile_array;
use function Phit\stat_sd;

/**
 * Vignette: the school random-intercept model fitted with Stan through
 * CmdStanPHP, compared with fit_lmer(). The draws come from a saved seeded
 * fit, so the page never runs CmdStan.
 */
class BayesianSchools extends Component
{
    public const LEVELS = [50 => '50%', 80 => '80%', 95 => '95%'];

    public int $level = 95;

    public function render()
    {
        $ready = function_exists('Phit\\fit_lmer') && SchoolPosterior::exists();

        return view('livewire.bayesian-schools', [
            'ready' => $ready,
            'fit' => $ready ? $this->fit() : null,
        ]);
    }

    private function fit(): array
    {
        $percent = array_key_exists($this->level, self::LEVELS) ? $this->level : 95;
        $probs = [(1 - $percent / 100) / 2, 0.5, (1 + $percent / 100) / 2];

        $posterior = SchoolPosterior::load();
        $draws = $posterior['draws'];
        $schools = $posterior['meta']['schools'];

        $lmer = fit_lmer(SchoolPosterior::FORMULA, DataFrame::fromQuery(StudentScore::query()->orderBy('id')));
        $random = $lmer['random'][0];
        $lmerIntercepts = [];
        foreach ($random['group_labels'] as $i => $school) {
            $lmerIntercepts[$school] = $lmer['named']['(Intercept)'] + $random['effects'][$i][0];
        }

        $summarise = function (array $values) use ($probs) {
            [$lower, $median, $upper] = stat_quantile_array($values, $probs);

            return ['mean' => stat_mean($values), 'sd' => stat_sd($values), 'lower' => $lower, 'median' => $median, 'upper' => $upper];
        };

        $parameters = [
            ['name' => '(Intercept)', 'draws' => 'beta[1]', 'lmer' => $lmer['named']['(Intercept)']],
            ['name' => 'study_hours', 'draws' => 'beta[2]', 'lmer' => $lmer['named']['study_hours']],
            ['name' => 'Residual SD (σ)', 'draws' => 'sigma', 'lmer' => $lmer['sigma']],
            ['name' => 'School SD (τ)', 'draws' => 'tau', 'lmer' => sqrt($random['covariance'][0][0])],
        ];
        foreach ($parameters as &$parameter) {
            $parameter += $summarise($draws[$parameter['draws']]);
        }
        unset($parameter);

        $intercepts = [];
        foreach ($schools as $j => $school) {
            $intercepts[] = ['school' => $school, 'lmer' => $lmerIntercepts[$school]] + $summarise($draws['school_intercept['.($j + 1).']']);
        }

        return [
            'meta' => $posterior['meta'],
            'convergence' => $posterior['convergence'],
            'parameters' => $parameters,
            'intercepts' => $intercepts,
            'level_label' => self::LEVELS[$percent],
            'intercept_chart' => $this->interceptChart($intercepts, self::LEVELS[$percent]),
            'tau_chart' => $this->tauChart($draws['tau'], $parameters[3]['lmer']),
        ];
    }

    private function interceptChart(array $intercepts, string $label): Plot
    {
        return Plot::of($intercepts)
            ->aes(x: 'median', y: 'school', xmin: 'lower', xmax: 'upper')
            ->pointrange(color: '#4c78a8', linewidth: 2)
            ->point(aes(x: 'lmer'), shape: 'diamond', color: '#e45756', size: 60)
            ->labs(x: "School intercept: posterior median and {$label} interval", y: null)
            ->tooltip()
            ->size('container', 360);
    }

    private function tauChart(array $tau, float $lmerTau): Plot
    {
        return Plot::of(array_map(fn ($t) => ['tau' => $t], $tau))
            ->aes(x: 'tau')
            ->histogram(bins: 40, fill: '#4c78a8', alpha: 0.8)
            ->vline($lmerTau, color: '#e45756', linewidth: 2)
            ->labs(x: 'School SD (τ), posterior draws', y: 'Draws')
            ->size('container', 220);
    }
}
