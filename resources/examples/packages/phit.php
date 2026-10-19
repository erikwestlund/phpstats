<?php

use App\Models\Order;

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$totals = \Phit\fit_lm('total ~ items + coupon + channel', $orders);

$returns = \Phit\fit_glm('returned ~ items + channel', $orders, 'binomial');

return $returns;
