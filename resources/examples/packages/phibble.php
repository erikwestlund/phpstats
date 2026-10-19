<?php

// ---
use App\Models\Order;
use Phibble\DataFrame;

use function Phibble\Expr\{col, quarter};

$orders = DataFrame::fromQuery(Order::query(), types: [
    'channel' => 'factor',
    'customer' => 'factor',
    'coupon' => 'bool',
    'returned' => 'bool',
]);

$typed = $orders
    ->select([
        'placed_on', 'channel', 'customer', 'items',
        'coupon', 'total', 'returned',
    ])
    ->mutate([
        'item_price' => col('total')->div(col('items')),
        'quarter' => quarter(col('placed_on')),
    ]);

$overview = $typed->glimpse(width: 76);

return $overview;
