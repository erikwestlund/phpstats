<?php

use App\Models\Order;

use function Phplyr\lm;

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$fit = $orders |> lm('total ~ items + coupon + customer + channel');

return $fit;
