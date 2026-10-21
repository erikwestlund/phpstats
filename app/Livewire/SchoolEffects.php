<?php

namespace App\Livewire;

use App\Models\StudentScore;
use Livewire\Component;
use Phibble\DataFrame;
use Phiz\Plot;

use function Phplyr\lm;
use function Phit\fit_lmer;
use function Phit\predict;

/**
 * Vignette: a random-intercept model for test scores, with the school
 * intercepts compared against a no-pooling fit that gives each school its own
 * fixed intercept.
 */
class SchoolEffects extends Component
{
    public const FORMULA = 'score ~ study_hours + (1 | school)';

    public const NO_POOLING_FORMULA = 'score ~ 0 + school + study_hours';

    public bool $reml = true;

    /** What-if inputs: a school (or 'new' for one outside the data) and study hours. */
    public string $whatIfSchool = 'new';

    public float $whatIfHours = 8.0;

    public function render()
    {
        return view('livewire.school-effects', [
            'extensionLoaded' => function_exists('Phit\\fit_lmer'),
            'fit' => $this->fit(),
        ]);
    }

    private function fit(): ?array
    {
        if (! function_exists('Phit\\fit_lmer')) {
            return null;
        }

        $scores = DataFrame::fromQuery(StudentScore::query());

        $mixed = fit_lmer(self::FORMULA, $scores, reml: $this->reml);
        $noPooling = $scores |> lm(self::NO_POOLING_FORMULA);

        $random = $mixed['random'][0];
        $intercept = $mixed['named']['(Intercept)'];
        $schoolVariance = $random['covariance'][0][0];
        $residualVariance = $mixed['residual_variance'];

        $schools = [];
        foreach ($random['group_labels'] as $i => $school) {
            $students = $random['group_sizes'][$i];
            $schools[] = [
                'school' => $school,
                'students' => $students,
                'no_pooling' => $noPooling['named']["school{$school}"],
                'partial_pooling' => $intercept + $random['effects'][$i][0],
                // Share of the school's mean residual that the random intercept
                // gives up: (sigma^2 / n) / (tau^2 + sigma^2 / n).
                'shrinkage' => ($residualVariance / $students) / ($schoolVariance + $residualVariance / $students),
            ];
        }

        return [
            'mixed' => $mixed,
            'school_sd' => sqrt($schoolVariance),
            'school_variance' => $schoolVariance,
            'icc' => $schoolVariance / ($schoolVariance + $residualVariance),
            'schools' => $schools,
            'chart' => $this->chart($schools, $intercept),
            'what_if' => $this->whatIf($mixed, $random['group_labels']),
        ];
    }

    /**
     * Phit\predict() for one student: the prediction with the school's random
     * intercept (lme4's re.form = NULL) and the population prediction without
     * it (re.form = NA). A new school predicts at the population level.
     */
    private function whatIf(array $mixed, array $schools): ?array
    {
        if (! function_exists('Phit\\predict')) {
            return null;
        }

        $school = in_array($this->whatIfSchool, $schools, true) ? $this->whatIfSchool : 'new';
        $hours = max(0.0, min(20.0, $this->whatIfHours));
        $newdata = ['study_hours' => [$hours], 'school' => [$school === 'new' ? 'a new school' : $school]];

        return [
            'school' => $school,
            'hours' => $hours,
            'conditional' => predict($mixed, $newdata, allowNewLevels: true)['fit'][0],
            'population' => predict($mixed, $newdata, random: false)['fit'][0],
        ];
    }

    /**
     * Each school's intercept without pooling and with partial pooling, joined
     * by a segment, with the overall intercept as a dashed line.
     */
    private function chart(array $schools, float $intercept): Plot
    {
        $points = [];
        foreach ($schools as $s) {
            $points[] = ['school' => $s['school'], 'intercept' => $s['no_pooling'], 'model' => 'No pooling'];
            $points[] = ['school' => $s['school'], 'intercept' => $s['partial_pooling'], 'model' => 'Partial pooling (lmer)'];
        }

        return Plot::of($points)
            ->aes(x: 'intercept', y: 'school', color: 'model')
            ->segment(['x' => 'no_pooling', 'xend' => 'partial_pooling', 'y' => 'school', 'yend' => 'school'], data: $schools, inheritAes: false, color: 'gray')
            ->point(size: 70)
            ->vline($intercept, color: 'gray', linetype: 'dashed')
            ->scaleColor(values: ['No pooling' => '#e45756', 'Partial pooling (lmer)' => '#4c78a8'])
            ->labs(x: 'School intercept (score at 0 study hours)', y: null, color: null)
            ->theme(legendPosition: 'bottom')
            ->tooltip()
            ->size('container', 360);
    }
}
