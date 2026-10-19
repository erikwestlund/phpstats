<?php

use App\Models\Order;

use function Phplyr\{col, desc, mean, n, phplyr};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$customers = phplyr($orders)
    ->mutate(item_price: col('total')->div(col('items')))
    ->group_by('customer', 'channel')
    ->summarise(
        orders: n(),
        item_price: mean('item_price'),
        coupon_rate: mean('coupon'),
        return_rate: mean('returned'),
    )
    ->arrange(desc('return_rate'))
    ->get();

return $customers;
