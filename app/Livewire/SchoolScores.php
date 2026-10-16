<?php

namespace App\Livewire;

use App\Models\StudentScore;
use Livewire\Component;
use Phibble\Frame;
use Phit\Plot;

use function Phplyr\lm;

/**
 * Vignette: regress test score on study hours and prior score.
 *
 * phibble reads the table and phplyr's lm() fits it with php_stats' stats_lm().
 */
class SchoolScores extends Component
{
    public const PREDICTORS = [
        'study_hours' => 'Study hours',
        'prior_score' => 'Prior score',
    ];

    /** @var list<string> */
    public array $predictors = ['study_hours', 'prior_score'];

    public function render()
    {
        return view('livewire.school-scores', [
            'extensionLoaded' => function_exists('stats_lm'),
            'fit' => $this->fit(),
        ]);
    }

    private function fit(): ?array
    {
        $predictors = array_values(array_intersect(array_keys(self::PREDICTORS), $this->predictors));

        if (! function_exists('stats_lm') || $predictors === []) {
            return null;
        }

        $scores = Frame::fromQuery(StudentScore::query()->select([...$predictors, 'score']));

        $result = $scores |> lm('score ~ '.implode(' + ', $predictors));

        return [
            'terms' => ['(Intercept)', ...array_map(fn ($p) => self::PREDICTORS[$p], $predictors)],
            'result' => $result,
            'chart' => $this->chart($scores->toVector('score'), $result),
        ];
    }

    /**
     * Observed score against fitted score, with the y = x line.
     */
    private function chart(array $observed, array $result): Plot
    {
        $points = array_map(
            fn (int $row, float $fitted) => ['fitted' => $fitted, 'observed' => $observed[$row]],
            $result['retained_rows'],
            $result['fitted'],
        );

        $values = [...$result['fitted'], ...array_column($points, 'observed')];
        $limits = [floor(min($values)), ceil(max($values))];

        return Plot::of($points)
            ->aes(x: 'fitted', y: 'observed')
            ->point(alpha: 0.6)
            ->abline(0, 1, color: 'gray', linetype: 'dashed')
            ->coordCartesian(xlim: $limits, ylim: $limits)
            ->labs(x: 'Fitted score', y: 'Observed score')
            ->tooltip()
            ->size('container', 320);
    }
}
