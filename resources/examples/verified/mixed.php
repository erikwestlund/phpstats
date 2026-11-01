<?php

chdir(database_path('data'));

// ---
use Phibble\DataFrame;
use function Phit\fit_lmer;

$schools = DataFrame::fromCsv('schools.csv');

$fit = fit_lmer(
    'score ~ study_hours + prior_score + (1 | school)',
    $schools,
    reml: false,
);

return $fit;
