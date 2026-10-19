<?php

use App\Models\Order;

use function Phplyr\{desc, mean, n, phplyr};

$orders = \Phibble\Polars\PolarsTable::fromTable(
    \Phibble\DataFrame::fromQuery(Order::query()),
);

$byChannel = phplyr($orders)
    ->group_by('channel', 'customer')
    ->summarise(orders: n(), average: mean('total'))
    ->arrange(desc('average'))
    ->get();

return $byChannel;
