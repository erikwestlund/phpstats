<?php

namespace App\Content;

/** Counts across the family's synced content, for the landing and Markdown pages. */
class Stats
{
    /** @return array{packages: int, verified: int, comparisons: int, examples: int, benchmarks: int} */
    public static function family(): array
    {
        $stats = ['packages' => 0, 'verified' => 0, 'comparisons' => 0, 'examples' => 0, 'benchmarks' => 0];
        foreach (Package::all() as $package) {
            $stats['packages']++;
            foreach ($package->verifications() as $verification) {
                $stats['comparisons'] += count($verification['cases']);
                $stats['verified'] += count(array_filter($verification['cases'], fn (array $case) => $case['passed'] ?? false));
            }
            $stats['benchmarks'] += count($package->benchmarks());
            $stats['examples'] += count($package->examples());
        }

        return $stats;
    }
}
