<?php

chdir(database_path('data'));

// ---
use function Phpolars\{col, scan_csv};

$cities = scan_csv('daily-temperatures.csv')
    ->groupBy('city')
    ->agg(
        col('temp_c')->min()->alias('coldest'),
        col('temp_c')->mean()->round(1)->alias('mean'),
        col('temp_c')->max()->alias('warmest'),
    )
    ->sort('mean', descending: true)
    ->collect();

echo $cities;

return ['cities' => $cities];
