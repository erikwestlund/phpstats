# Landing page evidence (2026-11-01)

The landing page gains three sections after the package explorer: a connected
phpolars → phit → phiz demonstration, a CSV benchmark against plain PHP, and a
selection of verification results. Each links to a complete record in a
package's repository or docs. The site renders saved results and never runs the
large workload.

## phpolars: CSV pipeline benchmark

Adopt the prototype in `2026-11-01-csv-benchmark/` as `bench/csv-pipeline/`:

- `make-orders.php ROWS PATH`: a seeded order-events CSV (order_id, placed_on,
  region, channel, status, quantity, amount; about 1% of amounts empty). 20
  million rows make a 1.03 GB file in about 7 seconds.
- `phpstats.php` and `plain.php`: the two pipelines. Both keep completed
  orders and compute orders, units, revenue, and the mean amount by region and
  month (192 groups). phpolars uses `scan_csv()` and
  `collect(engine: 'streaming')`. Plain PHP streams rows with `fgetcsv()`.
  The site shows these two files verbatim, so keep them free of comments and
  don't reformat them after the run.
- `run.php CSV [REPS]`: one warm-up of each, then REPS alternating runs, each a
  fresh process; checks that the results agree (counts exactly, sums and means
  to 1e-9 relative); prints the site record, including the source of both
  scripts under `code`. Run with
  `PHPOLARS_EXTENSION=/path/to/php_polars.so php -d extension=... run.php orders.csv 7`.

Commit the scripts first, then run from the committed tree so
`environment.commit` names it, with the default `php_polars.max_threads` (2).
Save the output as `site/benchmarks/csv-pipeline.json`, list it in
`site.json`'s `benchmarks`, and add a short `bench/csv-pipeline/README.md`
with the commands. Add a docs page only if the benchmarks section already has
one per benchmark.

Prototype results on the M5 Ultra (one run each, not for publication):
phpolars 1.4 s and 1.1 GiB peak RSS, plain PHP 13.8 s and 32 MiB.

## phit: Polars → phit → phiz example

Add `docs/examples/polars-mixed-model.qmd`, "Fit a mixed model to a Polars
table and chart it", in the Examples nav section and in site.json `examples`
with `also: ["phpolars", "phiz"]`. It follows the examples page format and
runs `2026-11-01-polars-mixed-model.php` against `docs/data/schools.csv`:

1. `scan_csv()->select([...])->collect()` reads the three columns.
2. `toColumns()` copies them into PHP arrays once. `fit_lmer()` and
   `Plot::of()` both accept an object with `toColumns()` and would each call
   it themselves; the example calls it once and says so.
3. `fit_lmer('score ~ study_hours + (1 | school)', $data)` (REML, as lme4's
   default), then the fitted values join the columns.
4. phiz draws the students as points and each school's fitted line.

Show the coefficient estimates and the chart. Keep the code the same as the
landing page's three steps.
