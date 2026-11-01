<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_glmer;

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$cbpp = DataFrame::fromCsv(
    "$data/cbpp.csv",
    types: ['period' => 'f'],
);

$fit = fit_glmer(
    'cbind(incidence, size - incidence) ~ period + (1 | herd)',
    $cbpp,
);

return $fit;
