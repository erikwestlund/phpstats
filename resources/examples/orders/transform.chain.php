<?php

use App\Models\Order;

use function Phplyr\{col, desc, mean, n, phplyr, sum};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$revenue = phplyr($orders)
    ->filter(col('returned')->eq(0))
    ->group_by('channel')
    ->summarize(orders: n(), revenue: sum('total'), average: mean('total'))
    ->arrange(desc('revenue'))
    ->get();

return $revenue;
