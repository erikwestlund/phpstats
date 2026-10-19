<?php

use App\Models\Order;

use function Phplyr\{desc, mean, n, phplyr};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$table = \Phibble\Polars\PolarsTable::fromTable($orders);

$byChannel = phplyr($table)
    ->group_by('channel', 'customer')
    ->summarise(orders: n(), average: mean('total'))
    ->arrange(desc('average'))
    ->get();

return $byChannel;
