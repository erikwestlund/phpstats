<?php

// ---
use function Phpolars\{col, len, read_csv};

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$weather = read_csv(
    "$data/weather.csv",
    columns: ['origin', 'temp', 'humid'],
);

$byAirport = $weather
    ->groupBy('origin')
    ->agg(
        len()->alias('hours'),
        col('temp')->mean()->alias('temp'),
        col('humid')->mean()->alias('humid'),
    )
    ->sort('origin');

return $byAirport->toDicts();
