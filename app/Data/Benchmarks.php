<?php

namespace App\Data;

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
}
