<?php

namespace App\Content;

use DateTimeInterface;
use Phibble\Contracts\Table;
use Phiz\Chart;

/**
 * Normalizes the blocks a vignette script returns into one shape per type
 * for the vignette-block component.
 */
class Blocks
{
    public static function normalize(array $block): array
    {
        $type = $block['type'] ?? 'text';

        return match ($type) {
            'text', 'note' => ['type' => $type, 'markdown' => (string) ($block['markdown'] ?? $block['content'] ?? '')],
            'code' => [
                'type' => 'code',
                'chain' => $block['chain'] ?? null,
                'pipe' => $block['pipe'] ?? null,
                'code' => $block['code'] ?? $block['content'] ?? null,
            ],
            'output' => ['type' => 'output', 'content' => (string) ($block['content'] ?? '')],
            'metrics' => ['type' => 'metrics', 'title' => $block['title'] ?? null, 'items' => self::metrics($block)],
            'table' => ['type' => 'table', 'title' => $block['title'] ?? null, ...self::table($block)],
            'model' => ['type' => 'model', 'title' => $block['title'] ?? null, ...self::model($block['result'] ?? [])],
            'chart' => ['type' => 'chart', 'title' => $block['title'] ?? null, 'plot' => self::plot($block)],
            default => ['type' => 'output', 'content' => "Unknown block type {$type}."],
        };
    }

    /** @return list<array{label: string, value: string}> */
    private static function metrics(array $block): array
    {
        $items = [];
        if (isset($block['items'])) {
            foreach ($block['items'] as $item) {
                $items[] = ['label' => (string) $item['label'], 'value' => self::format($item['value'] ?? null, $item['format'] ?? null)];
            }
        }
        foreach ($block['values'] ?? [] as $label => $value) {
            $items[] = ['label' => (string) $label, 'value' => self::format($value)];
        }

        return $items;
    }

    /** @return array{columns: list<string>, rows: list<list<string>>, more: int} */
    private static function table(array $block): array
    {
        $digits = $block['digits'] ?? null;
        $more = 0;
        if (($block['data'] ?? null) instanceof Table) {
            $table = $block['data'];
            $more = max(0, $table->nrow() - 20);
            $rows = $table->head(20)->toRows();
            $columns = $rows === [] ? $table->columns() : array_keys((array) $rows[0]);
        } else {
            $columns = array_map('strval', $block['columns'] ?? []);
            $rows = $block['rows'] ?? [];
            if ($columns === [] && $rows !== []) {
                $columns = array_map('strval', array_keys((array) $rows[0]));
            }
        }

        $cells = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $values = array_is_list($row) ? $row : array_map(fn ($column) => $row[$column] ?? null, $columns);
            $cells[] = array_map(fn ($value) => self::format($value, null, $digits), $values);
        }

        return ['columns' => $columns, 'rows' => $cells, 'more' => $more];
    }

    /**
     * A coefficient table from a Phit fit_* result: estimates, standard
     * errors, test statistics, p-values, and confidence intervals when the
     * fit reports them.
     */
    private static function model(array $fit): array
    {
        $terms = $fit['columns'] ?? array_keys($fit['named'] ?? []);
        $statistic = isset($fit['t_statistics']) ? ['t', $fit['t_statistics']] : (isset($fit['z_statistics']) ? ['z', $fit['z_statistics']] : null);
        $columns = ['Term', 'Estimate'];
        if (isset($fit['std_errors'])) {
            $columns[] = 'Std. error';
        }
        if ($statistic) {
            $columns[] = $statistic[0];
        }
        if (isset($fit['p_values'])) {
            $columns[] = 'p';
        }
        if (isset($fit['conf_int'])) {
            $columns[] = '95% CI';
        }

        $rows = [];
        foreach ($terms as $i => $term) {
            $row = [$term, self::format($fit['coefficients'][$i] ?? ($fit['named'][$term] ?? null), null, 4)];
            if (isset($fit['std_errors'])) {
                $row[] = self::format($fit['std_errors'][$i] ?? null, null, 4);
            }
            if ($statistic) {
                $row[] = self::format($statistic[1][$i] ?? null, null, 2);
            }
            if (isset($fit['p_values'])) {
                $p = $fit['p_values'][$i] ?? null;
                $row[] = is_numeric($p) && $p < 0.0001 ? '< 0.0001' : self::format($p, null, 4);
            }
            if (isset($fit['conf_int'])) {
                $interval = $fit['conf_int'][$i] ?? [null, null];
                $row[] = '['.self::format($interval[0], null, 3).', '.self::format($interval[1], null, 3).']';
            }
            $rows[] = $row;
        }

        $summary = array_filter([
            isset($fit['n_observations']) ? 'n = '.number_format($fit['n_observations']) : null,
            isset($fit['r_squared']) ? 'R² = '.self::format($fit['r_squared'], null, 4) : null,
            isset($fit['sigma']) ? 'σ = '.self::format($fit['sigma'], null, 4) : null,
            isset($fit['log_likelihood']) ? 'log-likelihood = '.self::format($fit['log_likelihood'], null, 2) : null,
            isset($fit['aic']) ? 'AIC = '.self::format($fit['aic'], null, 1) : null,
            isset($fit['deviance']) ? 'deviance = '.self::format($fit['deviance'], null, 2) : null,
        ]);

        return ['columns' => $columns, 'rows' => $rows, 'summary' => implode(', ', $summary)];
    }

    private static function plot(array $block): array|Chart
    {
        $plot = $block['plot'] ?? $block['spec'] ?? [];
        if (is_array($plot) && ! isset($plot['$schema'])) {
            $plot['$schema'] = 'https://vega.github.io/schema/vega-lite/v6.json';
        }

        return $plot;
    }

    public static function format(mixed $value, ?string $format = null, ?int $digits = null): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_bool($value) => $value ? 'true' : 'false',
            $format !== null && is_numeric($value) => sprintf($format, $value),
            is_int($value) => number_format($value),
            is_float($value) && ! is_finite($value) => is_nan($value) ? 'NaN' : ($value > 0 ? 'Inf' : '-Inf'),
            is_float($value) => self::float($value, $digits),
            is_array($value) => implode(', ', array_map(fn ($v) => self::format($v, null, $digits), $value)),
            default => (string) $value,
        };
    }

    private static function float(float $value, ?int $digits): string
    {
        if ($digits !== null) {
            return number_format($value, $digits);
        }
        $magnitude = abs($value);
        if ($magnitude !== 0.0 && ($magnitude < 1e-3 || $magnitude >= 1e7)) {
            return sprintf('%.3e', $value);
        }

        return rtrim(rtrim(number_format($value, $magnitude >= 100 ? 2 : 4, '.', ','), '0'), '.');
    }
}
