<?php

// ---
use App\Models\Order;

use function Phplyr\{col, mean, n, phplyr, round, sum};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$byChannel = phplyr($orders)
    ->filter(col('returned')->eq(0))
    ->groupBy('channel')
    ->summarize(
        orders: n(),
        revenue: sum('total'),
        average: round(mean('total'), 2),
    )
    ->get();

echo $byChannel;
