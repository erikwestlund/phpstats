<?php

use App\Models\Order;

use function Phplyr\{arrange, col, desc, group_by, mean, mutate, n, summarise};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$customers = $orders |>
    mutate(item_price: col('total')->div(col('items'))) |>
    group_by('customer', 'channel') |>
    summarise(orders: n(), price: mean('item_price'), returns: mean('returned')) |>
    arrange(desc('returns'));

return $customers;
