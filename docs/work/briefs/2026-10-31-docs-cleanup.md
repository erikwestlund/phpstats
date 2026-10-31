# Documentation cleanup, first pass (2026-10-31)

Erik's brief, split by repository. The coordinator is the phpstats.org session
(`phpstats-org-ff`). Each package session owns its own repository and reports
back to the coordinator.

## Rules for every repository

- Read the repository's CLAUDE.md and instructions before editing.
- Inspect the implementation, composer.json, releases on Packagist and GitHub,
  and tests before stating a fact. Do not present an unchecked command as
  tested.
- House style: `/Users/erik/Projects/phpstats.org/.claude/skills/phpstats-style/SKILL.md`
  and the `writing` skill. Plain, factual prose. No marketing, no leads that
  restate a title, no advice such as "Use PHP 8.5", no "Where to go next"
  filler, no self-congratulatory verification language. Write requirements as
  plain statements or plain lists. Never tack a qualifier on with a comma
  ("Composer, for the PHP libraries"; "The phit extension, optional, for X").
- User-facing "Vignettes" becomes "Examples": headings, navigation, cards,
  breadcrumbs, link text. Keep example content and selection as they are;
  Erik curates examples separately. Repair a broken example or link, but do
  not remove, merge, or reselect examples.
- Every example that uses the pipe operator `|>` shows a method-chain
  equivalent, labeled "PHP 8.5+" and "PHP 8.3–8.4".
- Ordinary-user installation is the released package: `composer require
  phpstats/<pkg>` or `pie install phpstats/<ext>`. Remove VCS repositories,
  `dev-main`, and "not on Packagist yet" from user paths. Keep contributor
  builds in a Contributing or Development section.
- PHP support: state what composer.json allows. phit and phpolars allow
  `>=8.3 <8.6`, so say 8.3, 8.4, and 8.5, not "8.3 and later". Separate
  supported versions, versions CI tests, and the 8.5 pipe syntax.
- Move proposals, plans, release assessments, agent or Polyscope workflow
  notes, and contributor instructions out of the user documentation path
  (contributor docs, dated history, or an issue).
- Remove old names and hosts (php-stats, erikwestlund GitHub Pages, personal
  paths such as /Users/erik) except where a migration note or historical
  evidence needs them.
- Preserve benchmark and verification evidence with its original version and
  date. Never relabel a benchmark without rerunning it.
- Render docs with the repository's own workflow (for example `bin/render-docs`)
  and commit the rendered HTML, because phpstats.org imports committed HTML
  and never runs Quarto. A docs nav entry without rendered HTML makes the
  site sync fail.
- Do not tag or publish releases. Do not deploy. Commit and push to main when
  your checks pass, then report the commit to the coordinator.

## Package structure

Use the categories a package needs, in this order, with short navigation:
Overview, Getting started, Guides, Reference, Examples, Compatibility and
limitations, Benchmarks and verification, Contributing. The overview explains
the package to a PHP developer who does not know R, the tidyverse, Stan, or
Polars. Keep migration guides ("Coming from dplyr") for readers who do.

Give each page one job. Cut repetition between the README, the docs index,
the site overview, and the family description; link to the detailed page.

For public APIs document, where it applies: namespace and signature,
parameters with types, defaults, and accepted values, return type and shape,
mutation, missing values, grouping, ordering, recycling, exceptions, backend
or optional-dependency limits, a short example with output, and availability.

## site/site.json contract

phpstats.org's Get started page and each package overview read these keys.
Fill every one that applies, from facts you checked.

```json
{
  "install": ["composer require phpstats/phibble"],
  "php": ["8.3", "8.4", "8.5"],
  "requires": ["PDO extension"],
  "optional": [
    {"name": "phit extension", "for": "the native CSV reader"},
    {"name": "illuminate/database", "for": "Laravel query builder and collection input"}
  ],
  "build": ["Rust toolchain", "libclang"],
  "platforms": ["Linux", "macOS"],
  "verify": {"command": "php -r 'echo phpversion(\"phit\"), PHP_EOL;'", "output": "0.3.0"},
  "quickstart": "getting-started"
}
```

- `install`: the commands an ordinary user runs, in order. For juphyter this
  includes kernel registration and a check.
- `php`: the PHP minor versions composer.json allows.
- `requires`: what must already be installed, as plain names. Leave PHP out;
  `php` covers it. List what Composer installs automatically only if a reader
  would otherwise not know (phplyr brings phibble).
- `optional`: each optional dependency and the feature it enables.
- `build`: source-build prerequisites only. Leave empty when nothing builds.
- `platforms`: supported operating systems, and PHP build type for extensions
  ("NTS (non-thread-safe) PHP").
