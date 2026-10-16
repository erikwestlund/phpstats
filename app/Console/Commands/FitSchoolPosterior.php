<?php

namespace App\Console\Commands;

use App\Data\SchoolPosterior;
use App\Models\StudentScore;
use CmdStan\StanData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Phibble\Frame;

use function CmdStan\cmdstan_model;
use function CmdStan\cmdstan_version;

/**
 * Fit the Bayesian school-intercepts model with CmdStan and store a thinned
 * set of draws for the school effects vignette. Pages read only the stored
 * file, so CmdStan is needed only to regenerate it.
 */
class FitSchoolPosterior extends Command
{
    protected $signature = 'stan:fit-school-posterior';

    protected $description = 'Fit the school intercepts Stan model and store its draws for the Bayesian vignette';

    public function handle(): int
    {
        $workdir = storage_path('app/stan');
        File::ensureDirectoryExists($workdir);
        File::copy(resource_path('stan/school_intercepts.stan'), "{$workdir}/school_intercepts.stan");

        $frame = Frame::fromQuery(StudentScore::query()->orderBy('id'));
        $data = StanData::fromFormula(SchoolPosterior::FORMULA, $frame);

        $this->info('Compiling and sampling with CmdStan '.cmdstan_version().'...');
        $model = cmdstan_model("{$workdir}/school_intercepts.stan");
        $fit = $model->sample(
            data: $data,
            seed: SchoolPosterior::SEED,
            chains: 4,
            parallel_chains: 4,
            iter_warmup: 1000,
            iter_sampling: 1000,
            thin: 4,
            show_messages: false,
        );

        $variables = ['beta', 'sigma', 'tau', 'school_intercept'];
        $draws = $fit->draws_columns($variables);
        $summary = $fit->summary_columns($variables, measures: ['rhat', 'ess_bulk', 'ess_tail']);
        $diagnostics = $fit->diagnostic_summary();

        $round = fn (array $values) => array_map(fn ($v) => is_float($v) ? round($v, 6) : $v, $values);

        File::put(SchoolPosterior::path(), json_encode([
            'meta' => [
                'formula' => SchoolPosterior::FORMULA,
                'stan_file' => 'resources/stan/school_intercepts.stan',
                'cmdstan' => cmdstan_version(),
                'seed' => SchoolPosterior::SEED,
                'chains' => 4,
                'iter_warmup' => 1000,
                'iter_sampling' => 1000,
                'thin' => 4,
                'columns' => $data->columns,
                'schools' => $data->levels['school_id'] ?? $data->levels['school'] ?? null,
                'divergent' => (int) array_sum($diagnostics['num_divergent']),
                'generated_at' => now()->toIso8601String(),
            ],
            'convergence' => array_map($round, $summary),
            'draws' => array_map($round, $draws),
        ], JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION)."\n");

        $this->info('Wrote '.count($draws['.draw']).' draws to '.SchoolPosterior::path());

        return self::SUCCESS;
    }
}
