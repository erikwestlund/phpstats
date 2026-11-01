<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_ols;

$data = 'https://raw.githubusercontent.com/phpstats/datasets/main/csv';
$mtcars = DataFrame::fromCsv("$data/mtcars.csv");

$fit = fit_ols('mpg ~ wt + hp', $mtcars);

return $fit;
