<?php

use App\Models\StudentScore;
use CmdStan\StanData;

use function CmdStan\cmdstan_model;

$scores = \Phibble\DataFrame::fromQuery(StudentScore::query());
$data = StanData::fromFormula('score ~ study_hours + (1 | school)', $scores);

$fit = cmdstan_model('school_intercepts.stan')
    ->sample(data: $data, chains: 4, iter_sampling: 1000);

$intercepts = $fit->draws_columns(['school_intercept']);

return $intercepts;
