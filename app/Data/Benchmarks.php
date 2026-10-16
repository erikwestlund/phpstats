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
