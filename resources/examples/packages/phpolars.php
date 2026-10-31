<?php

// ---
use Illuminate\Support\Facades\DB;

use function Phpolars\{col, len, read_database};

$orders = read_database(
    'select channel, total, returned from orders',
    DB::connection()->getPdo(),
);

$byChannel = $orders
    ->filter(col('returned')->eq(0))
    ->groupBy('channel')
    ->agg(
        len()->alias('orders'),
        col('total')->sum()->round(2)->alias('revenue'),
    )
    ->sort('revenue', descending: true);

return $byChannel;
