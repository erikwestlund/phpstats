<?php

// ---
use function Phit\{
    dist_gamma_cdf,
    dist_gamma_quantile,
    dist_normal_cdf,
    dist_normal_quantile,
};

$values = [
    dist_normal_cdf(-8.0),
    dist_normal_quantile(0.975),
    dist_gamma_cdf(12.0, shape: 2.0, scale: 3.0),
    dist_gamma_quantile(0.999, shape: 2.0, scale: 3.0),
];

return $values;
