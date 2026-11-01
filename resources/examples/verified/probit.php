<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_glm;

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$mroz = DataFrame::fromCsv("$data/mroz.csv");

$fit = fit_glm(
    'lfp ~ k5 + k618 + age + wc + hc + lwg + inc',
    $mroz,
    family: 'binomial',
    link: 'probit',
);

return $fit;
