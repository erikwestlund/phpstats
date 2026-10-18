<?php

namespace App\Content;

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
