<?php

use App\Models\Order;

$orders = \Phibble\DataFrame::fromQuery(Order::query());

return $orders;
