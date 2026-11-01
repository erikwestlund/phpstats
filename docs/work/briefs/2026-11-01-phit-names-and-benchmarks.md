# phit model names and workload benchmarks (2026-11-01)

## 1. Rename (approved: two-release plan)

| Old | New | Input |
|---|---|---|
| `fit_lm` | `fit_ols` | formula |
| `fit_glm` | `fit_generalized_linear` | formula |
| `fit_lmer` | `fit_mixed_gaussian` | formula |
| `fit_glmer` | `fit_mixed_logistic` | formula |
| `fit_ols` (matrix) | `fit_ols_matrix` | matrix |
| `fit_logistic` | `fit_logistic_matrix` | matrix |
| `fit_probit` | `fit_probit_matrix` | matrix |
| `fit_poisson` | `fit_poisson_matrix` | matrix |
| `fit_mixed` | `fit_mixed_gaussian_matrix` | matrix |
| `fit_mixed_intercept` | `fit_mixed_gaussian_intercept_matrix` | matrix |
| `fit_logistic_mixed` | `fit_mixed_logistic_matrix` | matrix |

Unchanged: `model_matrix`, `predict`, and phplyr's `lm()`, `glm()`, `lmer()`,
`glmer()`. No numerical behavior changes.

- `fit_mixed_logistic` has no `family`/`link` arguments: it fits binomial logit
  with exactly one random intercept, and its docs say so. The deprecated
  `fit_glmer` alias still accepts them.
- `fit_ols` and `fit_ols_matrix` docs say that `weights` performs weighted
  least squares.

**phit 0.4 (transition release).** New names are the documented API. Old names
are deprecated aliases that raise `E_USER_DEPRECATED` naming the replacement,
except `fit_ols`, which dispatches on its first argument: a string (or named
`formula:`/`data:`) is the formula fit; an array (or named `x:`/`y:`) runs the
old matrix fit with a deprecation pointing to `fit_ols_matrix()`. A call never
changes meaning. Stubs and docs show both `fit_ols` forms until 0.5.
CHANGELOG has a migration table.

**phit 0.5 (later, not now).** An array passed to `fit_ols` raises a TypeError
naming `fit_ols_matrix()`; the other aliases are removed.

Sessions:

- **phit:** extension, stubs, tests (including one per alias and both
  `fit_ols` dispatch paths, and a test that numbers are identical to the old
  names), docs, notebooks, verification scripts, examples. Release 0.4.0.
- **phit-polyfill:** the same names, aliases, and `fit_ols` dispatch; parity
  tests; README and docs. Release in step with phit 0.4.0.
- **phplyr:** `lm`, `glm`, `lmer`, `glmer` call the new names when the
  function exists and fall back to the old ones, so phplyr works with phit 0.3
  and 0.4. Docs that name phit functions use the new names. Release a patch.
- **phibble:** tests use `fit_ols_matrix` when it exists.
- **phpstats.org (me):** switches to the new names after phit 0.4 is
  installed on the server; production keeps working on 0.3 meanwhile.

## 2. Model workload benchmarks (phit)

Two records for the landing page's "Performant, powerful, and flexible"
section, each comparing the **phit extension, phit-polyfill, R, and Python
statsmodels** on one workload:

- `site/benchmarks/ols-workload.json`: OLS, about 1,000,000 rows and 10
  predictors (choose a size where every implementation finishes in under a
  minute per run). R `lm()`, statsmodels `OLS` (QR or pinv: record which),
  phit `fit_ols`, polyfill `fit_ols`.
- `site/benchmarks/mixed-workload.json`: Gaussian random-intercept model,
  REML in every implementation (lme4 `lmer`, statsmodels `MixedLM(reml=True)`,
  phit and polyfill `fit_mixed_gaussian`), sized so the polyfill finishes.
  Record each optimizer and its convergence tolerance, and note where they
  differ materially.

Method, the same for every implementation:

- Each run is a fresh process: one warm-up of each, then 7 alternating runs.
- Time: from data already in memory in the runtime's usual structure (PHP
  arrays of columns, an R data.frame, a pandas DataFrame) to holding the fit's
  coefficients and standard errors. Data loading is outside the timing.
- Memory: peak resident set size of the process (PHP `getrusage()`,
  Python `resource.getrusage`, R via `ps`/`/usr/bin/time -l`), plus each
  runtime's idle baseline in the record.
- Check that coefficients agree (record the largest difference); this is not a
  verification record, just a sanity check.

Record format (the landing page reads this; the Benchmarks tab reads `case`,
`implementation`, `value`, `reps`):

```json
{
  "title": "...", "description": "...",
  "measure": "Elapsed time", "unit": "ms", "statistic": "median",
  "workload": "OLS, 1,000,000 rows, 10 predictors",
  "environment": {"cpu": "", "cores": 0, "memory_gb": 0, "os": "", "php": "",
    "versions": {"phit": "", "phit-polyfill": "", "R": "", "lme4": "", "Python": "", "statsmodels": "", "numpy": ""},
    "threads": "BLAS/thread settings per runtime", "repeats": 7, "date": "", "commit": ""},
  "method": "timing boundary, memory method, convergence settings",
  "check": {"max_abs_coefficient_difference": 0.0},
  "code": {"phit": "...", "phit-polyfill": "...", "R": "...", "statsmodels": "..."},
  "languages": {"phit": "php", "phit-polyfill": "php", "R": "r", "statsmodels": "python"},
  "baseline_memory_mib": {"phit": 0, "...": 0},
  "rows": [{"case": "...", "implementation": "phit", "value": 0, "reps": [],
    "peak_memory_mib": 0, "reps_peak_memory_mib": []}]
}
```

`code` holds the timed part of each script verbatim, as the reader would write
it. Keep the scripts under `bench/` with a README, list both records in
site.json `benchmarks`, commit, push.
