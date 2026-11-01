<?php

// ---
use App\Models\Order;
use function Phplyr\{col, filter, floor_date};
use function Phplyr\{group_by, mutate, sum, summarize};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$monthly = $orders |>
    filter(col('returned')->eq(0)) |>
    mutate(month: floor_date(col('placed_on'), 'month')) |>
    group_by('month', 'channel') |>
    summarize(revenue: sum('total'));

$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'revenue', color: 'channel')
    ->line();

echo $chart->embed();
