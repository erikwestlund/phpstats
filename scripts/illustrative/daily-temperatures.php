<?php
// php scripts/illustrative/daily-temperatures.php > database/data/daily-temperatures.csv
//
// Illustrative data for the landing page's hero example, not observations:
// a daily mean temperature (°C) for New York, London, and Sydney through
// 2025, as a seasonal cycle with plausible means and ranges plus seeded
// noise, so the file is the same on every run.
mt_srand(20251231);

$cities = [
    // mean, half the seasonal range, and the day of the year of the warmest point
    'New York' => [12.9, 12.0, 202],
    'London' => [11.6, 6.8, 200],
    'Sydney' => [18.4, 4.6, 21],
];
$normal = function (): float {
    $u = max(mt_rand() / mt_getrandmax(), 1e-12);
    $v = mt_rand() / mt_getrandmax();

    return sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
};

echo "date,city,temp_c\n";
$start = new DateTimeImmutable('2025-01-01');
for ($day = 0; $day < 365; $day++) {
    $date = $start->modify("+{$day} days");
    foreach ($cities as $city => [$mean, $amplitude, $warmest]) {
        $seasonal = $mean + $amplitude * cos(2 * M_PI * ($day + 1 - $warmest) / 365);
        printf("%s,%s,%.1f\n", $date->format('Y-m-d'), $city, $seasonal + 2.2 * $normal());
    }
}
