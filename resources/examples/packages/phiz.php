<?php

use App\Models\DailyVisit;

use function Phplyr\{col, floor_date, group_by, mutate, sum, summarise};

$monthly = \Phibble\DataFrame::fromQuery(DailyVisit::query()) |>
    mutate(month: floor_date(col('visited_on'), 'month')) |>
    group_by('month', 'source') |>
    summarise(visits: sum('visits'));
// ---
$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'visits', color: 'source')
    ->line(linewidth: 2)
    ->scaleX(format: '%b')
    ->scaleColor(values: [
        'Search' => '#a855f7',
        'Social' => '#ec4899',
        'Direct' => '#a1a1aa',
        'Referral' => '#b8b8c0',
        'Email' => '#d0d0d6',
    ])
    ->labs(title: 'Visits over time', x: null, y: 'Visits', color: null);

return $chart;
