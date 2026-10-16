<?php

namespace App\Livewire;

use App\Models\StudentScore;
use Livewire\Component;
use Phibble\Frame;

/**
 * Vignette: regress test score on study hours and prior score.
 *
 * phibble reads the table and the fit calls stats_ols(). Once php_stats
 * provides stats_lm(), this becomes Frame::fromQuery(...) |> lm(...).
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
            'extensionLoaded' => extension_loaded('php_stats'),
            'fit' => $this->fit(),
        ]);
    }

    private function fit(): ?array
    {
        $predictors = array_values(array_intersect(array_keys(self::PREDICTORS), $this->predictors));

        if (! extension_loaded('php_stats') || $predictors === []) {
            return null;
        }

        $scores = Frame::fromQuery(StudentScore::query()->select([...$predictors, 'score']));

        $result = stats_ols(
            x: $scores->toMatrix($predictors),
            y: $scores->toVector('score'),
        );

        return [
            'terms' => ['(Intercept)', ...array_map(fn ($p) => self::PREDICTORS[$p], $predictors)],
            'result' => $result,
            'chart' => $this->chart($scores->toVector('score'), $result),
        ];
    }

    /**
     * Vega-Lite spec: observed score against fitted score, with the y = x line.
     */
    private function chart(array $observed, array $result): array
    {
        $points = array_map(
            fn (int $row, float $fitted) => ['fitted' => $fitted, 'observed' => $observed[$row]],
            $result['retained_rows'],
            $result['fitted'],
        );

        $values = [...$result['fitted'], ...array_column($points, 'observed')];
        $domain = [floor(min($values)), ceil(max($values))];

        return [
            '$schema' => 'https://vega.github.io/schema/vega-lite/v6.json',
            'width' => 'container',
            'height' => 320,
            'layer' => [
                [
                    'data' => ['values' => $points],
                    'mark' => ['type' => 'point', 'filled' => true, 'opacity' => 0.6, 'tooltip' => true],
                    'encoding' => [
                        'x' => ['field' => 'fitted', 'type' => 'quantitative', 'title' => 'Fitted score', 'scale' => ['domain' => $domain]],
                        'y' => ['field' => 'observed', 'type' => 'quantitative', 'title' => 'Observed score', 'scale' => ['domain' => $domain]],
                    ],
                ],
                [
                    'data' => ['values' => [['v' => $domain[0]], ['v' => $domain[1]]]],
                    'mark' => ['type' => 'line', 'strokeDash' => [4, 4], 'color' => 'gray'],
                    'encoding' => [
                        'x' => ['field' => 'v', 'type' => 'quantitative'],
                        'y' => ['field' => 'v', 'type' => 'quantitative'],
                    ],
                ],
            ],
        ];
    }
}
