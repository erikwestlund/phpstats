<?php

namespace App\Content;

use Phiz\Plot;

/**
 * A phiz bar chart of one measure of a benchmark workload: one horizontal
 * bar per implementation, in the record's order, labeled with its value as
 * the page prints it. PHPstats implementations are PHP purple and the others
 * gray, so the comparison reads at a glance.
 */
class BenchmarkChart
{
    private const PHPSTATS = '/^(phpolars|phibble|phit|phplyr|CmdStanPHP)\b/';

    /**
     * @param list<array{name: string, value: float, shown: string}> $bars
     */
    public static function plot(array $bars, string $title): Plot
    {
        $rows = array_map(fn (array $bar) => [
            'implementation' => $bar['name'],
            'value' => $bar['value'],
            'shown' => $bar['shown'],
            'kind' => preg_match(self::PHPSTATS, $bar['name']) ? 'PHPstats' : 'Other',
        ], $bars);

        return Plot::of($rows)
            ->aes(x: 'implementation', y: 'value', fill: 'kind')
            ->col(width: 0.62)
            ->text(aes: ['label' => 'shown'], align: 'left', dx: 6)
            ->coordFlip()
            ->scaleX(domain: array_column($rows, 'implementation'))
            ->scaleFill(domain: ['PHPstats', 'Other'], range: ['#777bb4', '#9c9cb2'])
            // Room past the longest bar for its label.
            ->scaleY(domain: [0, 1.28 * max(array_column($rows, 'value'))], nice: false)
            ->labs(x: null, y: $title, fill: null)
            // Axes with tick marks, and no gridlines or frame.
            ->theme(legendPosition: 'none', grid: false, axisLine: true, ticks: true, config: ['view' => ['stroke' => null]])
            ->width('container')
            ->height(max(96, 34 * count($rows)));
    }
}