- `verify`: for extensions and juphyter, a command that checks the install
  and its expected output. Note CLI versus PHP-FPM where it matters.
- `quickstart`: the docs page path of the package's getting-started page.

## Per-package scope

Each section below is Erik's text for that package. Treat the listed problems
as the starting point and continue through every page.

### Confirmed stale claims to recheck and fix

1. Pages that still call published packages unpublished: phpolars
   getting-started and deployment, phit deployment, CmdStanPHP installation,
   juphyter overview, README, installation, and Composer suggest text.
2. Open-ended PHP support claims that contradict composer.json.
3. juphyter's install stops at `composer global require`; add kernel
   installation, verification, Python and Jupyter requirements, and platforms.
4. juphyter has no site content on phpstats.org although its repository has
   installation, getting-started, JupyterLab, Quarto, and limitations docs.
5. Prose that attributes phibble's `Phibble\Polars\PolarsTable` to phpolars.
   The adapter belongs to phibble; phpolars supplies the native extension.
6. phiz's "Plan: CmdStanPHP figures on phiz" is in the public docs nav.
7. phit's docs index carries README-maintenance and add-a-document
   instructions, and its development page carries Polyscope workflow details.
8. juphyter's README and installation guide disagree on PHP extensions. Check
   runtime behavior and the doctor checks; state which extensions are
   required and which enable a capability.
9. Old names and hosts (see the rules above).

### phibble

Replace the overview's single long paragraph with a short introduction and a
few sections. Audit: overview/index, getting started (autoloading, imports,
build a small table, filter, mutate, group, summarize, inspect; show
immutability and missing values), coming from tibble (check every mapping
against current signatures; indexing, recycling, factors, null), types
(inference, casts, factors, dates, list-columns, model data; ambiguous and
invalid input), reading and writing (CSV defaults, type specs, parse
failures, quoting, locale, compression, chunking, JSON, database adapters),
expressions (start from a practical computation; column references, literal
strings, missing values, grouping), SQL tables (open, deferred execution,
query inspection, collect, fallback, dialects, joins, differences from
memory), Laravel (`toDataFrame()` materializes, `phibble()` keeps work in the
database; reproducible setup), backends (adapter ownership, selection,
transfer, fallback, strict mode, memory), table contract (advanced; reconcile
the implementation list with the source), development (contributors). Avoid
"same result everywhere" claims unless they account for fallback, ordering,
types, and tolerances.

### phplyr

Explain how phibble's methods relate to phplyr's functions and why one would
install phplyr. Audit overview and getting started (imports, loading data,
chain return types, `get()`, terminal operations, pipe syntax, PHP versions),
coming from dplyr (string column names, closures, expressions, naming,
option prefixes, unsupported behavior), gallery and porting an R script
(prerequisites, dataset links, interpretation, dependencies; leave selection
to Erik; `coming-from-dplyr` currently lacks method-chain versions), single
table, joins and set operations (keys, duplicate matches, row multiplication,
unmatched values, ordering, relationship checks), grouping (`_groups`, `_by`,
unused levels, rowwise, ordering), vector helpers, strings and dates (regex
versus literal, encodings, time zones, backend limits), selectors and
across, tidyr, janitor (now rendered and on the site; check transliteration
dependencies), models (actual phit capabilities, formulas, results, verified
polyfill behavior), databases, performance and development (move contributor
detail out of the learning path). Document collisions with PHP built-ins and
the difference between phplyr's option conventions and Phibble expressions.

### phpolars

Give a clear choice between the direct Phpolars API and phibble/phplyr over
`PolarsTable`. Qualify Python-compatibility claims to the tested cases. Audit
overview, getting started (released install, extension loading, imports, a
small query, output, lazy execution), coming from py-polars, phibble/phplyr
integration (ownership, versions, collection, copying, fallback, strict mode,
missing values), deployment (prebuilt targets versus source builds; keep
verified ABI, NTS, architecture, glibc/musl, CLI/FPM, worker restart, and
thread-pool guidance), DataFrame/LazyFrame reference (eager versus lazy),
expressions reference (categories with anchors; parameters, defaults,
returns, nulls, examples), Series (scalar versus Series results), I/O (a
format matrix for read, scan, write, sink), types (integer precision, decimal
strings, time units, time zones, null versus NaN, lists, structs),
performance (hardware, software, threads, data, timing boundaries,
repetitions, statistic; explain "fastest of five"), development. Keep the
native-memory warning visible: `memory_get_usage()` and `memory_limit` do not
see all native allocations.

### phit and phit-polyfill

