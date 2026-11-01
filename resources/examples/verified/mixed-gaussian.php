<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_mixed_gaussian;

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$sleepstudy = DataFrame::fromCsv("$data/sleepstudy.csv");

$fit = fit_mixed_gaussian(
    'Reaction ~ Days + (1 | Subject)',
    $sleepstudy,
);

return $fit;
