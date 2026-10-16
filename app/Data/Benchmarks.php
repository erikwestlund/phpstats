<?php

namespace App\Data;

use Phiz\Plot;

/**
 * Benchmark results copied from the php-stats docs by `php artisan benchmarks:sync`.
 */
final class Benchmarks
{
    public const MODELS = [
        'ols' => 'Linear regression (OLS)',
        'logistic' => 'Logistic regression',
        'probit' => 'Probit regression',
        'poisson' => 'Poisson regression',
        'lmm' => 'Linear mixed model',
        'logistic-mixed' => 'Mixed logistic regression',
    ];

    /**
     * Notes shown under a model's results, with an optional link.
     *
     * @var array<string, array{text: string, url: string, link: string}>
     */
    public const NOTES = [
        'logistic' => [
            'text' => 'The million-row, three-coefficient fit is slower than in the October 11 report because php-stats now sums the likelihood with compensated (Neumaier) summation. The earlier build failed its line search on most random million-row logistic datasets. The wider designs are faster because of a QR-based solver.',
            'url' => 'https://github.com/erikwestlund/php-stats/commit/c1bf8a2',
            'link' => 'php-stats c1bf8a2',
        ],
        'probit' => [
            'text' => 'The million-row, three-coefficient fit is slower than in the October 11 report because php-stats now sums the likelihood with compensated (Neumaier) summation, which avoids line-search failures on large datasets. The wider designs are faster because of a QR-based solver.',
            'url' => 'https://github.com/erikwestlund/php-stats/commit/c1bf8a2',
            'link' => 'php-stats c1bf8a2',
        ],
    ];

    public static function path(): string
    {
        return database_path('data/benchmarks.json');
    }

    public static function exists(): bool
    {
        return is_file(self::path());
    }

    public static function load(): array
    {
        return json_decode(file_get_contents(self::path()), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function backends(): array
    {
        return require database_path('data/backend-benchmarks.php');
    }

    public static function backendChart(array $backends): Plot
    {
        $rows = [];
        foreach ($backends['pipelines'] as $pipeline) {
            foreach ($pipeline['seconds'] as $backend => $seconds) {
                $rows[] = ['pipeline' => "Pipeline {$pipeline['key']}", 'backend' => $backends['backends'][$backend], 'seconds' => $seconds];
            }
        }

        return Plot::of($rows)
            ->aes(x: 'seconds', y: 'pipeline', color: 'backend')
            ->point(size: 90, alpha: 0.9)
            ->scaleX(type: 'log')
            ->labs(x: 'Time for 1,000,000 rows (seconds, log scale)', y: null, color: null)
            ->theme(legendPosition: 'bottom')
            ->tooltip()
            ->size('container', 160);
    }

    public const POLYFILL_IMPLEMENTATIONS = [
        'extension' => 'php-stats extension',
        'polyfill' => 'php-stats-polyfill',
        'plain_php_normal_equations' => 'Plain PHP, normal equations (coefficients only)',
    ];

    public static function polyfillCaseLabel(array $case): string
    {
        return ($case['model'] === 'ols' ? 'OLS' : ucfirst($case['model'])).', '.number_format($case['n']).' rows, '.$case['p'].' predictors';
    }

    public static function polyfillChart(array $polyfill): Plot
    {
        $rows = [];
        foreach ($polyfill['cases'] as $case) {
            foreach ($case['implementations'] as $name => $impl) {
                $rows[] = [
                    'case' => self::polyfillCaseLabel($case),
                    'implementation' => self::POLYFILL_IMPLEMENTATIONS[$name] ?? $name,
                    'milliseconds' => 1000 * $impl['median_seconds'],
                ];
            }
        }

        return Plot::of($rows)
            ->aes(x: 'milliseconds', y: 'case', color: 'implementation')
            ->point(size: 90, alpha: 0.9)
            ->scaleX(type: 'log')
            ->scaleY(domain: array_map(self::polyfillCaseLabel(...), $polyfill['cases']))
            ->labs(x: 'Median time (milliseconds, log scale)', y: null, color: null)
            ->theme(legendPosition: 'bottom', config: ['legend' => ['columns' => 2, 'labelLimit' => 400]])
            ->tooltip()
            ->size('container', 200);
    }
}
