<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_lmer;

$rdatasets = 'https://vincentarelbundock.github.io/Rdatasets/csv';
$sleepstudy = DataFrame::fromCsv("$rdatasets/lme4/sleepstudy.csv");

$fit = fit_lmer('Reaction ~ Days + (1 | Subject)', $sleepstudy);

return $fit;
