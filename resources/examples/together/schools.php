<?php

chdir(database_path('data'));

// ---
use function Phpolars\scan_csv;

$schools = scan_csv('schools.csv')
    ->select(['school', 'study_hours', 'score'])
    ->collect();

// ---
use function Phit\fit_mixed_gaussian;

$data = $schools->toColumns();

$fit = fit_mixed_gaussian(
    'score ~ study_hours + (1 | school)',
    $data,
);
$data['fitted'] = $fit['fitted'];

// ---
$chart = \Phiz\Plot::of($data)
    ->aes(x: 'study_hours', y: 'score')
    ->point(alpha: 0.3)
    ->line(
        aes: ['y' => 'fitted', 'group' => 'school'],
        alpha: 0.6,
    )
    ->labs(x: 'Study hours', y: 'Test score');

echo $chart->embed();
