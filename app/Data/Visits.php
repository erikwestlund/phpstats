<?php

namespace App\Data;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Simulated daily site visits in 2025 by traffic source, for the landing
 * page's phiz example. Search and social grow through the year, email spikes
 * after monthly newsletters, and weekends are quieter.
 */
final class Visits
{
    public const SEED = 20251020;

    /** Visits on a weekday in January, and growth over the year, by source. */
    public const SOURCES = [
        'Search' => [420, 0.9],
        'Social' => [160, 1.6],
        'Direct' => [260, 0.15],
        'Email' => [90, 0.1],
        'Referral' => [120, 0.25],
    ];

    /** @return list<array{visited_on: string, source: string, visits: int}> */
    public static function generate(): array
    {
        $random = new Randomizer(new Mt19937(self::SEED));
        $start = new \DateTimeImmutable('2025-01-01');
        $rows = [];

        for ($day = 0; $day < 365; $day++) {
            $date = $start->modify("+{$day} days");
            $weekend = (int) $date->format('N') >= 6;
            foreach (self::SOURCES as $source => [$base, $growth]) {
                $level = $base * (1 + $growth * $day / 365) * ($weekend ? 0.7 : 1.0);
                if ($source === 'Email' && (int) $date->format('j') <= 3) {
                    $level *= 3.0;
                }
                $rows[] = [
                    'visited_on' => $date->format('Y-m-d'),
                    'source' => $source,
                    'visits' => max(0, (int) round($level * (1 + 0.12 * ($random->nextFloat() * 2 - 1)))),
                ];
            }
        }

        return $rows;
    }
}
