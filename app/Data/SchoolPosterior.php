<?php

namespace App\Data;

/**
 * Stored posterior draws for score ~ study_hours + (1 | school), written by
 * `php artisan stan:fit-school-posterior`.
 */
final class SchoolPosterior
{
    public const FORMULA = 'score ~ study_hours + (1 | school)';

    public const SEED = 20261016;

    public static function path(): string
    {
        return database_path('data/stan/school-intercepts.json');
    }

    public static function exists(): bool
    {
        return is_file(self::path());
    }

    /**
     * @return array{meta: array, convergence: array, draws: array<string, list<float|int>>}
     */
    public static function load(): array
    {
        return json_decode(file_get_contents(self::path()), true, flags: JSON_THROW_ON_ERROR);
    }
}
