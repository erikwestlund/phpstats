<?php

// ---
use Illuminate\Support\Facades\DB;

use function Phpolars\{col, len, read_database};

$orders = read_database(
    'select channel, cast(total as double precision) as total
     from orders where not returned',
    DB::connection()->getPdo(),
);

$byChannel = $orders
    ->groupBy('channel')
    ->agg(
        len()->alias('orders'),
        col('total')->sum()->round(2)->alias('revenue'),
    )
    ->sort('revenue', descending: true);

echo $byChannel;
