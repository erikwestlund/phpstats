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
    public static function groups(array $verification, string $key = 'name'): array
    {
        $groups = [];
        foreach ($verification['cases'] as $case) {
            $name = (string) ($case[$key] ?? $case['name'] ?? '');
            $groups[$name] ??= ['name' => $name, 'cases' => [], 'passed' => true, 'worst_abs' => null, 'worst_rel' => null];
            $groups[$name]['cases'][] = $case;
            $groups[$name]['passed'] = $groups[$name]['passed'] && self::status($case) === 'pass';
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
        $known = ['cpu', 'cores', 'memory_gb', 'os', 'platform', 'php', 'versions', 'threads', 'repeats', 'warmups', 'date', 'commit', 'code_commit', 'memory', 'statistic', 'cache'];

        $dataset = $benchmark['dataset'] ?? null;
        if (is_array($dataset)) {
            $rows = $dataset['rows'] ?? null;
            $dataset = implode(', ', array_filter([
                // One size, or a list of the sizes the cases use.
                is_numeric($rows) ? number_format($rows).' rows' : (is_array($rows) && $rows !== [] ? implode(' and ', array_map(fn ($n) => is_numeric($n) ? number_format($n) : (string) $n, $rows)).' rows' : null),
                is_string($dataset['columns'] ?? null) ? $dataset['columns'] : null,
                isset($dataset['generator']) && is_string($dataset['generator']) ? 'generated by '.$dataset['generator'].(isset($dataset['seed']) ? ' with seed '.$dataset['seed'] : '') : null,
                is_numeric($dataset['bytes'] ?? null) ? ($dataset['bytes'] >= 1e9 ? number_format($dataset['bytes'] / 1e9, 2).' GB' : number_format($dataset['bytes'] / 1e6, 1).' MB') : null,
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

    // Evidence pages

    /** A record's short sidebar label: its own label, or its title up to the first colon. */
    public static function label(array $record): string
    {
        if (! empty($record['label']) && is_string($record['label'])) {
            return $record['label'];
        }
        $title = (string) ($record['title'] ?? $record['id'] ?? '');

        return str_contains($title, ': ') ? Str::before($title, ': ') : $title;
    }

    /**
     * The exact name of the metric in a benchmark's peak_memory_mib: the
     * record's memory_measure, or the environment's older memory entry
     * without its parenthetical, such as "peak anonymous memory (peakmem)".
     */
    public static function memoryMeasure(array $benchmark): ?string
    {
        $measure = $benchmark['memory_measure'] ?? $benchmark['environment']['memory'] ?? null;

        return is_string($measure) && $measure !== '' ? trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $measure)) : null;
    }

    /** The statistic over repetitions, from the record or its environment. */
    public static function statistic(array $benchmark): ?string
    {
        $statistic = $benchmark['statistic'] ?? $benchmark['environment']['statistic'] ?? null;

        return is_string($statistic) && $statistic !== '' ? $statistic : null;
    }

    /**
     * The memory metrics a benchmark's rows carry, each with its own field
     * and label, so two differently defined metrics never share a chart or a
     * column. peak_memory_mib is the metric memory_measure names; a record
     * that does not name it says so instead of passing as "Memory".
     *
     * @return list<array{field: string, label: string}>
     */
    public static function memoryMetrics(array $benchmark): array
    {
        $has = fn (string $field) => collect($benchmark['rows'] ?? [])->contains(fn (array $row) => is_numeric($row[$field] ?? null));
        $measure = self::memoryMeasure($benchmark);
        $isRss = $measure !== null && preg_match('/resident set size|\brss\b/i', $measure) === 1;
        $metrics = [];
        if ($has('peak_memory_mib')) {
            $metrics[] = ['field' => 'peak_memory_mib', 'label' => $measure === null ? 'Peak memory (metric not stated)' : Str::ucfirst($measure)];
        }
        if ($has('peak_rss_mib') && ! ($isRss && $has('peak_memory_mib'))) {
            $metrics[] = ['field' => 'peak_rss_mib', 'label' => 'Peak resident set size'];
        }
        if ($has('php_peak_mib')) {
            $metrics[] = ['field' => 'php_peak_mib', 'label' => 'PHP allocator peak'];
        }
        if ($has('child_peak_rss_mib')) {
            $metrics[] = ['field' => 'child_peak_rss_mib', 'label' => 'Peak resident set size of child processes'];
        }

        return $metrics;
    }

    /** A time in the record's unit, in milliseconds. */
    public static function milliseconds(mixed $value, ?string $unit): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value * match ($unit) {
            's' => 1000,
            'us', 'µs' => 0.001,
            'ns' => 0.000001,
            default => 1,
        };
    }

    /** A duration in milliseconds as the pages print it: "133 ms", "2.96 s", or "not measured". */
    public static function duration(?float $ms): string
    {
        return match (true) {
            $ms === null => 'not measured',
            $ms < 0.01 => self::number($ms * 1000).' µs',
            $ms < 1000 => self::number($ms).' ms',
            default => self::number($ms / 1000).' s',
        };
    }

    /** Memory in MiB as the pages print it, or "not measured". */
    public static function mebibytes(mixed $mib): string
    {
        return match (true) {
            ! is_numeric($mib) => 'not measured',
            $mib >= 1024 => number_format($mib / 1024, 2).' GiB',
            default => self::number($mib).' MiB',
        };
    }

    /**
     * A verification case's status: its own status, or pass or fail from the
     * older passed flag. A case with neither is "not reported", never a pass.
     *
     * @return 'pass'|'fail'|'skipped'|'not reported'
     */
    public static function status(array $case): string
    {
        $status = is_string($case['status'] ?? null) ? strtolower($case['status']) : null;
        $status = ['passed' => 'pass', 'failed' => 'fail', 'skip' => 'skipped'][$status] ?? $status;
        if (in_array($status, ['pass', 'fail', 'skipped'], true)) {
            return $status;
        }
        if (is_bool($case['passed'] ?? null)) {
            return $case['passed'] ? 'pass' : 'fail';
        }

        return 'not reported';
    }

    /** @return array{pass: int, fail: int, skipped: int, not reported: int} */
    public static function statusCounts(array $cases): array
    {
        $counts = ['pass' => 0, 'fail' => 0, 'skipped' => 0, 'not reported' => 0];
        foreach ($cases as $case) {
            $counts[self::status($case)]++;
        }

        return $counts;
    }

    /**
     * A verification record's comparison rules, as sentences: each rule of
     * its comparison list, or its older free-text tolerance.
     *
     * @return list<array{id: ?string, applies_to: ?string, text: string}>
     */
    public static function rules(array $verification): array
    {
        $rules = $verification['comparison'] ?? null;
        if (is_array($rules) && $rules !== [] && ! array_is_list($rules)) {
            $rules = [$rules];
        }
        if (is_array($rules) && $rules !== []) {
            return array_map(fn (array $rule) => [
                'id' => $rule['id'] ?? null,
                'applies_to' => $rule['applies_to'] ?? null,
                'text' => self::ruleText($rule),
            ], $rules);
        }
        $tolerance = $verification['tolerance'] ?? null;
        if ($tolerance === null || $tolerance === '') {
            return [];
        }

        return [['id' => null, 'applies_to' => null, 'text' => is_numeric($tolerance) ? self::tolerance((float) $tolerance).', as defined under Method' : rtrim((string) $tolerance, '.')]];
    }

    /**
     * One comparison rule as a compact criterion, with x the package's value
     * and r the reference's: "exact equality", or the tolerance bound with
     * its numbers, such as "|x − r| ≤ 1.0e-9 + 1.0e-7 × |r|". A rule with
     * rel_floor scales with max(|r|, floor). A purely relative rule without
     * a floor notes that a zero reference needs an exact zero.
     */
    public static function ruleText(array $rule): string
    {
        if (($rule['kind'] ?? null) === 'exact') {
            return 'exact equality';
        }
        $atol = is_numeric($rule['atol'] ?? null) ? (float) $rule['atol'] : null;
        $rtol = is_numeric($rule['rtol'] ?? null) ? (float) $rule['rtol'] : null;
        $floor = is_numeric($rule['rel_floor'] ?? null) ? (float) $rule['rel_floor'] : null;
        $scale = $floor === null ? '|r|' : 'max(|r|, '.self::tolerance($floor).')';

        return match (true) {
            $atol !== null && $rtol !== null => '|x − r| ≤ '.self::tolerance($atol).' + '.self::tolerance($rtol).' × '.$scale,
            $atol !== null => '|x − r| ≤ '.self::tolerance($atol),
            $rtol !== null => '|x − r| ≤ '.self::tolerance($rtol).' × '.$scale.($floor === null ? '; at r = 0, x = 0 exactly' : ''),
            default => (string) ($rule['text'] ?? 'as stated in the record'),
        };
    }

    /** A tolerance as written in a criterion: 1e-9 rather than 1.0e-9, and 1 for one. */
    public static function tolerance(float $value): string
    {
        if ($value >= 0.001 && $value < 1e4) {
            return rtrim(rtrim(sprintf('%.6f', $value), '0'), '.');
        }
        [$mantissa, $exponent] = explode('e', sprintf('%.1e', $value));

        return rtrim(rtrim($mantissa, '0'), '.').'e'.(int) $exponent;
    }

    /**
     * Every methods fact a benchmark record carries, as label => text, in a
     * fixed order: the workload and its input, how outputs were checked, the
     * implementations, the machine, versions and threads, repetitions and
     * statistic, timing boundaries and where the time is spent, cache, each
     * memory metric by its exact name and method, and when and at which
     * revision it was recorded. Values the record does not give are left out.
     *
     * @return array<string, string>
     */
    public static function benchmarkFacts(array $benchmark): array
    {
        $env = $benchmark['environment'] ?? [];
        $methods = self::methods($benchmark);
        $statistic = self::statistic($benchmark);
        $repeats = $env['repeats'] ?? null;
        $warmups = $env['warmups'] ?? null;

        $implementations = $benchmark['implementations'] ?? null;
        if (is_array($implementations)) {
            $implementations = implode('; ', array_map(fn ($name, $what) => "{$name}: {$what}", array_keys($implementations), $implementations));
        }
        $memory = collect(self::memoryMetrics($benchmark))->pluck('label')->implode('; ');
        $memoryMethod = $benchmark['memory_method'] ?? null;

        return array_filter([
            'Workload' => $methods['Workload'] ?? null,
            'Dataset' => $methods['Dataset'] ?? null,
            'Implementations' => $implementations ?: null,
            'Machine' => self::machine($env) ?: null,
            'Software' => $methods['Software'] ?? null,
            'Threads' => $methods['Threads'] ?? (is_numeric($env['threads'] ?? null) ? (string) $env['threads'] : null),
            'Repetitions' => $repeats === null ? null : implode(', ', array_filter([
                $repeats.' timed '.Str::plural('run', $repeats),
                $warmups === null ? null : $warmups.' warm-up '.Str::plural('run', $warmups),
            ])),
            'Statistic' => $statistic === null ? null : Str::ucfirst($statistic).' over the timed runs',
            'Time measure' => isset($benchmark['measure']) ? Str::ucfirst($benchmark['measure']).(isset($benchmark['unit']) ? ", in {$benchmark['unit']}" : '') : null,
            'Timing boundaries' => $benchmark['method'] ?? $benchmark['timing'] ?? null,
            'Time spent in' => $benchmark['time_spent_in'] ?? null,
            // One state, or one per group of cases, as {"warm cases": "warm", "cold cases": "cold"}.
            'Cache' => match (true) {
                is_array($env['cache'] ?? null) => implode('; ', array_map(fn ($k, $v) => Str::ucfirst((string) $k).': '.$v, array_keys($env['cache']), $env['cache'])),
                is_scalar($env['cache'] ?? null) => Str::ucfirst((string) $env['cache']),
                default => null,
            },
            ...array_diff_key($methods, array_flip(['Workload', 'Dataset', 'Runs', 'Threads', 'Software', 'Memory', 'Recorded'])),
            'Memory metric' => $memory === '' ? 'Not measured' : $memory.($memoryMethod ? ". {$memoryMethod}" : ''),
            'Recorded' => $methods['Recorded'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * The sidebar of a Benchmarks or Verification page. Benchmarks: one
     * anchor per workload on the one page, under its group. Verification:
     * the summary page, then one link per topic under its group, with the
     * current page's own sections under it.
     *
     * @param  'benchmarks'|'verification'  $section
     * @param  list<array{id: string, label: string}>  $children
     * @return list<array{title: ?string, items: list<array>}>
     */
    public static function sidebar(Package $package, string $section, ?string $current, array $children = []): array
    {
        // Benchmarks are one page, so their entries are anchors on it.
        if ($section === 'benchmarks') {
            $groups = [];
            foreach ($package->benchmarks() as $record) {
                $group = is_string($record['group'] ?? null) ? $record['group'] : '';
                $groups[$group] ??= ['title' => $group === '' ? null : $group, 'items' => []];
                $groups[$group]['items'][] = ['id' => $record['id'], 'label' => self::label($record)];
            }

            return array_values($groups);
        }

        $groups = [null => ['title' => null, 'items' => [[
            'href' => $package->url($section),
            'label' => 'Summary',
            'current' => $current === null,
            'children' => $current === null ? $children : [],
        ]]]];
        foreach ($package->verifications() as $record) {
            $group = is_string($record['group'] ?? null) ? $record['group'] : '';
            $groups[$group] ??= ['title' => $group === '' ? null : $group, 'items' => []];
            $groups[$group]['items'][] = [
                'href' => $package->url('verification.topic', ['id' => $record['id']]),
                'label' => self::label($record),
                'current' => $current === $record['id'],
                'children' => $current === $record['id'] ? $children : [],
            ];
        }

        return array_values($groups);
    }

    /**
     * A benchmark's output check as labeled entries, so a record's keys
     * never print joined: a key such as max_abs_difference_vs_R_phit is
     * grouped under "Largest absolute difference from R" with one line per
     * implementation, a nested map under its own label with one line per
     * key, and scalars and sentences each on their own line.
     *
     * @return list<array{label: string, value: ?string, lines: list<string>}>
     */
    public static function checkEntries(mixed $check): array
    {
        if (! is_array($check)) {
            return [];
        }
        $entries = [];
        foreach ($check as $key => $value) {
            $key = (string) $key;
            if (preg_match('/^(.+?)_vs_([A-Za-z0-9]+)_(.+)$/', $key, $m) && is_scalar($value)) {
                $label = self::checkLabel($m[1]).' from '.$m[2];
                $entries[$label] ??= ['label' => $label, 'value' => null, 'lines' => []];
                $entries[$label]['lines'][] = $m[3].': '.self::checkValue($value);
            } elseif (is_array($value)) {
                $entries[$key] = ['label' => self::checkLabel($key), 'value' => null, 'lines' => array_map(
                    fn ($k, $v) => $k.': '.(is_scalar($v) ? self::checkValue($v) : json_encode($v)),
                    array_keys($value), $value,
                )];
            } elseif ($value !== null) {
                $entries[$key] = ['label' => self::checkLabel($key), 'value' => self::checkValue($value), 'lines' => []];
            }
        }

        return array_values($entries);
    }

    /** A check key in words: max_abs_coefficient_difference reads "Largest absolute coefficient difference". */
    private static function checkLabel(string $key): string
    {
        $words = array_map(fn (string $w) => match ($w) {
            'max' => 'largest', 'min' => 'smallest', 'abs' => 'absolute', 'rel' => 'relative', 'php' => 'PHP', 'mu' => 'μ',
            'scipy' => 'SciPy', 'r' => 'R', 'statsmodels' => 'statsmodels',
            default => $w,
        }, explode('_', $key));
        $label = implode(' ', $words);

        // A library or package name keeps its own case, as dplyr does.
        return preg_match('/^(?:dplyr|tidyr|phit|phibble|phplyr|phpolars|phiz|juphyter|statsmodels|pandas)\b/', $label) ? $label : Str::ucfirst($label);
    }

    /** A check value as printed: yes or no, a grouped integer, a short number, or the text. */
    private static function checkValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'yes' : 'no',
            is_int($value) => number_format($value),
            is_float($value) && $value == 0.0 => '0',
            is_float($value) && (abs($value) < 1e-3 || abs($value) >= 1e6) => self::tolerance(abs($value)) === '0' ? '0' : ($value < 0 ? '−' : '').self::tolerance(abs($value)),
            is_float($value) => rtrim(rtrim(sprintf('%.6g', $value), '0'), '.'),
            default => (string) $value,
        };
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
