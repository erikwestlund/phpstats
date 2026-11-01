<?php

namespace App\Content;

use Phiz\Plot;

/**
 * A phiz bar chart of one measure of a benchmark workload: one horizontal
 * bar per implementation, in the record's order, labeled with its value as
 * the page prints it. PHPstats implementations are PHP purple, the others
 * gray, and phit-polyfill a lighter neutral gray, so the extension and its
 * plain-PHP fallback read apart at a glance.
 */
class BenchmarkChart
{
    /**
     * Implementations from outside PHPstats: the R and Python libraries,
     * the other CmdStan interfaces, CmdStan on its own, and plain PHP. A
     * label ending in "(R)" or "(Python)" is one too. Every other label on a
     * package's page names PHPstats code, such as DataFrame, SqlTable, "phit
     * reader", "juphyter kernel", or "PHP engine alone".
     */
    private const OTHER = '/^(?:R|SciPy|statsmodels|GPBoost|lme4|lm|glm|dplyr|tidyr|readr|tibble|pandas|numpy|py-polars|python3? ipykernel|ipykernel|CmdStanR|CmdStanPy|cmdstanr|cmdstanpy|CmdStan alone|plain PHP)(?![\w-])|\((?:R|Python)\)$/';

    /** How an implementation's bar is colored: PHPstats, Other, or Polyfill. */
    public static function kind(string $implementation): string
    {
        return match (true) {
            str_starts_with($implementation, 'phit-polyfill') => 'Polyfill',
            (bool) preg_match(self::OTHER, $implementation) => 'Other',
            default => 'PHPstats',
        };
    }

    /**
     * @param list<array{name: string, value: float, shown: string}> $bars
     */
    public static function plot(array $bars, string $title): Plot
    {
        $rows = array_map(fn (array $bar) => [
            'implementation' => $bar['name'],
            'value' => $bar['value'],
            'shown' => $bar['shown'],
            'kind' => self::kind($bar['name']),
        ], $bars);

        return Plot::of($rows)
            ->aes(x: 'implementation', y: 'value', fill: 'kind')
            ->col(width: 0.62)
            ->text(aes: ['label' => 'shown'], align: 'left', dx: 6)
            ->coordFlip()
            ->scaleX(domain: array_column($rows, 'implementation'))
            ->scaleFill(domain: ['PHPstats', 'Other', 'Polyfill'], range: ['#777bb4', '#9c9cb2', '#cfcfd6'])
            // Room past the longest bar for its label.
            ->scaleY(domain: [0, 1.28 * max(array_column($rows, 'value'))], nice: false)
            ->labs(x: null, y: $title, fill: null)
            // Axes with tick marks, and no gridlines or frame.
            // Axes with tick marks and no gridlines or frame. The bars start where
            // the category axis would be drawn, so that axis has labels only.
            ->theme(legendPosition: 'none', grid: false, axisLine: true, ticks: true, config: [
                'view' => ['stroke' => null],
                'axisY' => ['domain' => false, 'ticks' => false, 'labelPadding' => 6],
            ])
            ->width('container')
            ->height(max(96, 34 * count($rows)));
    }
}
