<?php

// ---
use Phibble\DataFrame;
use function Phit\fit_lm;

$rdatasets = 'https://vincentarelbundock.github.io/Rdatasets/csv';
$mtcars = DataFrame::fromCsv("$rdatasets/datasets/mtcars.csv");

$fit = fit_lm('mpg ~ wt + hp', $mtcars);

return $fit;
