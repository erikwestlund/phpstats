<?php

use App\Models\Order;

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$large = $orders
    ->filter(fn ($row) => $row->total > 100)
    ->select(['placed_on', 'channel', 'items', 'total'])
    ->arrange([['total', 'desc']])
    ->head(5);

return $large;
