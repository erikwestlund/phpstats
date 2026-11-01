<?php

namespace App\Content;

use Illuminate\Support\Str;

/**
 * Shapes the benchmark and verification files from each package's site/
 * folder for display.
 */
class Results
{
    /**
     * Benchmark rows pivoted to one row per case and one column per
     * implementation, in first-seen order.
     *
     * Notes on rows, such as a value below the timer resolution, are numbered
     * in the order they first appear.
     *
     * @return array{implementations: list<string>, cases: list<array{case: string, values: array<string, ?float>, marks: array<string, int>}>, notes: list<string>}
     */
    public static function pivot(array $benchmark): array
    {
        $implementations = [];
        $cases = [];
        $notes = [];
        foreach ($benchmark['rows'] as $row) {
            $implementations[$row['implementation']] = true;
            $cases[$row['case']] ??= ['case' => $row['case'], 'values' => [], 'marks' => []];
            $cases[$row['case']]['values'][$row['implementation']] = $row['value'];
            if (! empty($row['note'])) {
                $index = array_search($row['note'], $notes, true);
                if ($index === false) {
                    $notes[] = $row['note'];
                    $index = count($notes) - 1;
                }
                $cases[$row['case']]['marks'][$row['implementation']] = $index + 1;
            }
        }

        return ['implementations' => array_keys($implementations), 'cases' => array_values($cases), 'notes' => $notes];
    }

    /** A Vega-Lite dot chart of a benchmark, with a log scale when the values span more than two decimal orders. */
    public static function benchmarkChart(array $benchmark): array
    {
        $values = array_values(array_filter(
            array_map(fn (array $row) => ['case' => $row['case'], 'implementation' => $row['implementation'], 'value' => $row['value']], $benchmark['rows']),
            fn (array $row) => is_numeric($row['value']) && $row['value'] > 0,
        ));
        $numbers = array_column($values, 'value');
        $log = $numbers !== [] && max($numbers) / min($numbers) > 100;
        $cases = array_values(array_unique(array_column($benchmark['rows'], 'case')));
        $unit = $benchmark['unit'] ?? '';

        return [
            '$schema' => 'https://vega.github.io/schema/vega-lite/v6.json',
            'data' => ['values' => $values],
            'width' => 'container',
            'height' => max(80, 26 * count($cases)),
            'mark' => ['type' => 'point', 'filled' => true, 'size' => 80, 'opacity' => 0.9, 'tooltip' => true],
            'encoding' => [
                'x' => [
                    'field' => 'value', 'type' => 'quantitative',
                    'title' => trim(ucfirst($benchmark['measure'] ?? 'value')." ({$unit}".($log ? ', log scale' : '').')'),
                    'scale' => $log ? ['type' => 'log'] : ['zero' => true],
                ],
                'y' => ['field' => 'case', 'type' => 'nominal', 'title' => null, 'sort' => $cases],
                'color' => ['field' => 'implementation', 'type' => 'nominal', 'title' => null],
            ],
            'config' => ['legend' => ['orient' => 'bottom']],
        ];
    }

    /**
     * Verification cases grouped by name, with the worst error and pass
     * status of each group.
     *
     * @return list<array{name: string, cases: list<array>, passed: bool, worst_abs: ?float, worst_rel: ?float}>
     */
    public static function groups(array $verification): array
    {
        $groups = [];
        foreach ($verification['cases'] as $case) {
            $name = $case['name'] ?? '';
            $groups[$name] ??= ['name' => $name, 'cases' => [], 'passed' => true, 'worst_abs' => null, 'worst_rel' => null];
            $groups[$name]['cases'][] = $case;
            $groups[$name]['passed'] = $groups[$name]['passed'] && ($case['passed'] ?? false);
            foreach (['abs', 'rel'] as $kind) {
                $value = $case["max_{$kind}_error"] ?? null;
                if (is_numeric($value)) {
                    $groups[$name]["worst_{$kind}"] = max($groups[$name]["worst_{$kind}"] ?? 0, $value);
                }
            }
        }

        return array_values($groups);
    }

    /** Columns present in a verification file, besides name and passed. */
    public static function verificationColumns(array $verification): array
    {
        $columns = ['n_compared' => false, 'max_abs_error' => false, 'max_rel_error' => false];
        $peers = [];
        foreach ($verification['cases'] as $case) {
            foreach (array_keys($columns) as $column) {
                $columns[$column] = $columns[$column] || array_key_exists($column, $case);
            }
            foreach (array_keys($case['peers'] ?? []) as $peer) {
                $peers[$peer] = true;
            }
        }

        return ['columns' => array_keys(array_filter($columns)), 'peers' => array_keys($peers)];
    }

