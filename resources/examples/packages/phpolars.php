<?php

use App\Models\Order;

use function Phplyr\{arrange, desc, group_by, mean, n, summarise};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$table = \Phibble\Polars\PolarsTable::fromTable($orders);

$byChannel = $table |>
    group_by('channel', 'customer') |>
    summarise(orders: n(), average: mean('total')) |>
    arrange(desc('average'));

return $byChannel;
