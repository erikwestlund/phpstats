<?php

// ---
use App\Models\Order;
use Phibble\DataFrame;

$orders = DataFrame::fromQuery(Order::query(), types: [
    'channel' => 'factor',
    'coupon' => 'bool',
    'returned' => 'bool',
]);

echo $orders->glimpse();
