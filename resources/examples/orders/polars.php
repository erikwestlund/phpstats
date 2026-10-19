<?php

use App\Models\Order;

use function Phplyr\{arrange, col, desc, filter, group_by, mean, n, sum, summarize};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$table = \Phibble\Polars\PolarsTable::fromTable($orders);

$revenue = $table |>
    filter(col('returned')->eq(0)) |>
    group_by('channel') |>
    summarize(orders: n(), revenue: sum('total'), average: mean('total')) |>
    arrange(desc('revenue'));

return $revenue;
