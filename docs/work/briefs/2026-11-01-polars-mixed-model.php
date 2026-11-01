<?php
require '/Users/erik/Projects/phpstats.org/vendor/autoload.php';

use function Phit\fit_lmer;
use function Phpolars\scan_csv;

$schools = scan_csv('/Users/erik/Projects/phpstats.org/database/data/schools.csv')
    ->select(['school', 'study_hours', 'score'])
    ->collect();

$data = $schools->toColumns();

$fit = fit_lmer('score ~ study_hours + (1 | school)', $data);
$data['fitted'] = $fit['fitted'];

$chart = \Phiz\Plot::of($data)
    ->aes(x: 'study_hours', y: 'score')
    ->point(alpha: 0.3)
    ->line(aes: ['y' => 'fitted', 'group' => 'school'], alpha: 0.6)
    ->labs(x: 'Study hours per week', y: 'Test score');

file_put_contents(__DIR__.'/demo.json', $chart->toJson());
echo count($data['fitted']), ' ', $fit['n_observations'], ' ', count(array_unique($data['school'])), "\n";
printf("%.3f %.3f %s\n", ...[...$fit['coefficients'], $fit['method']]);
