# Documentation inventory and dispositions (2026-10-31)

The first-pass cleanup in `briefs/2026-10-31-docs-cleanup.md`. This file
covers phpstats.org's own pages. Each package's inventory is appended below as
its session reports.

Every public URL below has a path-mode twin under `/{package}` on preview
hosts.

## phpstats.org

| URL | Source | Reader and purpose | State | Disposition | Problems found | Verified by |
|---|---|---|---|---|---|---|
| `/` | `resources/views/home.blade.php`, `resources/pages/home/*.md`, `config/phpstats.php` explorer | Visitor: what the family does, one example per package | Released | Keep | phpolars bullet and relations gave `PolarsTable` to phpolars; "10 to 100 times faster" and "a million rows takes milliseconds" were unsupported; juphyter bullets carried marketing lines; phit "fits models fastest" | phibble namespace `Phibble\Polars\PolarsTable`; phplyr `benchmarks/backends.json` (1.3–15×, 51 ms at 1M rows, M5 Ultra, 2026-10-18) |
| `/get-started` | `resources/pages/get-started.md`, `components/{package-chooser,install,requirements}` | New user: which package, what it needs, how to install and check | Released | Rewritten | VCS and `dev-main` installs; open-ended PHP claims; juphyter install incomplete; no task chooser; Markdown code blocks drew a second box | Packagist versions; composer.json `php` constraints; PIE `download` resolved phit v0.3.0 and phpolars v0.2.0; install lines now come from each package's `site.json` |
| `/about` | `resources/pages/about.md` | Visitor: what PHPstats is, packages, design, license | Released | Rewritten | Lead restated the title; "This site" described repo internals | composer.json licenses (all MIT) |
| `/csv-options` | `resources/pages/csv-options.md` | Reader mapping readr CSV options to Polars ones | Released | Keep, off the menu | Only the sitemap links it | `RootPagesTest` option lists |
| `/vignettes` (titled Examples) | `resources/views/vignette-index.blade.php` | Index of every example | Released | Keep; Erik curates content | "Vignettes" labels | Route and title tests |
| `{package}.phpstats.org/` | `resources/views/package/overview.blade.php`, `config/projects.php`, synced `site.json` | Package overview | Released | Keep | phpolars "targets PHP 8.5"; juphyter "Nothing in the family is on Packagist"; phit-polyfill paragraphs contradict phpstats.php (pending phit-polyfill facts) | composer.json; Packagist |
| `{package}.phpstats.org/docs/*` | imported from each package's committed Quarto HTML at the pin in `config/phpstats.php` | Package docs | Pinned main | Package sessions own | See package sections | `site:sync`, `DocsTest`, `ProseTest` |
| `{package}.phpstats.org/vignettes[/slug]` (Examples) | `resources/views/vignettes/*`, synced `site/vignettes` | Worked examples | Pinned main | Keep; Erik curates | Labels | `VignettesTest` |
| `{package}.phpstats.org/benchmarks`, `/verification` | synced `site/benchmarks`, `site/verifications` | Evidence | Historical, dated | Keep | none found | `DocsTest` renders every file |
| `phit-polyfill.phpstats.org/*` | route redirect | — | — | Redirect to phit (301) | — | `PackagePagesTest` |
| `/robots.txt`, `/sitemap.xml` | `RobotsController`, `SitemapController` | Crawlers | — | Keep | Cloudflare's managed robots.txt hides the `Sitemap:` line in production | `SeoTest`; curl of origin and public |

## Packages

Appended as each session reports.

### phibble (f0c1e8b, reported by phibble-d5)

Every page documents released 0.5.0 behavior on main.

