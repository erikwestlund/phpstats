<?php

use App\Models\Order;

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$chart = \Phiz\Plot::of($orders)
    ->aes(x: 'total', fill: 'channel')
    ->histogram(bins: 30)
    ->labs(x: 'Order total ($)', y: 'Orders', fill: null);

return $chart;
