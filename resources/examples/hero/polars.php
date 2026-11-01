<?php

chdir(database_path('data'));

// ---
use function Phpolars\{col, scan_csv};

$quarter = col('date')->dt()->quarter()->alias('quarter');

$quarters = scan_csv('daily-temperatures.csv', tryParseDates: true)
    ->groupBy(['city', $quarter])
    ->agg(
        col('temp_c')->min()->alias('coldest'),
        col('temp_c')->mean()->round(1)->alias('mean'),
        col('temp_c')->max()->alias('warmest'),
    )
    ->sort(['city', 'quarter'])
    ->collect();

echo $quarters;
