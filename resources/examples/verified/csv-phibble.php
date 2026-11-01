<?php

// ---
use Phibble\DataFrame;
use function Phibble\Expr\{col, mean, n};

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$weather = DataFrame::fromCsv("$data/weather.csv");

$byAirport = $weather
    ->groupBy(['origin'])
    ->summarize([
        'hours' => n(),
        'temp' => mean(col('temp'), na_rm: true),
        'humid' => mean(col('humid'), na_rm: true),
    ]);

return $byAirport->toRows();
