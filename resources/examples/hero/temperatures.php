<?php

chdir(database_path('data'));

// ---
use Phibble\DataFrame;
use function Phplyr\{col, floor_date, group_by};
use function Phplyr\{mean, mutate, summarize};

$temps = DataFrame::fromCsv('daily-temperatures.csv');
$monthly = $temps |>
    mutate(month: floor_date(col('date'), 'month')) |>
    group_by('month', 'city') |>
    summarize(temp_c: mean('temp_c'));

$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'temp_c', color: 'city')
    ->line()
    ->labs(x: 'Month', y: 'Mean temperature (°C)', color: 'City');

echo $monthly;
echo $chart->embed();

return ['monthly' => $monthly, 'chart' => $chart];
