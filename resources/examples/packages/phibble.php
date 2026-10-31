<?php

// ---
use App\Models\Order;
use Phibble\DataFrame;

$orders = DataFrame::fromQuery(Order::query(), types: [
    'channel' => 'factor',
    'coupon' => 'bool',
    'returned' => 'bool',
]);

return $orders->glimpse(width: 76);
