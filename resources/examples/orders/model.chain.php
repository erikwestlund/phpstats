<?php

use App\Models\Order;

use function Phplyr\{phplyr};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$fit = phplyr($orders)->lm('total ~ items + coupon + customer + channel');

return $fit;
