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

    /** The summary shown by the landing page's package explorer, for resources/examples/packages/cmdstanphp.php. */
    public static function explorerSummary(): \Phibble\DataFrame
    {
        return self::summary(['beta[1]', 'beta[2]', 'sigma', 'tau'], ['mean', 'sd', 'quantile2', 'rhat', 'ess_bulk', 'ess_tail']);
    }

    /**
     * The summary that $fit->summary_frame($variables, measures: $measures)
     * gives for the stored fit, computed by CmdStanPHP's own
     * Summary::summarise() from the stored draws. The fit was thinned by 4,
     * so the stored draws are all of its draws.
     *
     * @param  list<string>  $variables  names in the draws, such as beta[1]
     * @param  list<string>  $measures
     */
    public static function summary(array $variables, array $measures): \Phibble\DataFrame
    {
        $draws = self::load()['draws'];
        $chains = [];
        foreach ($draws['.chain'] as $i => $chain) {
            foreach ($variables as $variable) {
                $chains[$chain][$variable][] = $draws[$variable][$i];
            }
        }

        return \Phibble\DataFrame::fromRows(\CmdStan\Summary::summarise($chains, $measures));
    }
}