    /**
     * The machine a benchmark ran on, in one line. A CPU listed in
     * config/phpstats.php cpus is named by its cores, as in "2 AMD Zen 5
     * cores"; an older record with only a platform string gives that.
     */
    public static function machine(array $environment): string
    {
        $cpu = $environment['cpu'] ?? null;
        $cores = $environment['cores'] ?? null;
        $memory = isset($environment['memory_gb']) ? "{$environment['memory_gb']} GB RAM" : null;
        $os = $environment['os'] ?? $environment['platform'] ?? null;
        $family = $cpu === null ? null : (config('phpstats.cpus')[$cpu] ?? null);

        if ($family !== null && $cores !== null) {
            return implode(', ', array_filter(["{$cores} {$family} cores", $memory, $os === null ? null : strtok($os, ',')]));
        }

        $detail = implode(', ', array_filter([$cores === null ? null : "{$cores} cores", $memory === null ? null : "{$environment['memory_gb']} GB"]));

        return implode(', ', array_filter([$cpu === null ? null : $cpu.($detail === '' ? '' : " ({$detail})"), $os]));
    }

    /**
     * The facts a benchmark record states about its own method, as label =>
     * text, in a fixed order and only where the record gives them: the
     * workload or dataset, repetitions and statistic, threads, software
     * versions, other settings, and when and at which commit it ran. The
     * record's description and its timing method are shown on their own.
     *
     * @return array<string, string>
     */
    public static function methods(array $benchmark): array
    {
        $env = $benchmark['environment'] ?? [];
        $known = ['cpu', 'cores', 'memory_gb', 'os', 'platform', 'php', 'versions', 'threads', 'repeats', 'date', 'commit', 'code_commit'];

        $dataset = $benchmark['dataset'] ?? null;
        if (is_array($dataset)) {
            $dataset = implode(', ', array_filter([
                isset($dataset['rows']) ? number_format($dataset['rows']).' rows' : null,
                isset($dataset['bytes']) ? number_format($dataset['bytes'] / 1e9, 2).' GB' : null,
                isset($dataset['missing_amounts']) ? "{$dataset['missing_amounts']} of amounts missing" : null,
            ]));
        }

        $repeats = $env['repeats'] ?? null;
        $statistic = $benchmark['statistic'] ?? null;
        $runs = match (true) {
            $repeats === null => null,
            $statistic !== null => ucfirst($statistic).' of '.$repeats.' '.Str::plural('run', $repeats),
            default => $repeats.' '.Str::plural('run', $repeats),
        };

        $threads = $env['threads'] ?? null;
        if (is_array($threads)) {
            $threads = implode(', ', array_map(fn ($name, $n) => "{$name} {$n}", array_keys($threads), $threads));
        }

        $versions = array_filter([
            'PHP' => $env['php'] ?? null,
            ...($env['versions'] ?? []),
            // Any other entry that reads as a version, a commit, or "R version 4.6.1".
            ...array_filter(
                array_diff_key($env, array_flip($known)),
                fn ($value) => is_string($value) && preg_match('/^v?\d+(\.\d+)+|^[0-9a-f]{7,40}$|\bversion \d/', $value) === 1,
            ),
        ]);
        $settings = array_filter(array_diff_key($env, array_flip($known), $versions), fn ($value) => is_scalar($value));
        $versions = array_map(fn (string $version) => match (true) {
            preg_match('/^[0-9a-f]{8,40}$/', $version) === 1 => substr($version, 0, 7),
            default => preg_replace('/^.*?\bversion\s+/', '', $version),
        }, $versions);

        $recorded = isset($env['date']) ? Str::before($env['date'], 'T') : null;
        $commit = $env['commit'] ?? $benchmark['code_commit'] ?? null;

        return array_filter([
            'Workload' => $benchmark['workload'] ?? null,
            'Dataset' => $dataset ?: null,
            'Runs' => $runs,
            'Threads' => $threads,
            'Software' => $versions === [] ? null : implode(', ', array_map(fn ($name, $version) => (strlen($name) === 1 ? strtoupper($name) : $name)." {$version}", array_keys($versions), $versions)),
            ...array_combine(array_map(fn ($key) => Str::ucfirst(str_replace('_', ' ', $key)), array_keys($settings)), array_map(fn ($value) => (string) $value, $settings)),
            'Memory' => isset($benchmark['memory_measure']) ? ucfirst($benchmark['memory_measure']) : null,
            'Recorded' => implode(', ', array_filter([$recorded, $commit === null ? null : 'commit '.substr($commit, 0, 7)])) ?: null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public static function error(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            ! is_numeric($value) => (string) $value,
            (float) $value === 0.0 => '0',
            default => sprintf('%.1e', $value),
        };
    }

    public static function number(mixed $value, string $unit = ''): string
    {
        if (! is_numeric($value)) {
            return '';
        }
        $value = (float) $value;
        $digits = match (true) {
            $value >= 100 => 0,
            $value >= 10 => 1,
            $value >= 1 => 2,
            default => 3,
        };

        return number_format($value, $digits).($unit === '' ? '' : " {$unit}");
    }
}
