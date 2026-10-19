<?php

use App\Models\Order;

use function Phplyr\{col, floor_date, group_by, mutate, sum, summarise};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$monthly = $orders
    |> mutate(month: floor_date(col('placed_on'), 'month'))
    |> group_by('month', 'channel')
    |> summarise(revenue: sum('total'));

$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'revenue', color: 'channel')
    ->line()
    ->point()
    ->labs(x: null, y: 'Revenue ($)', color: null);

return $chart;
