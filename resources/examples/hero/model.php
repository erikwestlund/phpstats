<?php

// ---
use App\Models\Order;
use Phibble\DataFrame;
use function Phit\fit_glm;

$orders = DataFrame::fromQuery(Order::query());

$fit = fit_glm(
    'returned ~ channel + items + coupon',
    $orders,
    family: 'binomial',
);

$coefficients = DataFrame::fromColumns([
    'term' => $fit['columns'],
    'estimate' => $fit['coefficients'],
    'std_error' => $fit['std_errors'],
    'p_value' => $fit['p_values'],
]);

echo $coefficients;

return ['coefficients' => $coefficients];
