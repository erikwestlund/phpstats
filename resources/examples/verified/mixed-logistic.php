<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_glmer;

$rdatasets = 'https://vincentarelbundock.github.io/Rdatasets/csv';
$cbpp = DataFrame::fromCsv(
    "$rdatasets/lme4/cbpp.csv",
    types: ['period' => 'f'],
);

$fit = fit_glmer(
    'cbind(incidence, size - incidence) ~ period + (1 | herd)',
    $cbpp,
);

return $fit;
