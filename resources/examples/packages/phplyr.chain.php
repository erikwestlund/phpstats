<?php

use App\Models\Order;

use function Phplyr\{col, phplyr, quarter, round, sum};

$orders = \Phibble\DataFrame::fromQuery(Order::query());
// ---
$shares = phplyr($orders)
    ->mutate(quarter: quarter(col('placed_on')))
    ->groupBy('quarter', 'channel')
    ->summarize(revenue: sum('total'))
    ->mutate(share: round(col('revenue')->div(sum('revenue')), 3))
    ->pivotWider(
        idCols: 'quarter',
        namesFrom: 'channel',
        valuesFrom: 'share',
    )
    ->get();

return $shares;