The largest restructure. The docs open as "Project documentation" and give
assessments, verification records, build details, and numerical internals
too much prominence. Build an application-user path: installation and
extension verification; choosing the extension or the polyfill; a first
formula model; reading coefficients, uncertainty, fit statistics, and
diagnostics; prediction on new data; linear models; binomial/logistic,
probit, and Poisson; mixed models (exact supported structures and fitting
methods); missing values and sample accounting; distributions, descriptive
statistics, and seeded sampling; matrix APIs. Audit index (remove contributor
instructions and release history from the introduction), contracts (split
into navigable reference sections; keep shapes, ordering, errors), formulas,
missing data, distributions/descriptive/RNG, CSV input (low-level reader
versus phibble's DataFrame), posterior-draw kernels (callers, dependencies),
deployment/development (separate user install from contributor builds and
release engineering), design philosophy (advanced), v0.1/v0.2 assessments
(dated history), math notebooks (advanced path), verification records and
compatibility ledger (keep versions, tolerances, scope, failures), benchmarks
(historical versus current), figure conventions, licensing, data provenance,
docs tooling (contributor or evidence sections).

phit-polyfill: verify installation, bootstrap and autoload, coexistence with
the extension, the functions available, numerical differences, performance
limits, and version compatibility. Check formulas, mixed models, CSV, and
posterior kernels one by one, and make only claims the source and tests
support. The phit session owns phit's docs; the phit-polyfill session owns
the polyfill repository and gives the phit session the facts for the
"extension or polyfill" guide.

### phiz

A complete copyable first chart with imports and an explicit way to save or
view it. Audit coming from ggplot2 (Vega-Lite differences), data and fields,
geoms (by task), scales/coordinates/labels, facets/composition, themes
(output, precedence), interaction (browser versus static export), statistics
(result schemas; verified phit/polyfill requirements; drop php-stats
terminology), rendering (specification, HTML, embedding, static export;
assets; vl-convert; export errors), Laravel/Livewire, dashboard
(prerequisites, source links), large data (qualified row counts;
aggregation, downsampling), API reference (signatures, defaults, returns,
errors, links), design/development (contributors). Move the CmdStanPHP
figures plan out of product docs. `get-started`, `dashboard`, and `ggplot2`
currently use pipes without method-chain versions.

### CmdStanPHP

Fix installation first: released Composer install, PHP requirements,
`proc_open`, CmdStan and toolchain setup, path selection, verification, first
compilation. Separate optional FFI, phit kernels, table interop, and figure
export by feature. Supported operating systems; tested native Windows versus
WSL/Linux. Overview: the Bernoulli example includes or links the Stan program
and says where to run it. Split the inference guide (MCMC,
optimization/Laplace, ADVI/Pathfinder, generated quantities, diagnostics, log
density, LOO/WAIC, figures, persistence). Explain assumptions, outputs,
convergence diagnostics, warnings, and common failures before internals. API
reference: signatures, defaults, result objects, file lifetimes, timeouts,
parallel execution, compilation caching, stale executables, errors.
Compatibility contract: keep the CmdStanR mapping and gaps; separate matching
names from matching behavior. Move testing and GitHub Pages procedures to
contributor docs. Check every example's Stan file, data, output directory,
executable, and optional capability.

### juphyter

Make its docs importable by phpstats.org (`site/site.json` with the contract
above, and rendered docs the site's DocsImporter can read; compare with
phplyr's `site/` and `docs/`). Simplest successful path first. Audit overview
(stale Packagist claim), installation (global versus project install, Composer
bin path, kernel registration, Python/Jupyter/ipykernel versions, optional
environment, doctor checks; mark shell startup-file edits and offer a
temporary PATH), getting started (first cell, output, session state, imports,
exceptions, restart), JupyterLab (kernel selection, autoloading, tables,
plots, fallback), Quarto (front matter, PHP cells, rendering, interpreter,
options, daemon, freeze, formats), limitations (redeclaration, namespaces,
strict types, cross-cell references, interruption; remedies), README and
Composer metadata. Move Python interpreter resolution and editor
troubleshooting into a dedicated section. Replace personal paths with
labeled examples. Qualify renderer compatibility by tested versions.

## What each session reports back

Send the coordinator, by message, when done:

1. The pushed commit SHA on main.
2. A page inventory as a Markdown table: public URL or docs path, source
   file, reader and purpose, released / main-only / historical / proposal,
   disposition (keep, rewrite, merge, move, archive, redirect), problems
   found, how claims were verified.
3. The factual corrections and structural changes, briefly.
4. What ran, passed, failed, or stayed unverified, and why.
5. Open product decisions for Erik.

Ask the coordinator before changing anything another repository owns.
