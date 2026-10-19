<?php

// ---
use Illuminate\Support\Facades\DB;
use Phibble\Polars\PolarsTable;

use function Phplyr\{arrange, desc, group_by, mean, n, summarize};
use function Phpolars\read_database;

$orders = read_database(
    'select channel, customer, total from orders',
    DB::connection()->getPdo(),
);

$byChannel = PolarsTable::fromPolars($orders) |>
    group_by('channel', 'customer') |>
    summarize(orders: n(), average: mean('total')) |>
    arrange(desc('average'));

return $byChannel;
