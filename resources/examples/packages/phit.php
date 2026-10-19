<?php

use App\Models\Order;

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$returns = \Phit\fit_glm('returned ~ items + coupon + channel', $orders, 'binomial');

return $returns;
