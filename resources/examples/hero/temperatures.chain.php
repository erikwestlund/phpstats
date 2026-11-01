<?php

chdir(database_path('data'));

// ---
use Phibble\DataFrame;
use function Phplyr\{col, floor_date, mean, phplyr};

$temps = DataFrame::fromCsv('daily-temperatures.csv');
$monthly = phplyr($temps)
    ->mutate(month: floor_date(col('date'), 'month'))
    ->groupBy('month', 'city')
    ->summarize(temp_c: mean('temp_c'))
    ->get();

echo \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'temp_c', color: 'city')
    ->line()
    ->labs(x: 'Month', y: 'Mean temperature (°C)', color: 'City')
    ->embed();
