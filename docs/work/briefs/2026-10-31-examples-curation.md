# Examples curation (2026-10-31)

The site's Examples section shows the most useful functionality of each
package in short, runnable examples. Each example has one owning package and
one canonical page in that package's docs, in a nav section named
"Examples". The site's shared index (`/examples`) and each package's Examples
tab link to those pages. They don't copy the content.

## The curated set

| Owner | Path | Title | Replaces |
|---|---|---|---|
| phibble | `examples/read-a-csv` | Read a CSV file into a typed table | site `csv-reader` |
| phibble | `examples/database-table` | Query a database table | site `sql-pipeline` |
| phplyr | `examples/filter-and-mutate` | Filter rows and add columns | |
| phplyr | `examples/group-and-summarize` | Summarize groups | |
| phplyr | `examples/join` | Join two tables | |
| phplyr | `examples/pivot` | Reshape with pivot_longer and pivot_wider | phplyr `reshaping` |
| phpolars | `examples/expressions` | Aggregate with expressions | site `polars-pipeline` |
| phpolars | `examples/lazy-csv` | Query a large CSV file lazily | phpolars `lazy-queries` |
| phpolars | `examples/time-series` | Rolling windows and as-of joins | phpolars `time-series` |
| phit | `examples/linear-model` | Fit a linear model and predict | site `simulated-rents`, phit `rent-model` |
| phit | `examples/glm` | Fit logistic and Poisson models | site `glm-models` |
| phit | `examples/mixed-model` | Fit a mixed model | site `school-scores`, `school-shrinkage`, `random-effects`, phit `school-effects` |
| phit | `examples/distributions` | Densities, quantiles, and random draws | phit `distributions` |
| phiz | `examples/layers` | Layer points and a fitted line | |
| phiz | `examples/facets` | Compare groups with facets | |
| phiz | `examples/save` | Save a chart to a file | |
| CmdStanPHP | `examples/sample` | Compile, sample, and summarize a Stan model | site `bayesian-schools` |
| juphyter | `examples/notebook` | Show a table and a chart in a notebook | |

Kept outside the index:

- phplyr `port-r-script` stays a guide.
- phplyr `backends`, phpolars `phplyr-on-polars`, and site `polars-pipeline`
  timing panels move to benchmarks. The pipeline itself is phpolars' existing
  "phibble and phplyr" guide.
- Site `rent-dashboard` becomes the advanced example linked from phiz's
  "Dashboards with Livewire" guide.
- phiz's and phplyr's Gallery pages and CmdStanPHP's existing Examples page
  stay, under the same Examples nav section, after the curated pages.

## Page format

Each example page (`docs/examples/{slug}.qmd`, rendered to `.html` the usual
way) has, in this order:

1. A task-based title.
2. One sentence on what it shows.
3. Prerequisites in a line, or a link to the installation page.
4. Small input data, inline or as a linked file. A reshape shows the input
   table, the operation, and the output.
5. Complete code: `<?php`, `require 'vendor/autoload.php';`, every `use`, and
   any setup. The pipe version first, with the method chain as the second tab
   where the package's docs already do that.
6. The output, from running that exact code.
7. A short explanation and links to the reference pages it uses.

No hidden prelude, no timing, no dashboard controls. Keep statistical
interpretation short and accurate. One operation per example: split unrelated
operations into separate pages.

## site.json

Add an `examples` list in nav order:

```json
"examples": [
  {"path": "examples/pivot", "title": "Reshape with pivot_longer and pivot_wider",
   "description": "Turns a wide table of yearly sales into one row per year and back.",
   "also": ["phibble"]}
]
```

- `description`: one sentence, shown in the index.
- `also`: optional, other packages whose Examples tab should cross-link it.
- Remove `vignettes` from site.json once its scripts are replaced, and keep
  the scripts' content in the new pages.

The docs nav (`docs/_quarto.yml` sidebar) puts these pages under a section
named "Examples". The sync checks that every `examples[].path` is a page in
that section.

## Interactive versions

The site keeps `school-shrinkage` and `bayesian-schools` as live pages,
because their controls show how the estimates move. phit's mixed-model
example and CmdStanPHP's sample example may link them as "Interactive
version". The site supplies the URL.
