<?php

use App\Models\Order;

use function Phplyr\{arrange, desc, group_by, mean, n, summarise};

$orders = \Phibble\Polars\PolarsTable::fromTable(\Phibble\DataFrame::fromQuery(Order::query()));

$byChannel = $orders
    |> group_by('channel', 'customer')
    |> summarise(orders: n(), average: mean('total'))
    |> arrange(desc('average'));

return $byChannel;
