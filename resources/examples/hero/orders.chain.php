<?php

// ---
use App\Models\Order;
use function Phplyr\{col, floor_date, phplyr, sum};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$monthly = phplyr($orders)
    ->filter(col('returned')->eq(0))
    ->mutate(month: floor_date(col('placed_on'), 'month'))
    ->groupBy('month', 'channel')
    ->summarize(revenue: sum('total'))
    ->get();

$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'revenue', color: 'channel')
    ->line();

echo $chart->embed();
