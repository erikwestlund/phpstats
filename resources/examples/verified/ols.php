<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_lm;

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$mtcars = DataFrame::fromCsv("$data/mtcars.csv");

$fit = fit_lm('mpg ~ wt + hp', $mtcars);

return $fit;
