<?php

// ---
use App\Models\Order;

use function Phplyr\{col, filter, group_by, mean, n, round, sum, summarize};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$byChannel = $orders |>
    filter(col('returned')->eq(0)) |>
    group_by('channel') |>
    summarize(
        orders: n(),
        revenue: sum('total'),
        average: round(mean('total'), 2),
    );

return $byChannel;
