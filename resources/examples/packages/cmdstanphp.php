<?php

use App\Models\StudentScore;
use CmdStan\StanData;

use function CmdStan\cmdstan_model;

$scores = \Phibble\DataFrame::fromQuery(StudentScore::query());

$fit = cmdstan_model('school_intercepts.stan')->sample(
    data: StanData::fromFormula('score ~ study_hours + (1 | school)', $scores),
    chains: 4,
    iter_sampling: 1000,
    thin: 4,
    seed: 20261016,
);

$summary = $fit->summary_frame(
    variables: ['beta', 'sigma', 'tau'],
    measures: ['mean', 'sd', 'quantile2', 'rhat', 'ess_bulk', 'ess_tail'],
);

return $summary;
