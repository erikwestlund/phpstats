<?php

namespace App\Livewire;

use App\Models\StudentScore;
use Livewire\Component;
use Phibble\DataFrame;

use function Phiz\random_effects_plot;
use function Phplyr\lmer;

/**
 * Vignette: school random effects with intervals from their conditional
 * variances, which Phit\fit_lmer() reports as lme4's ranef(condVar = TRUE) does.
 */
class RandomEffects extends Component
{
    public const STRUCTURES = [
        'intercept' => ['label' => 'Random intercepts', 'term' => '(1 | school)'],
        'correlated' => ['label' => 'Correlated random slopes', 'term' => '(1 + study_hours | school)'],
        'independent' => ['label' => 'Independent random slopes', 'term' => '(1 + study_hours || school)'],
    ];

    public const LEVELS = [50, 80, 90, 95, 99];

    public string $structure = 'intercept';

    public int $level = 95;

    public function render()
    {
        return view('livewire.random-effects', [
            'extensionLoaded' => function_exists('Phit\\fit_lmer'),
            'formula' => $this->formula(),
            'fit' => $this->fit(),
        ]);
    }

    public function formula(): string
    {
        $structure = self::STRUCTURES[$this->structure] ?? self::STRUCTURES['intercept'];

        return "score ~ study_hours + {$structure['term']}";
    }

    private function fit(): ?array
    {
        if (! function_exists('Phit\\fit_lmer')) {
            return null;
        }

        $level = in_array($this->level, self::LEVELS, true) ? $this->level : 95;
        $result = DataFrame::fromQuery(StudentScore::query()) |> lmer($this->formula());
        $term = $result['random'][0];
        $sigma2 = $result['residual_variance'];

        $schools = [];
        foreach ($term['group_labels'] as $g => $school) {
            $row = ['school' => $school, 'students' => $term['group_sizes'][$g]];
            foreach ($term['columns'] as $a => $column) {
                $row['effects'][$column] = [
                    'mode' => $term['effects'][$g][$a],
                    'sd' => sqrt(max(0.0, $term['effects_cond_var'][$g][$a][$a])),
                ];
            }
            if (count($term['columns']) === 1) {
                // For one random intercept, the conditional variance has the
                // closed form tau^2 sigma^2 / (sigma^2 + n tau^2).
                $tau2 = $term['covariance'][0][0];
                $row['closed_form_sd'] = sqrt($tau2 * $sigma2 / ($sigma2 + $row['students'] * $tau2));
            }
            $schools[] = $row;
        }

        // A faceted chart (one panel per effect) keeps phiz's fixed panel width.
        $chart = random_effects_plot($result, level: $level / 100);
        if (count($term['columns']) === 1) {
            $chart = $chart->width('container');
        }

        return [
            'result' => $result,
            'term' => $term,
            'schools' => $schools,
            'level' => $level,
            'chart' => $chart,
        ];
    }
}
