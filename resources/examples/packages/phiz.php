<?php

// ---
use App\Models\Track;

$tracks = \Phibble\DataFrame::fromQuery(Track::query());

$chart = \Phiz\Plot::of($tracks)
    ->aes(x: 'playlist_adds', y: 'streams', color: 'label')
    ->point(alpha: 0.4)
    ->smooth(method: 'lm')
    ->labs(
        x: 'Playlist adds (thousands)',
        y: 'Streams (millions)',
        color: null,
    );

echo $chart->embed();
