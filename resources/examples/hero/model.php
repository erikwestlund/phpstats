<?php

// ---
use App\Models\Order;
use Phibble\DataFrame;
use function Phit\fit_generalized_linear;

$orders = DataFrame::fromQuery(Order::query());
$fit = fit_generalized_linear('returned ~ channel + items',
    $orders, family: 'binomial');

echo DataFrame::fromColumns([
    'term' => $fit['columns'],
    'estimate' => $fit['coefficients'],
    'std_error' => $fit['std_errors'],
    'p_value' => $fit['p_values'],
]);
