<?php

namespace App\Livewire;

use Livewire\Component;
use Phibble\DataFrame;
use Phiz\Plot;

use function Phiz\effect_plot;
use function Phplyr\glm;

/**
 * Vignette: binary and count outcomes through phplyr's glm(), which passes an
 * R formula and the phibble frame to php_stats' stats_glm().
 */
class GlmModels extends Component
{
    /**
     * Each model: the data file, formula, family, link, the predictor the
     * chart varies, and labels for the page.
     */
    public const MODELS = [
        'logit' => [
            'label' => 'Logistic regression',
            'file' => 'learning.csv',
            'formula' => 'passed ~ study_hours + practice_score',
            'family' => 'binomial',
            'link' => 'logit',
            'x' => 'study_hours',
            'ratio' => 'Odds ratio',
        ],
        'probit' => [
            'label' => 'Probit regression',
            'file' => 'learning.csv',
            'formula' => 'passed ~ study_hours + practice_score',
            'family' => 'binomial',
            'link' => 'probit',
            'x' => 'study_hours',
            'ratio' => null,
        ],
        'poisson' => [
            'label' => 'Poisson regression',
            'file' => 'clinic.csv',
            'formula' => 'calls ~ visitors + staff',
            'family' => 'poisson',
            'link' => 'log',
            'x' => 'visitors',
            'ratio' => 'Rate ratio',
        ],
    ];

    public string $model = 'logit';

    public bool $interaction = false;

    public function render()
    {
        return view('livewire.glm-models', [
            'extensionLoaded' => function_exists('stats_glm'),
            'spec' => $this->spec(),
            'formula' => $this->formula(),
            'fit' => $this->fit(),
        ]);
    }

    private function spec(): array
    {
        return self::MODELS[$this->model] ?? self::MODELS['logit'];
    }

    /** The model's formula, with the two predictors crossed when requested. */
    public function formula(): string
    {
        $formula = $this->spec()['formula'];

        return $this->interaction ? str_replace(' + ', ' * ', $formula) : $formula;
    }

    private function fit(): ?array
    {
        if (! function_exists('stats_glm')) {
            return null;
        }

        $spec = $this->spec();
        $data = DataFrame::fromCsv(database_path("data/{$spec['file']}"));
        $result = $data |> glm($this->formula(), family: $spec['family'], link: $spec['link']);

        return [
            'result' => $result,
            'rows' => $data->nrow(),
            // McFadden's pseudo R-squared, 1 - D / D0.
            'pseudo_r2' => 1 - $result['deviance'] / $result['null_deviance'],
            'chart' => $this->chart($result, $data, $spec),
        ];
    }

    private function chart(array $result, DataFrame $data, array $spec): Plot
    {
        $label = $spec['family'] === 'poisson' ? 'Expected calls' : 'Probability of passing';

        return effect_plot($result, $data, $spec['x'])
            ->labs(x: str_replace('_', ' ', ucfirst($spec['x'])), y: $label)
            ->tooltip()
            ->size('container', 300);
    }
}
