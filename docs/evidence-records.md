# Evidence records

The Benchmarks and Verification sections of each package host render saved
JSON records from the package's `site/` folder. `site:sync` copies them to
`resources/content/{package}/`. A page never runs a benchmark or a check.

`site.json` lists the records in sidebar order:

```json
{
  "benchmarks": ["site/benchmarks/csv-pipeline.json"],
  "verifications": ["site/verifications/py-polars-fixtures.json"],
  "evidence_gaps": {
    "benchmarks": "No benchmark covers Parquet reading yet.",
    "verification": "Window functions are not compared with py-polars yet."
  }
}
```

A record's file name is its id and its URL: `/benchmarks/{id}` and
`/verification/{id}`. Keep ids stable; renaming one breaks links. The old
`/benchmarks#{id}` and `/verification#{id}` links redirect to the topic page.

`evidence_gaps` is optional. Each entry is one plain sentence naming what is
not measured or checked. A package with no records shows its gap sentence, or
a one-line statement that nothing is published yet.

Every field below is optional unless marked required. Fields the current
records already use keep their meaning. A missing value is `null` or absent,
never `0` or `""`.

## Verification record

One record is one topic: a behavior or calculation checked one way.

| Field | Meaning |
| --- | --- |
| `title` (required) | Topic heading, such as "OLS: agreement with R". |
| `label` | Short sidebar label, such as "OLS". Defaults to `title` up to its first colon. |
| `kind` | `reference` (compared with another implementation), `contract` (the package's own expected values or invariants), or `integration` (runs an external program or service end to end). |
| `checked` | What behavior or calculation was checked, in one or two sentences. Defaults to `description`. |
| `description` | Longer context. |
| `reference` | The reference implementation and version, as one line. |
| `versions` | Every version that matters, as `{"R": "4.6.1", "lme4": "2.0.6"}`. |
| `inputs` | The input data: a string, or a list of `{"name", "detail", "url"}`. |
| `comparison` | The comparison rules (below). Replaces the free-text `tolerance`, which still renders when `comparison` is absent. |
| `cases` (required) | The rows (below). |
| `exclusions` | List of sentences: inputs or quantities deliberately not compared, and why. |
| `known_differences` | List of sentences: where the package differs from the reference on purpose. |
| `reproduce` | Shell command, or list of commands, run from the package repository root. |
| `source` | Repository path of the full machine-readable results. Linked at the pinned commit. |
| `data` | Repository path or URL of the input data. |
| `environment` | `{"date", "commit", "php", "os"}` of the run. |
| `group` | Sidebar group heading, when a package has many topics. |

### Comparison rules

`comparison` is a list. A case names its rule with `rule` when there is more
than one.

```json
"comparison": [
  {"id": "fixed", "applies_to": "fixed-effects models", "kind": "tolerance", "atol": 1e-9, "rtol": 1e-7},
  {"id": "structure", "applies_to": "column names, types, and row counts", "kind": "exact"}
]
```

- `exact`: the values must be identical. Strings, integers, booleans, dates,
  names, and types compare equal or not; missing values must be missing in
  the same positions. Say in `applies_to` what is compared.
- `tolerance`: with `x` the package's value and `r` the reference's, a value
  passes when `|x − r| ≤ atol + rtol·|r|`. A zero reference then needs
  `|x| ≤ atol`, so give `atol` whenever a reference can be zero or near zero.
  Leave out `atol` or `rtol` for a purely relative or absolute rule.
- Absolute error is `|x − r|`. Relative error is `|x − r| / max(|r|, rel_floor)`.
  Give `rel_floor` (such as `1` for log values, `1e-300` for probabilities)
  whenever `max_rel_error` is reported. Without one the site prints
  "relative to |r|; undefined at r = 0".

### Cases

| Field | Meaning |
| --- | --- |
| `name` (required) | The input or fixture, such as "sleepstudy". Cases with the same name form one row group. |
| `quantity` | What was compared, such as "coefficients" or "6 rows × 9 columns". |
| `status` | `pass`, `fail`, or `skipped`. Replaces `passed`, which still reads as pass or fail. A case with neither shows "not reported", never pass. |
| `reason` | Required for `fail` and `skipped`: one sentence. |
| `rule` | The comparison rule's `id`. |
| `n_compared` | How many values the case compared. |
| `max_abs_error`, `max_rel_error` | Largest errors over those values, as defined above. |
| `actual`, `expected` | The package's and the reference's value, for a case that compares one number. Shown beside the difference. |
| `peers` | Other implementations' errors against the same reference, as `{"statsmodels": 2.5e-16}`. |

Every case is listed. A failed or skipped case is never dropped or counted as
a pass.

### Example

```json
{
  "title": "OLS: agreement with R",
  "label": "OLS",
  "kind": "reference",
  "checked": "fit_ols() coefficients, standard errors, residual SD, and R² on the same inputs as R's lm().",
  "reference": "R 4.6.1 lm()",
  "versions": {"R": "4.6.1", "statsmodels": "0.15.0", "PHP": "8.5.10"},
  "inputs": [{"name": "simulated_housing", "detail": "1,000 rows, seed 20261011", "url": "docs/data/housing.csv"}],
  "comparison": [{"id": "fixed", "kind": "tolerance", "atol": 1e-9, "rtol": 1e-7}],
  "cases": [
    {"name": "simulated_housing", "quantity": "coefficients", "status": "pass", "n_compared": 4, "max_abs_error": 3.98e-13, "peers": {"statsmodels": 1.71e-13}},
    {"name": "simulated_housing", "quantity": "R²", "status": "pass", "actual": 0.71834, "expected": 0.71834, "max_abs_error": 1.1e-16},
    {"name": "rank_deficient", "quantity": "coefficients", "status": "skipped", "reason": "R drops aliased columns; fit_ols() raises instead."}
  ],
  "known_differences": ["fit_ols() raises on a rank-deficient design; lm() reports NA coefficients."],
  "reproduce": "composer verify:ols",
  "source": "docs/verifications/ols-results.json",
  "environment": {"date": "2026-11-01", "commit": "1a2b3c4", "php": "8.5.12", "os": "Linux, Ubuntu 24.04.5 LTS (x86_64)"}
}
```

## Benchmark record

One record is one workload: a task at one or more sizes, run by one or more
implementations on one machine.

| Field | Meaning |
| --- | --- |
| `title` (required) | Workload heading. |
| `label` | Short sidebar label. Defaults to `title` up to its first colon. |
| `description` | The task and what each implementation does. |
| `workload` | Task and input size in one line. |
| `dataset` | `{"rows", "bytes", "sha256", ...}` of the input. |
| `check` | Evidence that the outputs are equivalent, such as `{"max_rel_difference": 4.3e-14}`. |
| `implementations` | `{"phit": "phit 0.4.3, Rust extension", "phit-polyfill": "plain PHP"}`: what each row label runs, with enabled extensions. |
| `measure`, `unit` (required), `statistic` | The time measure ("Elapsed time"), its unit (`ms` or `s`), and the statistic over repetitions (`median`). |
| `method` | Timing boundaries: where the clock starts and stops, and what is excluded. |
| `time_spent_in` | Where the measured time is spent: "the PHP process", "CmdStan's executable, launched by the PHP wrapper", or "the browser". |
| `memory_measure` | The exact metric in `peak_memory_mib` (below). Required when any row has `peak_memory_mib`. |
| `memory_method` | How memory was sampled, such as "RssAnon from /proc, every millisecond, by bench/peakmem.c". |
| `environment` | `cpu`, `cores`, `memory_gb`, `os`, `php`, `versions` (a map), `threads` (a number or a map by implementation), `cache` (`warm` or `cold`), `repeats`, `warmups`, `date`, `commit`. |
| `rows` (required) | The measurements (below). |
| `code`, `languages` | Each implementation's code, keyed by implementation (or by case, then implementation), and its highlight language. |
| `reproduce` | Command, or list of commands, from the repository root. |
| `source` | Repository path of the benchmark script or raw results. |
| `group` | Sidebar group heading. |

### Memory metrics

Each metric has its own field and its own label. The site never draws two
metrics in one chart or under a bare "Memory" heading.

| Field | Metric |
| --- | --- |
| `peak_memory_mib` | The metric `memory_measure` names: `peak anonymous memory` (Linux RssAnon peak) or `peak resident set size`. |
| `peak_rss_mib` | Always the peak resident set size, file pages included. |
| `php_peak_mib` | PHP's allocator peak, `memory_get_peak_usage(true)`. PHP rows only. |
| `child_peak_rss_mib` | Peak resident set size of child processes, such as CmdStan's chains, when measured. |

Only current Linux runs belong in records. `peak memory footprint` (macOS)
records are not shown.

### Rows

`case`, `implementation`, and `value` are required. `reps` lists each
repetition; `reps_peak_memory_mib` and `reps_peak_rss_mib` likewise. A
measurement that was not taken is `null` or absent and shows as "not
measured". `note` explains a value, such as "below the 1 ms timer
resolution".

### Example

```json
{
  "title": "OLS workload: phit, phit-polyfill, R, and statsmodels",
  "label": "OLS",
  "description": "Fit mpg ~ wt + hp on 1,000,000 simulated rows and return the coefficient table.",
  "workload": "1,000,000 rows, 3 coefficients",
  "implementations": {"phit": "phit 0.4.3 extension", "phit-polyfill": "phit-polyfill 0.4.3, plain PHP", "R": "lm()", "statsmodels": "ols()"},
  "measure": "Elapsed time", "unit": "ms", "statistic": "median",
  "method": "From the fit call to holding the coefficient table. Reading the CSV and starting the runtime are excluded.",
  "time_spent_in": "the measured process",
  "memory_measure": "peak anonymous memory",
  "memory_method": "RssAnon from /proc, sampled every millisecond by bench/peakmem.c",
  "check": {"max_abs_coefficient_difference": 3.1e-13},
  "environment": {"cpu": "AMD Ryzen 9 9955HX 16-Core Processor", "cores": 2, "memory_gb": 8, "os": "Linux, Ubuntu 24.04.5 LTS (x86_64)", "php": "8.5.12", "versions": {"R": "4.6.2", "statsmodels": "0.15.0"}, "threads": 1, "cache": "warm", "repeats": 7, "warmups": 1, "date": "2026-11-01", "commit": "7ddfe1c"},
  "rows": [
    {"case": "1,000,000 rows", "implementation": "phit", "value": 41.2, "reps": [41.0, 41.2, 41.5], "peak_memory_mib": 96.1, "peak_rss_mib": 120.4, "php_peak_mib": 2.0},
    {"case": "1,000,000 rows", "implementation": "statsmodels", "value": 88.0, "reps": [87.1, 88.0, 90.2], "peak_memory_mib": 310.5, "peak_rss_mib": 352.0, "php_peak_mib": null}
  ],
  "reproduce": "bench/run ols",
  "source": "bench/ols.php"
}
```
