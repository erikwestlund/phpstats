<?php

// phplyr pipelines on three phibble backends, copied from the "PolarsTable"
// section of phplyr's performance page (phplyr 4a3cc48), which reports
// phpolars' bench/phplyr.php run. Update by hand when that page changes.

return [
    'source' => 'https://erikwestlund.github.io/phplyr/performance.html',
    'phpolars_source' => 'https://erikwestlund.github.io/phpolars/performance.html',
    'rows' => 1_000_000,
    'machine' => 'Apple M5 Ultra',
    'method' => 'Best of three runs, timed through collect(), with Polars on two threads and phibble dbd8b1e.',
    'backends' => ['Frame' => 'phibble Frame (in memory)', 'PolarsTable' => 'phpolars PolarsTable', 'SQLite' => 'SQLite table'],
    'pipelines' => [
        ['key' => 'A', 'description' => 'Filter, join a store lookup, two-key summarise, then a ranking window', 'seconds' => ['Frame' => 1.52, 'PolarsTable' => 0.06, 'SQLite' => 0.46]],
        ['key' => 'B', 'description' => 'Grouped lag(), cumsum(), and a share of the group total over every row', 'seconds' => ['Frame' => 2.68, 'PolarsTable' => 0.44, 'SQLite' => 3.57]],
        ['key' => 'C', 'description' => 'Pipeline A with PHP closures in place of expressions', 'seconds' => ['Frame' => 1.67, 'PolarsTable' => 0.91, 'SQLite' => 2.88]],
    ],
];
