<?php

namespace App\Data;

/**
 * R's and Python's results that the landing page's verification section
 * compares phit with, copied from phit's verification records by
 * `php artisan verification:references /path/to/phit`.
 */
class VerificationReferences
{
    public static function path(): string
    {
        return database_path('data/verification/references.json');
    }

    /** @return array<string, mixed> */
    public static function load(): array
    {
        return json_decode(file_get_contents(self::path()), true, flags: JSON_THROW_ON_ERROR);
    }
}
