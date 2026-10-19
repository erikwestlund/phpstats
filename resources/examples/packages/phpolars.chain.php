<?php

// ---
use Illuminate\Support\Facades\DB;
use Phibble\Polars\PolarsTable;

use function Phplyr\{desc, mean, n, phplyr};
use function Phpolars\read_database;

$orders = read_database(
    'select channel, customer, total from orders',
    DB::connection()->getPdo(),
);

$byChannel = phplyr(PolarsTable::fromPolars($orders))
    ->group_by('channel', 'customer')
    ->summarize(orders: n(), average: mean('total'))
    ->arrange(desc('average'))
    ->get();

return $byChannel;
