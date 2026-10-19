<?php

use App\Models\Track;

$tracks = \Phibble\DataFrame::fromQuery(Track::query());
// ---
use Phibble\DataFrame;

use function Phibble\Expr\{col, str_detect};

$fit = \Phit\fit_lm('streams ~ playlist_adds * label + genre', $tracks);

$t = \Phit\dist_t_quantile(0.975, $fit['df_residual']);

$slopes = DataFrame::fromColumns([
    'term' => $fit['columns'],
    'estimate' => $fit['coefficients'],
    'std_error' => $fit['std_errors'],
])
    ->filter(str_detect(col('term'), 'playlist_adds'))
    ->mutate([
        'lower' => col('estimate')->sub(col('std_error')->mul($t)),
        'upper' => col('estimate')->add(col('std_error')->mul($t)),
    ]);

return ['fit' => $fit, 'table' => $slopes];
