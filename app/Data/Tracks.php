<?php

namespace App\Data;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Simulated tracks released in 2025, for the landing page's phiz example:
 * each track's playlist adds (thousands) and streams in its first three
 * months (millions). Streams grow with playlist adds at a rate that differs
 * by genre, and major-label tracks gain more streams per add than
 * independent ones.
 */
final class Tracks
{
    public const SEED = 20251021;

    public const PER_GENRE = 160;

    /** Streams with no playlist adds and streams per thousand adds, by genre. */
    public const GENRES = ['Pop' => [1.5, 0.95], 'Hip-Hop' => [1.0, 1.15], 'Electronic' => [0.6, 0.7]];

    /** Share of tracks on a major label, and their streams per add relative to independents. */
    public const MAJOR = [0.4, 1.35];

    /** @return list<array{genre: string, label: string, playlist_adds: float, streams: float}> */
    public static function generate(): array
    {
        $random = new Randomizer(new Mt19937(self::SEED));
        [$majorShare, $majorLift] = self::MAJOR;
        $rows = [];

        foreach (self::GENRES as $genre => [$base, $slope]) {
            for ($i = 0; $i < self::PER_GENRE; $i++) {
                $major = $random->nextFloat() < $majorShare;
                // Most tracks get few adds; a few get many.
                $adds = 0.5 + 59.5 * $random->nextFloat() ** 1.8;
                $mean = $base + $slope * $adds * ($major ? $majorLift : 1.0);
                $streams = $mean + (0.6 + 0.12 * $mean) * self::normal($random);

                $rows[] = [
                    'genre' => $genre,
                    'label' => $major ? 'Major label' : 'Independent',
                    'playlist_adds' => round($adds, 1),
                    'streams' => round(max(0.05, $streams), 2),
                ];
            }
        }

        return $rows;
    }

    /** Standard normal draw (Box-Muller). */
    private static function normal(Randomizer $random): float
    {
        return sqrt(-2.0 * log(1.0 - $random->nextFloat())) * cos(2.0 * M_PI * $random->nextFloat());
    }
}
