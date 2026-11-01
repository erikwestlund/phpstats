<?php

// ---
use Phibble\DataFrame;
use function Phit\dist_gamma_pdf_array;

$x = range(0.0, 20.0, 0.25);
$rows = [];
foreach ([1.0, 2.0, 4.0] as $k) {
    $pdf = dist_gamma_pdf_array($x, $k, scale: 2.0);
    foreach ($x as $i => $v) {
        $rows[] = ['x' => $v, 'k' => "$k", 'density' => $pdf[$i]];
    }
}
$gamma = DataFrame::fromRows($rows);

echo \Phiz\Plot::of($gamma)
    ->aes(x: 'x', y: 'density', color: 'k')
    ->line()
    ->labs(x: 'x', y: 'Density', color: 'Shape')
    ->embed();
