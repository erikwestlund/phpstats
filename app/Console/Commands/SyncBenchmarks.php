<?php

namespace App\Console\Commands;

use App\Data\Benchmarks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Copy the published php-stats benchmark results into the site, keeping only
 * what the benchmarks page shows: machine, date, versions, timings, and
 * agreement with R.
 */
class SyncBenchmarks extends Command
{
    protected $signature = 'benchmarks:sync {--ref=main : php-stats git ref to read docs/benchmarks from}';

    protected $description = 'Fetch the php-stats benchmark results for the benchmarks page';

    public function handle(): int
    {
        $ref = $this->option('ref');
        $models = [];

        foreach (Benchmarks::MODELS as $key => $label) {
            $url = "https://raw.githubusercontent.com/erikwestlund/php-stats/{$ref}/docs/benchmarks/{$key}-results.json";
            $report = Http::timeout(60)->get($url)->throw()->json();

            $models[$key] = [
                'label' => $label,
                'recorded_at' => $report['recorded_at'],
                'cpu' => $report['cpu'],
                'platform' => $report['platform'],
                'repeats' => $report['repeats'] ?? null,
                'source' => "https://erikwestlund.github.io/php-stats/benchmarks/{$key}.html",
                'cases' => array_map(fn (array $case) => [
                    'n' => $case['n'],
                    'p' => $case['p'],
                    'groups' => $case['n_groups'] ?? null,
                    'agreement_passed' => $case['agreement_passed'],
                    'implementations' => array_map(fn (array $impl) => [
                        'version' => is_array($impl['version']) ? json_encode($impl['version']) : (string) $impl['version'],
                        'median_fit_seconds' => $impl['median_fit_seconds'],
                        'min_fit_seconds' => $impl['min_fit_seconds'],
                    ], $case['implementations']),
                ], $report['cases']),
            ];
            $this->line("{$key}: ".count($report['cases']).' cases, recorded '.$report['recorded_at']);
        }

        File::put(Benchmarks::path(), json_encode([
            'source' => "erikwestlund/php-stats@{$ref}",
            'synced_at' => now()->toIso8601String(),
            'models' => $models,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)."\n");

        $this->info('Wrote '.Benchmarks::path());

        return self::SUCCESS;
    }
}
