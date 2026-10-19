<?php

use App\Models\Order;

use function Phplyr\{col, phplyr, quarter, round, sum};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$shares = phplyr($orders)
    ->mutate(quarter: quarter(col('placed_on')))
    ->group_by('quarter', 'channel')
    ->summarize(revenue: sum('total'))
    ->mutate(share: round(col('revenue')->div(sum('revenue')), 3))
    ->pivot_wider(
        id_cols: 'quarter',
        names_from: 'channel',
        values_from: 'share',
    )
    ->get();

return $shares;
