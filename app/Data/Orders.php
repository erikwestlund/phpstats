<?php

namespace App\Data;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Simulated orders from an online store in 2025, for the landing page
 * examples. Order totals grow with the number of items and differ by channel
 * and customer, and coupons raise them a little. Marketplace orders are
 * returned more often.
 */
final class Orders
{
    public const SEED = 20251019;

    public const ROWS = 2400;

    public const CHANNELS = ['Web' => [0.5, 0.0, -2.6], 'App' => [0.3, 6.0, -3.0], 'Marketplace' => [0.2, -4.0, -1.9]];

    /**
     * @return list<array{placed_on: string, channel: string, customer: string, items: int, coupon: bool, total: float, returned: bool}>
     */
    public static function generate(): array
    {
        $random = new Randomizer(new Mt19937(self::SEED));
        $start = new \DateTimeImmutable('2025-01-01');
        $rows = [];

        for ($i = 0; $i < self::ROWS; $i++) {
            // Orders become more frequent through the year.
            $day = (int) floor(365 * sqrt($random->nextFloat()));
            $channel = self::pick($random, array_map(fn (array $c) => $c[0], self::CHANNELS));
            [, $channelEffect, $returnLogit] = self::CHANNELS[$channel];
            $returning = $random->nextFloat() < 0.6;
            $coupon = $random->nextFloat() < 0.25;
            $items = 1;
            while ($items < 8 && $random->nextFloat() < 0.45) {
                $items++;
            }

            $total = 12.0 + 18.0 * $items + $channelEffect + ($returning ? 8.0 : 0.0) + ($coupon ? 9.0 : 0.0) + 10.0 * self::normal($random);
            $returned = $random->nextFloat() < 1 / (1 + exp(-($returnLogit + 0.15 * $items)));

            $rows[] = [
                'placed_on' => $start->modify("+{$day} days")->format('Y-m-d'),
                'channel' => $channel,
                'customer' => $returning ? 'Returning' : 'New',
                'items' => $items,
                'coupon' => $coupon,
                'total' => round(max(5.0, $total), 2),
                'returned' => $returned,
            ];
        }

        usort($rows, fn (array $a, array $b) => $a['placed_on'] <=> $b['placed_on']);

        return $rows;
    }

    /** @param array<string, float> $weights */
    private static function pick(Randomizer $random, array $weights): string
    {
        $u = $random->nextFloat();
        foreach ($weights as $key => $weight) {
            if (($u -= $weight) < 0) {
                return $key;
            }
        }

        return array_key_last($weights);
    }

    /** Standard normal draw (Box-Muller). */
    private static function normal(Randomizer $random): float
    {
        return sqrt(-2.0 * log(1.0 - $random->nextFloat())) * cos(2.0 * M_PI * $random->nextFloat());
    }
}
