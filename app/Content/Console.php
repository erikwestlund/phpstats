<?php

namespace App\Content;

/** Console-style text for results that have no printed form of their own. */
class Console
{
    /**
     * A coefficient table for a phit fit, laid out like R's summary() of an
     * lm or glm fit, with the fit statistics below it.
     */
    public static function fit(array $fit): string
    {
        $statistic = isset($fit['z_statistics']) ? 'z' : 't';
        $rows = [['', 'Estimate', 'Std. Error', "{$statistic} value", "Pr(>|{$statistic}|)"]];
        foreach ($fit['columns'] as $i => $term) {
            $rows[] = [
                $term,
                sprintf('%.5f', $fit['coefficients'][$i]),
                sprintf('%.5f', $fit['std_errors'][$i]),
                sprintf('%.3f', $fit["{$statistic}_statistics"][$i]),
                self::pValue($fit['p_values'][$i]),
            ];
        }

        $widths = array_map(fn (int $c) => max(array_map(fn (array $row) => strlen($row[$c]), $rows)), array_keys($rows[0]));
        $lines = ['Coefficients:'];
        foreach ($rows as $row) {
            $cells = [str_pad($row[0], $widths[0])];
            foreach (array_slice($row, 1, preserve_keys: true) as $c => $cell) {
                $cells[] = str_pad($cell, $widths[$c], ' ', STR_PAD_LEFT);
            }
            $lines[] = rtrim(implode('  ', $cells));
        }
        $lines[] = '';

        $n = $fit['n_observations'];
        if (isset($fit['deviance'])) {
            $lines[] = sprintf('    Null deviance: %.2f  on %d  degrees of freedom', $fit['null_deviance'], $n - 1);
            $lines[] = sprintf('Residual deviance: %.2f  on %d  degrees of freedom', $fit['deviance'], $fit['df_residual']);
            $lines[] = sprintf('AIC: %.2f', $fit['aic']);
        } else {
            $lines[] = sprintf('Residual standard error: %.4f on %d degrees of freedom', $fit['sigma'], $fit['df_residual']);
            $lines[] = sprintf('Multiple R-squared: %.4f, Adjusted R-squared: %.4f', $fit['r_squared'], $fit['adj_r_squared']);
        }

        return implode("\n", $lines);
    }

    private static function pValue(float $p): string
    {
        return match (true) {
            $p < 2e-16 => '< 2e-16',
            $p < 0.0001 => sprintf('%.2e', $p),
            default => sprintf('%.4f', $p),
        };
    }
}
