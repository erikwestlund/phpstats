<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_generalized_linear;

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$mroz = DataFrame::fromCsv("$data/mroz.csv");

$fit = fit_generalized_linear(
    'lfp ~ k5 + k618 + age + wc + hc + lwg + inc',
    $mroz,
    family: 'binomial',
);

return $fit;