| Docs path | Source | Reader and purpose | Disposition | Problems found | Verified by |
|---|---|---|---|---|---|
| index | docs/index.qmd | New user | Rewritten | One long intro; "phpolars is an optional Polars-backed table"; "cmdstanphp will accept"; filler; open-ended PHP | composer.json, CI matrix, CmdStanPHP suggest, phiz `Plot::of` |
| getting-started | getting-started.qmd | First session | Rewritten in part | No autoloader; no missing-values example; old tab labels | New `missing.php` example run through `bin/docs-examples` |
| coming-from-tibble | coming-from-tibble.qmd | R users | Keep, one fix | String functions are regex by default, with `fixed()` | Reflection on mapped signatures; runtime checks |
| types | types.qmd | Types and casts | Keep | none | Runtime checks |
| io | io.qmd | CSV, JSON, PDO, chunks | Keep | none | IoTest, CsvChunkTest |
| expr | expr.qmd | Expressions | Keep, one fix | "phpolars maps it to Polars"; unqualified same-result claim | PolarsTable source |
| sql | sql.qmd | SqlTable | Keep, one fix | "Results match DataFrame" ignored known differences | SqlTest |
| laravel | laravel.qmd | Laravel users | Rewritten in part | Broken phplyr example (`na_rm:` for `naRm:`, missing `col()` import); no setup | Example run on SQLite, chain and pipe |
| backends | backends.qmd | Choosing a backend | Rewritten in part | PolarsTable ownership; same-result claim; comments in code | SqlReviewTest, Polars tests (9-place rounding) |
| contract | contract.qmd | Backend authors | Keep, fixed | Wrong implementation list; `groupRows()` missing | Diff against `src/Contracts/Table.php` |
| development | development.qmd | Contributors | Keep | CI scope unstated | `.github/workflows/tests.yml` |
| README | README.md | GitHub | Edited | Family list; same-result claim | Example run |

Checks: `bin/render-docs` rendered 47 examples (only the new one changed);
phpcs passed; Pest on 8.5 with PostgreSQL, MySQL, phit, and php_polars: 907
passed, 1 skipped. Not run: 8.3, 8.4; browser check.

### phit-polyfill (004c60a, reported by phit-polyfill-db)

Not imported by phpstats.org (a companion of phit). Its facts feed phit's
"extension or polyfill" guide and the site's phit overview.

| Docs path | Reader and purpose | Disposition | Problems found | Verified by |
|---|---|---|---|---|
| README.md | GitHub users | Rewritten | Version table wrong; "phit has no tagged release yet"; open-ended PHP; duplicated docs | Packagist; fresh `composer require`; README example run |
| site/docs/index | Overview, install, use | Rewritten | Stale release claim; advice; coexistence facts missing | Fresh install; bootstrap with and without the extension |
| site/docs/functions | Reference | Rewritten (additions) | Warning text quoted as exception text; no CSV, mixed-model, or posterior sections | Source; `formula-lme4-r.json`; RandomizedParityTest |
| site/docs/php-versions (now "Compatibility and limitations") | Compatibility | Rewritten | PHP 8.3 JIT default wrong; support versus CI not separated | `php -n` ini defaults; CI workflow; ACCURACY.md speed table (2026-10-16) |
| site/docs/how-it-works | Advanced | Keep, trimmed | Duplicated differences | Source |
| site/docs/development | Contributors | Keep | none | composer.json scripts |
| site/vignettes | Examples | Keep | "Only about speed" (CSV is extension-only) | Ran each with the 0.3.0 extension |
| site/site.json | Site contract | Rewritten | Missing contract keys; dangling benchmark/verification lists; pipes at line starts | Ran both example variants |
| ACCURACY.md, CHANGELOG.md, docs/notes | Evidence, history | Keep | One "X, not Y" sentence | — |
| composer.json | Packagist metadata | Rewritten | 0.1-era description; homepage now https://phit.phpstats.org | curl |

Checks: fresh install of v0.3.0; Pest on 8.5: 590 passed, 129 skipped; lint
clean; `composer validate`. Not run: benchmarks (kept with original dates),
Windows, an older-extension runtime check; CI on 004c60a was pending.

### phplyr (814d0f7, reported by phplyr-f4)

All pages are released content at `docs/<path>.qmd`.

| Page | Reader and purpose | Disposition | Problems found | Verified by |
|---|---|---|---|---|
| index | PHP developer | Rewritten | VCS and dev-main install; "not on Packagist yet"; open-ended PHP; filler; no phibble-versus-phplyr explanation | Packagist; composer.json; phibble source |
| getting-started | First use | Rewritten | Built-in collisions listed 3 of 19; old tab labels; no chain return types | `get_defined_functions()`; `bin/chain-docblock`; runs on 8.5 |
| coming-from-dplyr | dplyr users | Rewritten | Pipe-only `_by` example; listed six existing functions as unsupported; miscount | src grep; scripted checks |
| port-r-script | dplyr users | Keep, prerequisites and data link added | — | File checks |
| reference/single-table, two-table, grouping, vectors, tidyselect, tidyr, databases | Reference | Keep, fixed | `where()` types; join row multiplication and order; unqualified fallback claim | Scripted join and order checks; src |
| reference/strings-dates | Reference | Keep, added | No encoding or time-zone notes | Scripted UTF-8 and UTC checks |
| reference/janitor | Reference | Keep | — | janitor tests |
| reference/models | Reference | Rewritten | No result shape or polyfill guidance | phit result keys; polyfill run (lm, glm within 1e-12 of R) |
| gallery | Examples | Keep, notes the phit need | — | Data identical to phit's |
| compatibility | Limits | New | — | composer.json; CI; scripted checks |
| performance | Benchmarks | Keep unchanged | — | Versions and dates preserved |
| development | Contributors | Keep, updated | R script list; tab labels | File checks |
| README | GitHub | Rewritten short | "phpolars' PolarsTable"; VCS install | Examples run on 8.5 and 8.4 |

