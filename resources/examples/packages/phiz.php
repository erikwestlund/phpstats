<?php

use App\Models\Track;

$tracks = \Phibble\DataFrame::fromQuery(Track::query());
// ---
$chart = \Phiz\Plot::of($tracks)
    ->aes(x: 'playlist_adds', y: 'streams', color: 'label')
    ->point(alpha: 0.35, size: 14)
    ->smooth(method: 'lm', se: true, linewidth: 2.5)
    ->scaleColor(
        values: ['Major label' => '#a855f7', 'Independent' => '#f97316'],
        guide: ['symbolOpacity' => 1],
    )
    ->facetWrap('genre', ncol: 3)
    ->labs(
        title: 'Streams per playlist add, by genre',
        subtitle: 'Playlist adds in thousands, streams in millions',
        x: 'Playlist adds',
        y: 'Streams',
        color: null,
    )
    ->theme(legendPosition: 'bottom')
    ->size(width: 178, height: 230);

return $chart;
