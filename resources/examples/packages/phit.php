<?php

// ---
use App\Models\Track;

$tracks = \Phibble\DataFrame::fromQuery(Track::query());

$fit = \Phit\fit_ols('streams ~ playlist_adds + label', $tracks);

echo \Phibble\DataFrame::fromColumns([
    'term' => $fit['columns'],
    'estimate' => $fit['coefficients'],
    'std_error' => $fit['std_errors'],
    'p_value' => $fit['p_values'],
]);
