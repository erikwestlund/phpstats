<?php

use App\Models\Order;

use function Phplyr\{col, group_by, mutate, pivot_wider, quarter, round, sum, summarize};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$shares = $orders |>
    mutate(quarter: quarter(col('placed_on'))) |>
    group_by('quarter', 'channel') |>
    summarize(revenue: sum('total')) |>
    mutate(share: round(col('revenue')->div(sum('revenue')), 3)) |>
    pivot_wider(
        idCols: 'quarter',
        namesFrom: 'channel',
        valuesFrom: 'share',
    );

return $shares;
