<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_glm;

$rdatasets = 'https://vincentarelbundock.github.io/Rdatasets/csv';
$mroz = DataFrame::fromCsv("$rdatasets/carData/Mroz.csv");

$fit = fit_glm(
    'lfp ~ k5 + k618 + age + wc + hc + lwg + inc',
    $mroz,
    family: 'binomial',
    link: 'probit',
);

return $fit;
