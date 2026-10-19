<?php

use App\Models\Order;

use function Phplyr\{col, desc, mean, n, phplyr};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$customers = phplyr($orders)
    ->mutate(item_price: col('total')->div(col('items')))
    ->group_by('customer', 'channel')
    ->summarise(orders: n(), price: mean('item_price'), returns: mean('returned'))
    ->arrange(desc('returns'))
    ->get();

return $customers;
