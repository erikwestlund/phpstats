<?php

namespace App\Console\Commands;

use App\Data\VerificationReferences;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Copy the reference values the landing page's verification section compares
 * phit with, from a phit checkout, into database/data/verification. They are
 * R's and Python's results as phit's verification saved them: lme4 and
 * statsmodels for the simulated schools mixed model, glm() and statsmodels for
 * the simulated learning logistic regression, and R and SciPy for four
 * distribution values. The page computes phit's own values from the code it
 * shows, so only the references are stored. Run it again when phit refreshes
 * those records.
 */
class CaptureVerificationReferences extends Command
{
    protected $signature = 'verification:references {checkout : Path to a phit checkout}';

    protected $description = "Copy the R and Python reference values for the landing page's verification section from a phit checkout";

    /** The distribution values shown: [distribution, params, operation, input]. */
    private const POINTS = [
        ['normal', ['mean' => 0, 'sd' => 1], 'cdf', -8],
        ['normal', ['mean' => 0, 'sd' => 1], 'quantile', 0.975],
        ['gamma', ['shape' => 2, 'scale' => 3], 'cdf', 12],
        ['gamma', ['shape' => 2, 'scale' => 3], 'quantile', 0.999],
    ];

    public function handle(): int
    {
        $root = rtrim((string) $this->argument('checkout'), '/');
        $read = function (string $path) use ($root): array {
            $file = "{$root}/{$path}";
            if (! is_file($file)) {
                throw new RuntimeException("{$file} is missing.");
            }

            return json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        };
        $case = fn (array $results, string $name) => collect($results)->firstWhere('name', $name)
            ?? throw new RuntimeException("No {$name} case.");

        $mixed = $case($read('docs/verifications/lmm-results.json'), 'simulated_schools');
        $logistic = $case($read('docs/verifications/logistic-results.json'), 'simulated_learning');
        $model = fn (array $fit) => array_intersect_key($fit, array_flip([
            'coefficients', 'std_errors', 'residual_variance', 'random_intercept_variance', 'log_likelihood',
        ]));

        $fixtures = [
            'R' => $read('tests/fixtures/distributions-r.json'),
            'SciPy' => $read('tests/fixtures/distributions-scipy.json'),
        ];
        $points = [];
        foreach (self::POINTS as [$distribution, $params, $operation, $input]) {
            $values = [];
            foreach ($fixtures as $library => $fixture) {
                $entry = collect($fixture['cases'])->first(fn ($c) => $c['distribution'] === $distribution && $c['params'] == $params && $c['operation'] === $operation)
                    ?? throw new RuntimeException("No {$library} case for {$distribution} {$operation}.");
                $at = array_search($input, $entry['input'], false);
                if ($at === false) {
                    throw new RuntimeException("{$library} has no {$distribution} {$operation} at {$input}.");
                }
                $values[$library] = (float) $entry['expected'][$at];
            }
            $points[] = ['distribution' => $distribution, 'params' => $params, 'operation' => $operation, 'input' => $input, 'values' => $values];
        }

        $commit = trim((string) shell_exec('git -C '.escapeshellarg($root).' rev-parse HEAD'));
        $data = [
            'source' => ['repository' => 'phpstats/phit', 'commit' => $commit],
            'mixed' => [
                'versions' => $mixed['versions'],
                'R' => $model($mixed['R']),
                'statsmodels' => $model($mixed['Python']),
            ],
            'logistic' => [
                'versions' => $logistic['versions'],
                'R' => $model($logistic['R']),
                'statsmodels' => $model($logistic['statsmodels']),
            ],
            'distributions' => [
                'versions' => ['R' => $fixtures['R']['reference'] ?? null, 'SciPy' => $fixtures['SciPy']['reference'] ?? null],
                'points' => $points,
            ],
        ];

        @mkdir(dirname(VerificationReferences::path()), 0755, true);
        file_put_contents(VerificationReferences::path(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)."\n");
        $this->components->info('Wrote '.VerificationReferences::path()." from phit {$commit}.");

        return self::SUCCESS;
    }
}
