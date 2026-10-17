<?php

namespace App\Data;

use Phiz\Plot;

/**
 * Benchmark results copied from the phit docs by `php artisan benchmarks:sync`.
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
            'text' => 'phit c1bf8a2 added a QR-based solver, which makes the wider designs faster, and a compensated (Neumaier) likelihood sum, which stops the line-search failures the earlier build had on most random million-row datasets. Run in the same harness, these changes took the million-row, three-coefficient fit from 418 ms (18b430b) to 394 ms, 6% faster. The October 11 recording of 288 ms could not be reproduced: the same commit (d073c15) measures 424 ms in the same harness today, so the comparison here uses runs from the same session.',
            'url' => 'https://github.com/erikwestlund/phit/commit/c1bf8a2',
            'link' => 'phit c1bf8a2',
        ],
        'probit' => [
            'text' => 'phit c1bf8a2 added a QR-based solver, which makes the wider designs faster, and a compensated (Neumaier) likelihood sum, which avoids line-search failures on large datasets. Run in the same harness, these changes took the million-row, three-coefficient fit from 1,177 ms (18b430b) to 1,285 ms, 9% slower. The October 11 recording of 580 ms could not be reproduced: the same commit (d073c15) measures 1,188 ms in the same harness today, so the comparison here uses runs from the same session.',
            'url' => 'https://github.com/erikwestlund/phit/commit/c1bf8a2',
            'link' => 'phit c1bf8a2',
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

    public const BACKENDS = [
        'DataFrame' => 'phibble DataFrame (in memory)',
        'PolarsTable' => 'phpolars PolarsTable',
        'SqlTable(SQLite)' => 'SQLite table',
    ];

    /**
     * Backend results indexed as [rows_in][pipeline][backend].
     */
    public static function backendGrid(array $backends): array
    {
        $grid = [];
        foreach ($backends['results'] as $result) {
            $grid[$result['rows_in']][$result['pipeline']][$result['backend']] = $result;
        }
        ksort($grid);

        return $grid;
    }

    public static function backendChart(array $backends, int $rows): Plot
    {
        $points = [];
        foreach ($backends['results'] as $result) {
            if ($result['rows_in'] === $rows && $result['seconds'] !== null) {
                $points[] = [
                    'pipeline' => "Pipeline {$result['pipeline']}",
                    'backend' => self::BACKENDS[$result['backend']] ?? $result['backend'],
                    'seconds' => $result['seconds'],
                ];
            }
        }

        return Plot::of($points)
            ->aes(x: 'seconds', y: 'pipeline', color: 'backend')
            ->point(size: 90, alpha: 0.9)
            ->scaleX(type: 'log')
            ->labs(x: 'Time for '.number_format($rows).' rows (seconds, log scale)', y: null, color: null)
            ->theme(legendPosition: 'bottom')
            ->tooltip()
            ->size('container', 160);
    }

    public const POLYFILL_IMPLEMENTATIONS = [
        'extension' => 'phit extension',
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
