<?php

namespace App\Data;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Simulated monthly rents generated from known coefficients.
 *
 * rent = intercept + area_m2 * area + bedrooms * beds + neighborhood effect + N(0, sigma)
 *
 * The first neighborhood is the reference level, so its effect is zero.
 */
final class SimulatedRentals
{
    public const SEED = 20261016;

    public const ROWS = 3000;

    public const INTERCEPT = 400.0;

    public const AREA_M2 = 18.0;

    public const BEDROOMS = 120.0;

    public const SIGMA = 150.0;

    public const NEIGHBORHOODS = [
        'Riverside' => 0.0,
        'Old Town' => 350.0,
        'Hillcrest' => 200.0,
        'Harbor' => 500.0,
        'University' => 150.0,
        'Northgate' => -100.0,
    ];

    /**
     * @return list<array{neighborhood: string, area_m2: float, bedrooms: int, monthly_rent: float}>
     */
    public static function generate(): array
    {
        $random = new Randomizer(new Mt19937(self::SEED));
        $neighborhoods = array_keys(self::NEIGHBORHOODS);
        $rows = [];

        for ($i = 0; $i < self::ROWS; $i++) {
            $neighborhood = $neighborhoods[$random->getInt(0, count($neighborhoods) - 1)];
            $bedrooms = $random->getInt(1, 4);
            $area = round(max(20.0, 30.0 + 22.0 * $bedrooms + 12.0 * self::normal($random)), 1);

            $rent = self::INTERCEPT
                + self::AREA_M2 * $area
                + self::BEDROOMS * $bedrooms
                + self::NEIGHBORHOODS[$neighborhood]
                + self::SIGMA * self::normal($random);

            $rows[] = [
                'neighborhood' => $neighborhood,
                'area_m2' => $area,
                'bedrooms' => $bedrooms,
                'monthly_rent' => round($rent, 2),
            ];
        }

        return $rows;
    }

    /**
     * True coefficients keyed by R's coefficient names for
     * monthly_rent ~ area_m2 + bedrooms + neighborhood, with Riverside as the
     * reference level: (Intercept), area_m2, bedrooms, neighborhoodOld Town, ...
     *
     * @return array<string, float>
     */
    public static function coefficients(): array
    {
        $effects = array_slice(self::NEIGHBORHOODS, 1, preserve_keys: true);

        return [
            '(Intercept)' => self::INTERCEPT,
            'area_m2' => self::AREA_M2,
            'bedrooms' => self::BEDROOMS,
            ...array_combine(array_map(fn ($n) => "neighborhood{$n}", array_keys($effects)), $effects),
        ];
    }

    /** Standard normal draw (Box-Muller). */
    private static function normal(Randomizer $random): float
    {
        return sqrt(-2.0 * log(1.0 - $random->nextFloat())) * cos(2.0 * M_PI * $random->nextFloat());
    }
}
