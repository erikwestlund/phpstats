<?php

use App\Models\StudentScore;
use CmdStan\StanData;

use function CmdStan\cmdstan_model;

$scores = \Phibble\DataFrame::fromQuery(StudentScore::query());
// ---
$data = StanData::fromFormula(
    'score ~ study_hours + (1 | school)',
    $scores,
);

$fit = cmdstan_model('school_intercepts.stan')
    ->sample(data: $data, chains: 4, thin: 4, seed: 20261016);

$summary = $fit->summaryTable(
    variables: ['beta', 'sigma', 'tau'],
    measures: ['mean', 'sd', 'quantile2', 'rhat', 'ess_bulk', 'ess_tail'],
);

return $summary;
