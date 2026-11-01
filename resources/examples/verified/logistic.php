<?php

chdir(database_path('data'));

// ---
use Phibble\DataFrame;
use function Phit\fit_glm;

$students = DataFrame::fromCsv('learning.csv');

$fit = fit_glm(
    'passed ~ study_hours + practice_score',
    $students,
    family: 'binomial',
);

return $fit;