Checks: `bin/test` on 8.3, 8.4, 8.5; lint; `chain-docblock --check`;
`bin/render-docs` with chain/pipe parity; CI run 41211872172 green.
Verification files regenerated at f7edd6e on 2026-10-31 except polars.json.
Not run: Windows; PolarsTable parity; the polyfill in CI.

### phit (10e856c, reported by phit-5b)

Paths under `docs/`; site URL `phit.phpstats.org/docs/<path>`. Released
v0.3.0 unless marked.

| Path | Reader and purpose | Disposition | Problems found | Verified by |
|---|---|---|---|---|
| index | Overview | Rewritten | "Project documentation"; README-maintenance and add-a-document instructions; release history | Example run on v0.3.0 |
| installation (new) | Install and verify | New | Old path said "not on Packagist" | `pie install` in php:8.4-cli-bookworm (aarch64); `pie build` on macOS arm64, PHP 8.5.10; release tarball sha256 |
| extension-or-polyfill (new) | Choosing | New | — | Facts from phit-polyfill 004c60a |
| getting-started (new) | First model, results, prediction, missing values | New | — | Outputs run on v0.3.0 |
| formulas | Guide | Keep, fixed | Example used nonexistent columns | Calls run |
| models, mixed-models (new) | Guides | New | — | Outputs and errors run; schools and cbpp data |
| missing-data | Guide | Intro rewritten | "now defaults"; ignored formula fits | Contract text |
| distributions, descriptive, rng, draw-numerics, io | Guides | Small fixes | 0.3.0 samplers missing | Function count |
| deployment | FPM, Docker, manual build | Rewritten | "Not submitted to Packagist"; future-tense PIE | Workflows, composer.json |
| contracts → contracts, reference-models, reference-utilities | Reference | Split | 7,300 words on one page; maintainer notes | Old anchors kept; link checker |
| licensing, r-compatibility | Reference, compatibility | Keep | — | Not rechecked |
| verification | Evidence | Keep, fixed | Understated CI coverage | packaging.yml, Dockerfile |
| verifications/*, benchmarks/*, notebooks/*-verification, data/lmm | Evidence | Keep, relabeled | — | Not rerun |
| notebooks/*-math | Advanced | Keep under Mathematics | Authoring notes in the index | Moved to documentation |
| v0.1, v0.2 | History | Archived with dated callout | Read as current | git log dates |
| development, releasing (new), documentation | Contributors | Rewritten, moved | Polyscope text; user material in contributor page; stale release statement | release.yml; Polyscope notes moved to unrendered docs/notes |
| visualization, design-philosophy | Contributors | Moved | — | — |
| README | Repo entry | Rewritten | "8.3+"; "Packagist pending"; github.io links | Example run |

Checks: `scripts/check` (Rust tests, clippy, PHP suites, stubs, manifests,
`site-export --check`); full render; internal link check with zero broken
links or anchors. Not run: `pie install` on macOS; apt line on a host;
frozen notebooks. A recorded benchmark JSON keeps a /Users path as evidence.
The first push (b6ba8b8) failed the site sync on the default `data/`
exclusion; 10e856c declares an empty exclude list and lists every page.

### juphyter (d92f6b8, reported by juphyter-4f; first content pin)

Site URL `juphyter.phpstats.org/docs/<path>`. All pages released 0.1.0.

| Path | Reader and purpose | Disposition | Problems found | Verified by |
|---|---|---|---|---|
| index | PHP developer new to Jupyter or Quarto | Rewritten | Stale `composer require --dev`; pipe-only example; self-verification line | Cells executed; both tabs on phplyr 0.4.2, phibble 0.5 |
| install | Requirements, install, check | Rewritten | Wrong extensions; VCS and "not on Packagist"; stale doctor output; interpreter material mixed in; PHAR offered with none published | Full path on macOS (isolated HOME, PHP 8.5.10, Python 3.14) and Debian 13 (PHP 8.3.35, Python 3.13) from Packagist; real output quoted |
| get-started | First cell, state, imports, errors, restart | Rewritten | Project install first; filler; odd sample data | Cells executed |
| quarto | Front matter, cells, formats, daemon, freeze | Edited | Personal path; no tested version; contributor detail | Executed; Quarto 1.10.18 |
| jupyter | Kernel selection, completion, tables, plots | Edited | `vendor/bin` line; undated Vega-Lite claim | Claim dated 2026-10-19, phiz 0.3, Vega-Lite 5.6.1; not rerun |
| python (new) | Interpreter resolution, editors, venv | New | Personal paths | Source |
| limitations | Limits and remedies | Edited | Wrong Windows reason; missing remedies | Source |
| development (new) | Contributors | New | — | pest, lint, pytest |
| README | GitHub | Rewritten | VCS family install; duplicated troubleshooting | Same commands as install |

Required extensions per source: mbstring and tokenizer. pcntl is optional
(stop button). posix and sockets are unused. Checks: pest 762 passed, 15
skipped; pytest Protocol 23 and Quarto 25 passed; render executed then frozen
with no diff; CI green on d92f6b8; DocsImporter dry run 8 pages. Not run:
WSL; Python 3.10 and older; Vega-Lite claim on phiz 0.5. The site's tests now
treat juphyter as synced, and ProseTest allows "stack frame" on its
limitations page.

### phiz (c656ce1, reported by phiz-77)

All `docs/*.qmd`, released v0.5.0 behavior.

| Path | Reader and purpose | Disposition | Problems found | Verified by |
|---|---|---|---|---|
| index | New user | Rewritten | VCS and dev-main install; "8.3 or later"; "about 30 geoms"; erikwestlund links; assumed ggplot2 | Fresh `composer require` (v0.5.0); geom count by reflection (42) |
| get-started | First chart | Rewritten top; pipe tabbed | No complete script; pipe without chain | Script run on 8.3 and 8.4; headless screenshot; specs equal |
| ggplot2 | ggplot2 users | Keep; tabbed | Unlabeled pipe block | Both versions compiled equal |
| data, scales, facets, themes, interaction | Guides | Keep | none found | Read against source; tests |
| geoms | Guide | Keep | Not audited line by line | Spot checks |
| statistics | Guide | Rewritten in part | php-stats provenance; no phit requirement; false GLM interval explanation | Saved fits refit with phit-polyfill 0.3.0 (2e-14); GLM vs R |
| rendering | Export | Keep, one fix | Reproducibility claim missed jitter | Three renders differ |
| laravel | Blade, Livewire | Expanded | Config table incomplete; untested Livewire claims unmarked | config/phiz.php |
| dashboard | Livewire walkthrough | Expanded | No prerequisites; pipe-only | chain twin; specs compared |
| large-data | Large data | Qualified | Row-count guidance stated as fact; undated benchmark | Benchmark dated 2026-10-16, phiz 0.3.0 |
| reference | API reference | Rewritten (generated) | ~15 geoms missing; no signatures or exceptions | `scripts/docs-reference.php` from reflection |
| gallery (new .qmd) | Examples | New | Was skipped by the sync (no .qmd) | Generated from `examples/catalog.php` |
| design | Compatibility | Retitled "Design and compatibility" | "Computed in PHP" list stale | Compile/Geoms.php |
| development | Contributors | Expanded | Render workflow, twins, sitemap rule | Ran the workflow |
| cmdstanphp (plan) | Proposal | Moved to notes/, off the site | Proposal in public nav | — |
| README | GitHub | Rewritten short | VCS install; php-stats API; duplication | Examples compared |

Checks: Pest on 8.3, 8.4, 8.5 and CI; phpcs; `scripts/render-docs` with
vl-convert 1.9.0; read-only DocsImporter run (18 pages). Sitemap edited by
hand from the committed copy (host, entries, lastmod only for changed pages).
Not run: browser row-count guidance; Livewire behavior; Windows. Site check:
Quarto tabsets switch on phpstats.org (app.js and app.css handle
`.panel-tabset`); the gallery imports at 3.7 MB.
