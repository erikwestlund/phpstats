<?php

// ---
use App\Models\Track;

$tracks = \Phibble\DataFrame::fromQuery(Track::query());

$fit = \Phit\fit_lm('streams ~ playlist_adds + label', $tracks);

return $fit;
