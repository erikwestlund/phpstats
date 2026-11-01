<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_lmer;

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$sleepstudy = DataFrame::fromCsv("$data/sleepstudy.csv");

$fit = fit_lmer(
    'Reaction ~ Days + (1 | Subject)',
    $sleepstudy,
);

return $fit;
