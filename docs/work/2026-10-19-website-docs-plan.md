# Website and docs plan

Task group 1, website-docs. Planner: `1-website-docs-1-plan-review-steady-orca`.
Orchestrator: `1-website-docs-2-orchestrator-proud-llama`. Integrator:
`0-reconcile-royal-dingo`.

## Goal

Make phpstats.org a complete public home for the family. Today it presents
three of the seven packages well and the other four thinly, it omits juphyter
entirely, and the landing page recomputes every example on every request. This
plan closes the coverage gaps, regroups the packages into the two categories
Erik asked for, adds the root-host pages the site is missing, and makes the
landing page cheap to serve.

## The site as it stands

The site is four days old. Its plumbing is sound and its coverage is uneven.

**What works.** The sync pipeline is healthy. Across the three synced packages
there are 281 `phpstats://` link placeholders, all of which resolve, no
unresolvable relative links between docs pages, and 96 references to files
under `/content/`, all of which exist on disk. Docs pages get a grouped
sidebar, previous and next links, and a table of contents with scroll
tracking. Benchmarks, verification, and vignettes all have written empty
states. Examples degrade to a plain sentence when an extension is missing.
`tests/Feature/PagesTest.php` holds 30 tests that cover the landing page, the
package routes, the redirects, and the numerical results of every vignette.

**Coverage by package.**

| Package | Synced | On-site docs | Vignettes | Benchmarks | Verification |
|---|---|---|---|---|---|
| phit | yes | 16 in nav, 52 reachable | 11 | 8 | 9 |
| phplyr | yes | 16 | 7 | 1 | 6 |
| phpolars | yes | 12 | 4 | 2 | 1 |
| phibble | no | external | 3 | none | none |
| phiz | no | external | 2 | none | none |
| cmdstanphp | no | external | 1 | none | none |
| juphyter | not on the site at all | | | | |

**Gaps found in the audit.**

1. juphyter appears in no config file, no menu, and no page. The family README
   lists it as one of nine repositories.
2. phibble, phiz, and CmdStanPHP have no `site/` folder, so `site:sync` cannot
   take them. Their Docs tab links off to GitHub Pages, and their Benchmarks
   and Verification sections are permanently empty while still appearing in the
   package bar.
3. phibble has 11 rendered Quarto pages in its repo and phiz has 20. Both need
   only a `site/site.json` to become syncable. CmdStanPHP's docs are Jekyll
   Markdown with no Quarto build, so `DocsImporter` cannot read them.
4. The landing page runs 11 examples per request, and also renders a Livewire
   dashboard and reads the stored Stan posterior. The six explorer panels all
   render server-side, and so do all seven tab panels, because the tabs are
   Alpine `x-show` rather than lazy. The work includes a Polars aggregation, a
   linear model fit, and two chart specifications. Nothing in the application
   calls `Cache::` anywhere.
5. `Package::summary()` returns `[$this->site['summary']]` when a package is
   synced. A synced package therefore shows one dense paragraph on its overview
   while an unsynced one shows the three or four shaped paragraphs from
   `config/projects.php`. Syncing a package currently makes its overview worse.
6. `resources/views/package/overview.blade.php` prints `example.output` raw, so
   phplyr's overview shows the `# A tibble: 5 × 4` header line that
   `package-output.blade.php` is careful to strip.
7. phit's `site.json` lists 16 nav entries for 52 docs pages. The other 36,
   including every `notebooks/` and `verifications/` page, are reachable by URL
   but absent from the sidebar, and their previous and next links do not render
   because the path is not in the nav array.
8. phit's `site.json` has an empty `example.output`, so its overview shows
   example code with no result.
9. The root host serves only `/` and `/about`. There is no install or
   getting-started page, no index of vignettes across packages, and no way
   into the docs from phpstats.org itself.
10. `layout.blade.php` emits no meta description, canonical URL, or Open Graph
    tags. There is no `sitemap.xml`, and `robots.txt` is a bare allow-all.
11. There is no search over the synced docs.
12. The site README omits phiz from its list of packages.

## Decisions

Erik settled four questions on 2026-10-19.

- **All four areas are in the first wave**: docs coverage, root-host pages,
  landing-page robustness, and polish on the content already synced.
- **The three categories become two.** "Data & Workflow" holds phibble,
  phpolars, phplyr, and juphyter. "Statistics, Modeling & Visualization" holds
  phit, phiz, and cmdstanphp. juphyter therefore gets a layer, a menu entry,
  and a package page.
- **Cross-repo work belongs to this group**, done in a family workspace with
  `scripts/workspace add`.
- **CmdStanPHP's docs convert to Quarto** so `site:sync` can read them.

Three details follow from the regrouping that Erik did not specify. The plan
assumes them, and any of them is cheap to change.

- Layer keys become `data` and `stats`. The `processing` and `models` keys go
  away.
- Colors: indigo for Data & Workflow, purple for Statistics, Modeling &
  Visualization. This keeps phit and cmdstanphp on the color they have now and
  retires fuchsia from the layer palette. `.layer-fuchsia` stays defined in
  `resources/css/app.css` because the docs callout styles use fuchsia
  independently.
- Layer roles are rewritten, because both current roles stop describing their
  contents: "Frames, pipelines, and notebooks" for the first and "Models,
  inference, and charts" for the second. Menu `short` labels become "Data" and
  "Statistics".

## Constraints

- The site repository is phpstats.org. Work that changes phibble, phiz, or
  CmdStanPHP must happen in a family workspace, where each package directory is
  its own clone, and must be committed to that package's own repository.
- `site:sync` clones each package from GitHub at a pinned commit. A pin can
  only name a commit that has been pushed, so the pins for phibble, phiz, and
  CmdStanPHP can only be committed after `0-reconcile-royal-dingo` has merged
  and pushed those repositories. Task 9 adds a local sync path so the site can
  be verified before that happens.
- PHP 8.5 is required. With Herd, `php85`; the bare `php` is 8.4.
- Neither phit nor php_polars is loaded by the default `php85`, and neither has
  a built dylib on this machine. A worker that needs them runs
  `cargo build --release` in the package and passes
  `-dextension=<path>/libphit.dylib`, as CLAUDE.md describes. `artisan serve`
  and `artisan test` do not pass `-d` to child processes.
- Quarto 1.10.18, cargo 1.99, Composer, and npm are all installed.
- This worktree has no `vendor/`, so a worker runs `composer install` first.
- House style for the site is `.claude/skills/phpstats-style`. Package names
  are monospace wherever they are labels. Dark mode is the `.dark` class, never
  a media query.

## Out of scope

- The juphyter kernel itself. That is task group 2,
  `2-juphyter-1-plan-review-azure-jaguar`. This group gives juphyter a place on
  the site and the `site/site.json` contract to fill later.
- Package API changes. That is task group 3, api-consistency.
- Hosting, deployment, and DNS.
- Rewriting the landing page's design. Erik has been iterating on it directly
  and the recent commits are all his. Tasks here change the explorer only where
  the regrouping forces it.
- The examples section heading, "Simplify Statistical Workflows in PHP
  Applications with Native PHP". It is keyword-stuffed title case and reads
  against the house style, but Erik set it deliberately three commits ago. It
  is listed as an open question instead of a task.

## Approach and risks

The work splits into one track inside phpstats.org and one track across the
package repositories, which run in parallel and meet at task 10.

Two files cause almost all of the contention. `config/phpstats.php` carries the
layers, menus, explorer, and content pins, and `tests/Feature/PagesTest.php` is
a single 549-line file that nearly every task would otherwise touch. Task 1
splits the test file by area so that later tasks own separate test files, and
`config/phpstats.php` is written by task 2 and then by task 10, never
concurrently.

Risks:

- **Stale cached output.** Task 3 caches rendered example output. If the cache
  is not cleared on deploy and after `site:sync`, the landing page will show
  results from old code. The task owns the invalidation.
- **Serializing results.** Example results are `Phiz\Plot` objects, phibble
  frames, and Polars tables. The cache must hold the rendered HTML fragment,
  not the result object.
- **The CmdStanPHP conversion is the largest piece.** Six Markdown pages move
  to Quarto and their HTML must be rendered and committed. If it runs long, it
  can ship in a second round without blocking tasks 6 and 7.
- **Previews degrade without the extensions.** A preview without phit shows
  "This server does not load phit" in several panels. Build the dylib or tell
  Erik which panels are expected to be blank.
- **Erik's taste governs the categories.** Task 2 changes the colors and roles
  of every package page. It needs a preview before review.

## Open questions

1. Should the examples section heading change? It reads as marketing copy and
   `test_landing_tabs_come_from_markdown_pages_that_call_php` asserts it
   verbatim.
2. phit's docs carry `benchmarks/*.html` and `verifications/*.html` pages while
   the site builds its own Benchmarks and Verification sections from the same
   JSON. Should the docs pages stay, or should the nav point at the site's
   sections instead? Task 11 assumes they stay and enter the nav under their own
   groups.
3. phit's `site.json` summary and phplyr's both claim verification against R.
   The style guide keeps verification claims off the landing and About pages
   and does not rule on package overviews. Leaving them is the assumption.
4. Does juphyter get an explorer panel once it runs code, and what should its
   example show?

## Tasks

Each task names the repository it commits to. Model suggestions assume planners
run a strong model and routine work runs cheaper.

### Wave 1

**Task 1. Split the test suite by area.** (phpstats.org)

Every later task adds tests. One 549-line file would collide on nearly all of
them.

- Deliverable: `tests/Feature/PagesTest.php` split into `LandingPageTest`,
  `PackagePagesTest`, `DocsTest`, `VignettesTest`, and `ExamplesTest`, with
  every assertion preserved.
- Owns: `tests/`.
- Acceptance: the same 30 test methods exist, distributed by area. No
  production code changes. No assertion is weakened or dropped.
- Tests: `php85 vendor/bin/phpunit`, compared against the count and names on
  the current `HEAD`.
- Model: medium. Dependencies: none.

**Task 2. Two categories, and juphyter on the site.** (phpstats.org)

- Deliverable: the layers and menus become Data & Workflow (phibble, phpolars,
  phplyr, juphyter) and Statistics, Modeling & Visualization (phit, phiz,
  cmdstanphp), in that order, with the keys, colors, and roles from
  **Decisions**. juphyter gets a `config/projects.php` entry with status
  `in-progress`, `from_r` of `IRkernel and knitr`, `docs` of `null`, and an
  overview written from the repository's stated purpose rather than from
  unbuilt features. The package explorer skips a package that has no `explorer`
  entry, so juphyter appears in the menus, footer, and family table without a
  panel. The package bar hides Vignettes, Benchmarks, or Verification when that
  section has no content, which also stops phibble, phiz, and cmdstanphp
  advertising three empty sections.
- Owns: `config/phpstats.php`, `config/projects.php`,
  `resources/views/components/package-explorer.blade.php`,
  `resources/views/components/package-header.blade.php`,
  `resources/pages/about.md`, `CLAUDE.md`, `README.md`,
  `.claude/skills/phpstats-style/SKILL.md`,
  `tests/Feature/LandingPageTest.php`, `tests/Feature/PackagePagesTest.php`.
- Acceptance: two menus, each package in exactly one. `/juphyter` renders with
  its overview, repository link, and no Docs tab. The explorer still lists six
  packages and opens on phibble. `about.md` and the style skill describe two
  categories. `CLAUDE.md` no longer says three dropdowns. The README lists
  phiz and juphyter. Every package page works in light and dark mode.
- Tests: `test_landing_page_links_every_package` and
  `test_every_package_has_code_on_the_landing_page_and_it_runs` both iterate
  `Package::keys()` and will fail when juphyter joins it. Narrow the second to
  the packages in `explorer` and add a case asserting juphyter has a page and
  no explorer panel.
- Preview: required. The categories, colors, and menus are Erik's call.
- Model: strong. Dependencies: task 1.

**Task 4. Meta tags and a sitemap.** (phpstats.org)

- Deliverable: `layout.blade.php` takes a `description` and emits a meta
  description, a canonical URL, and Open Graph and Twitter card tags. Package
  pages fill them from the package's tagline, Markdown pages from a
  `description` front-matter key, and docs pages from the page title and
  package. A `sitemap.xml` route lists the landing page, the Markdown pages,
  and every package overview, docs page, vignette, benchmarks, and verification
  URL across the configured hosts. `robots.txt` points at it.
- Owns: `resources/views/components/layout.blade.php`, `routes/web.php`,
  `public/robots.txt`, a new sitemap controller, `tests/Feature/SeoTest.php`.
- Acceptance: every route type carries a description and a canonical URL.
  `sitemap.xml` is valid XML, contains no 404, and uses the right host in host
  mode and in `PHPSTATS_PATHS` mode. The `{page}` catch-all route still matches
  `about` and does not swallow `sitemap.xml`.
- Tests: a crawl of every URL in the sitemap returning 200, and an assertion
  that the canonical URL matches the request URL on each page type.
- Model: medium. Dependencies: task 1.

**Task 6. phibble publishes its site content.** (phibble)

- Deliverable: `site/site.json` following phplyr's schema: package, title,
  tagline, summary, install, requires, repository, example with pipe, chain,
  and output, and a `docs` block whose nav covers all 11 pages. The summary is
  written as the shaped paragraphs the overview wants, not one dense block, in
  whatever form task 11 settles on.
- Owns: `phibble/site/`.
- Acceptance: `site.json` names package `phibble`. Every nav path has a
  committed `.html` beside its `.qmd`. The example runs and its output is
  copied from a real run.
- Tests: phibble's own suite stays green. Verified against the site in task 10.
- Model: medium. Dependencies: none.

**Task 7. phiz publishes its site content.** (phiz)

Same shape as task 6, over phiz's 18 Quarto sources and 20 rendered pages. phiz's docs tree also holds
`_generated`, `figures`, and `examples` directories; the nav lists only real
pages, and figures must be reachable under `/content/phiz/`.

- Owns: `phiz/site/`.
- Model: medium. Dependencies: none.

**Task 8. Convert CmdStanPHP's docs to Quarto.** (cmdstanphp)

- Deliverable: `docs/index.md`, `installation.md`, `inference.md`, `api.md`,
  `compatibility.md`, and `testing.md` become `.qmd` with a `_quarto.yml`, each
  rendered to committed `.html` beside its source, matching how phibble, phiz,
  phit, phplyr, and phpolars are laid out. `site/site.json` follows the same
  schema and lists the nav, the install lines, and an example. The Jekyll
  `_config.yml` and `Gemfile` are removed or left inert, whichever keeps the
  existing GitHub Pages site serving until the site takes over.
- Owns: `cmdstanphp/docs/`, `cmdstanphp/site/`.
- Acceptance: `quarto render` produces the committed HTML. Every nav path has
  an `.html`. Internal links between pages use relative `.html` targets or
  `phpstats://` placeholders so `DocsImporter` and `Package::resolveLinks`
  handle them. No content is lost against the Markdown originals.
- Tests: a link check across the rendered HTML. Verified against the site in
  task 10.
- Model: strong. This is the largest unit in the group.
- Dependencies: none. It can ship in a later round without blocking tasks 6 and
  7.

### Wave 2

**Task 3. Cache the examples the landing page computes.** (phpstats.org)

- Deliverable: the landing page stops recomputing its 11 examples on every
  request. Cache the rendered output fragment per example name rather than the
  result object, because results are `Phiz\Plot` instances, phibble frames, and
  Polars tables. The key includes the example file's modification time so an
  edit invalidates it. Caching is off when `APP_DEBUG` is true, so local work still
  sees changes.
- Owns: `app/Content/Example.php`,
  `resources/views/components/example.blade.php`,
  `resources/views/components/package-explorer.blade.php`,
  `tests/Feature/ExamplesTest.php`, and a cache-clearing command or a public
  flush method for task 9 to call.
- Acceptance: a second request to `/` runs none of the cached examples,
  verified by a counter or a spy rather than by timing. The rendered HTML is identical with
  the cache warm and cold. An example whose file changes recomputes. A missing
  extension still produces the existing sentence and is not cached as a
  success.
- Tests: two requests to `/` asserting identical HTML and no second
  computation, plus a case for the missing-extension path.
- Preview: required, to confirm the landing page is visually unchanged.
- Model: strong. Dependencies: task 2, which also edits the explorer.

**Task 5. A getting-started page and an index of vignettes.** (phpstats.org)

- Deliverable: `resources/pages/get-started.md` giving the PHP 8.5 requirement,
  the Composer and PIE install lines for each package, and the shortest
  worthwhile example, built from `Package::install()` and `requires()` so it
  stays correct as packages sync. A `/vignettes` route on the root host listing
  every vignette across all packages with its packages and a link to its owner,
  which the site has lacked since the move to per-package hosts. Both reachable
  from the site bar.
- Owns: `resources/pages/get-started.md`, a new root-host vignettes index view,
  `resources/views/components/site-header.blade.php`, `routes/web.php`,
  `tests/Feature/RootPagesTest.php`.
- Acceptance: `/get-started` and `/vignettes` render on the root host and in
  `PHPSTATS_PATHS` mode. The new `/vignettes` route is declared before the
  `{page}` catch-all, and `/vignettes/{slug}` still redirects to the owning
  package. The index lists all 10 Livewire vignettes plus every synced script
  vignette. Package names are monospace. Install lines for a package with no
  `site.json` either come from its repository or are omitted, never invented.
- Preview: required. New public pages.
- Model: strong, for the copy. Dependencies: task 4, which also owns
  `routes/web.php`.

**Task 9. Sync from a local checkout.** (phpstats.org)

- Deliverable: `site:sync --from <path>` reads a package's `site/` and `docs/`
  from a local checkout instead of cloning from GitHub, so tasks 6, 7, and 8
  can be verified on the site before reconcile pushes them. A local sync writes
  no pin and marks `sync.json` as local so a local run is never mistaken for a
  pinned one. The command also flushes the example cache from task 3.
- Owns: `app/Console/Commands/SyncSiteContent.php`,
  `tests/Feature/SyncTest.php`.
- Acceptance: `--from` populates `resources/content/{package}` and
  `public/content/{package}` identically to a clone of the same commit.
  `config/phpstats.php` is not modified by a local sync. Without `--from` the
  command behaves exactly as it does now.
- Model: medium. Dependencies: task 3.

### Wave 3

**Task 10. Pin and sync phibble, phiz, and CmdStanPHP.** (phpstats.org)

- Deliverable: `content` entries in `config/phpstats.php` for the three
  packages at their merged commits, their synced content under
  `resources/content/` and `public/content/`, and a check that each package's
  Docs, Benchmarks, Verification, and Vignettes sections render.
- Owns: `config/phpstats.php` (the `content` key only),
  `resources/content/{phibble,phiz,cmdstanphp}/`,
  `public/content/{phibble,phiz,cmdstanphp}/`, `tests/Feature/DocsTest.php`.
- Acceptance: all six library packages have on-site docs. Every nav path
  renders. Every `phpstats://` placeholder resolves to a real page. Every
  `/content/` asset referenced by the synced HTML exists on disk. The Docs tab
  no longer points off-site for these three.
- Tests: extend the existing link and asset audit over the new packages.
- Preview: required. Three package sections appear for the first time.
- Model: medium. Dependencies: task 2, task 9, and tasks 6, 7, and 8 merged and
  pushed by `0-reconcile-royal-dingo`. This is the group's one hard
  cross-repository ordering constraint, and the orchestrator should expect to
  dispatch it after a reconcile round.

**Task 11. Fix the overview regression and phit's orphan docs.** (phpstats.org
and phit)

- Deliverable: a synced package stops losing its shaped overview.
  `Package::summary()` accepts a list of paragraphs from `site.json` and falls
  back to `config/projects.php` when `site.json` gives one block, so syncing a
  package never makes its overview worse. `package/overview.blade.php` strips a
  leading `# A tibble:` line from `example.output`, as
  `package-output.blade.php` already does, which removes it from phplyr's
  overview. In phit's repository, the 36 docs pages missing from the nav enter
  it under their folder groups, and `example.output` is filled from a real run
  so phit's overview shows a result.
- Owns: `app/Content/Package.php`, `resources/views/package/overview.blade.php`,
  `tests/Feature/PackagePagesTest.php` additions, and `phit/site/site.json`.
- Acceptance: each package overview shows shaped paragraphs. No overview prints
  a tibble header. phit's sidebar lists all 52 pages and previous and next work
  on every one. phit's overview shows example output.
- Tests: a case asserting multi-paragraph overviews for a synced package and a
  case asserting no `# A tibble` appears in any package page.
- Model: medium. Dependencies: task 2 and task 10.

**Task 12. Search across the synced docs.** (phpstats.org)

- Deliverable: search over the synced docs. Build an index at sync time from
  each page's headings and text, and serve it from the docs sidebar. Keep it
  server-rendered or a small static JSON per package rather than adding a
  hosted search service.
- Owns: a new search index builder and view component,
  `app/Content/DocsImporter.php`, `tests/Feature/SearchTest.php`.
- Acceptance: a query returns results from the current package with page
  titles and headings, works with JavaScript for ranking but degrades to a
  plain form, and adds no request-time cost to a docs page that is not
  searching.
- Preview: required.
- Model: strong. Dependencies: task 9 and task 10, so the index covers all six
  packages.

## Parallelism and dependencies

```
Wave 1:  T1 ──┬── T2 ──── T3 ──── T9 ──┐
              └── T4 ──── T5           │
         T6 ──┐                        │
         T7 ──┼── (reconcile pushes) ──┴── T10 ──┬── T11
         T8 ──┘                                  └── T12
```

- T1 runs alone and is small. Everything in phpstats.org waits on it.
- T6, T7, and T8 are in three different repositories and share no files. They
  run in parallel with each other and with the whole phpstats.org track.
- T2 then T3 then T9 is a chain, because T2 and T3 both edit the package
  explorer and T9 calls the cache flush T3 defines.
- T4 then T5 is a chain on `routes/web.php`.
- T10 is the meeting point and cannot start until reconcile has pushed T6, T7,
  and T8.
- T11 and T12 are independent of each other.

If the group needs to ship less, cut T12 first, then T8, then T5. T1, T2, T3,
and T6 with T7 and T10 are the core.

## Preview contract

Erik should see T2, T3, T5, T10, and T12 before they are approved.

- Serve one preview per task behind a single tunnel URL with
  `PHPSTATS_PATHS=true` and `TRUSTED_PROXIES=*`, so every package is reachable
  under `/{package}` without wildcard DNS.
- Seed state: `composer install`, `php artisan key:generate`,
  `php artisan migrate --seed`, and `npm install && npm run build`. The seeder
  loads the example datasets from `database/data`.
- For any preview that shows example output, build the extensions first with
  `cargo build --release` in `phit` and in `phpolars` and start the server with
  `-dextension=<path>/libphit.dylib`. Neither is built on this machine today.
  `artisan serve` does not pass `-d` to its children, so use the pattern in
  CLAUDE.md. Without them, several panels show "This server does not load
  phit", which should be stated in the handoff rather than left for Erik to
  find.
- What to check per task: T2, the two menus, the package colors, and
  `/juphyter`. T3, that `/` is unchanged. T5, `/get-started` and `/vignettes`.
  T10, the Docs, Benchmarks, and Verification sections of phibble, phiz, and
  cmdstanphp. T12, a search from a docs sidebar.
- Each preview is checked in light and dark mode and at phone width.

## Revisions

Changes to the plan made while the work ran, with the reason. The task
definitions above stand except where noted here.

**Erik's decisions, 2026-10-19.** Two questions came up during review that the
code could not settle.

- *Composer package names.* phiz declares `erikwestlund/phiz` and CmdStanPHP
  declares `cmdstan/php`, while the other five packages declare `phpstats/*`.
  phibble's require-dev asks for `erikwestlund/phiz` while its `repositories`
  entry already points at `github.com/phpstats/phiz`, which is why
  `composer install` fails inside phiz. Both rename to `phpstats/*`, phiz to
  `phpstats/phiz` and CmdStanPHP to `phpstats/cmdstanphp`.
- *Install lines.* No package is on Packagist. `phpstats/phibble`,
  `phpstats/phplyr`, `phpstats/phiz`, `erikwestlund/phiz`, `cmdstan/php`, and
  `phpstats/phit` all return 404, so every `composer require phpstats/x` line
  the site publishes is an instruction that fails. Each Composer package's
  install block takes the two-line form that CmdStanPHP's worker arrived at
  independently: a `composer config repositories.<pkg> vcs <url>` line, then
  `composer require <name>:dev-main`. A `composer init` line for the
  empty-directory case does not go in seven manifests; if it is wanted, it is
  one sentence on `/get-started`.

**Task 11 splits in two.** `Package::summary()` returns
`[$this->site['summary']]`, so a `site.json` carrying a list of paragraphs
renders "Array". All three package workers write `summary` as a list, so the
code change has to land before the pins rather than after.

- 11a: list support in `Package::summary()` and the `# A tibble:` strip in
  `package/overview.blade.php`. phpstats.org only, depends on task 2, runs
  beside tasks 3, 5, and 9, and must precede task 10. It owns
  `app/Content/Package.php`, `resources/views/package/overview.blade.php`, and
  additions to `tests/Feature/PackagePagesTest.php`, which overlaps nothing in
  tasks 3, 5, or 9.
- 11b: phit's nav and example output, after task 10 as originally planned.

11a must accept both forms, not only lists. phit, phplyr, and phpolars already
have a `summary` string committed and synced. The rule: prefer
`site['summary']` when it is a non-empty list, otherwise fall back to
`project['overview']`. A string summary is then ignored in favor of the shaped
paragraphs in `config/projects.php`, which improves the three synced packages
today and needs no further change once their repos ship lists. Acceptance needs
three cases: a `site.json` giving a list, one giving a string, and a package
with no `site.json`.

11a also fixes an asymmetry in `Package::example()`. It reads
`'chain' => $example['chain'] ?: null` with no null-coalesce, while `pipe` uses
`?? null`, so a `site.json` that omits `chain` raises an undefined-key warning
where omitting `pipe` is safe. phiz omits `chain`, so this is load-bearing:
`'chain' => ($example['chain'] ?? null) ?: null`, with a test for a `site.json`
that has `pipe` only.

**Task 9 gains two items.** `SyncSiteContent.php:97` builds nav entries as
`['title', 'path']` and discards every other key, so a `section` key in a
`site.json` can never reach the site. Task 9 carries it through. Task 9 also
suppresses the group heading in `resources/views/package/docs.blade.php` when
`docsGroups()` returns exactly one group, following the convention already
stated in `config/phpstats.php` that a menu with one layer shows no layer
heading. Without it, phibble's 11 top-level pages sit under a lone "Guide"
heading. `resources/views/package/docs.blade.php` now has three owners in
sequence: task 9, then task 13, then task 12.

**Task 13, charts in synced docs.** `DocsImporter` keeps only
`<main id="quarto-document-content">` and strips `script` elements inside it,
while phiz's Vega loader and its `[data-phiz]` initializer sit in `<head>`. The
chart containers survive the import and nothing draws them. Task 13 owns
`resources/views/package/docs.blade.php`, loads phiz's scripts when a page
contains `data-phiz`, and adds the `.phiz-figure` rule. It depends on task 10,
because it needs real synced phiz docs to test against. Acceptance: a phiz docs
page with a chart renders it in light and dark mode, and a page without
`data-phiz` loads no chart JavaScript. This stayed out of task 10 so that task
10 remains pin, sync, and verify rather than a feature task on the critical
path.

**Task 14, install blocks for the synced packages.** phit, phplyr, and phpolars
carry the broken one-line install form in `site.json` files already pinned, so
fixing them means a commit in each repo and a re-pin. Awaiting Erik's call on
whether it belongs in this wave.

**Task 15, the composer renames.** phiz to `phpstats/phiz`, CmdStanPHP to
`phpstats/cmdstanphp`, and phibble's require-dev from `erikwestlund/phiz` to
`phpstats/phiz`. The task 6, 7, and 8 workers do their own package's rename,
because they already hold those repos. phiz's `composer.json` also carries two
stale `repositories` URLs pointing at `github.com/erikwestlund/phibble` and
`erikwestlund/phplyr`; they go in the same commit. phiz's rename and phibble's
require-dev change must reach main together, because either alone breaks the
other repo's dependency resolution.

**Task 10 gains two gates.** It must not pin phiz or cmdstanphp until task 15
has landed, so no published install line names a package its repo does not
declare. Its acceptance also records that phiz ships 18 of its 19 docs pages:
`docs/gallery.html` is a hand-rolled standalone page from
`scripts/docs-examples.php` with no `.qmd` and no `<main>`, and
`DocsImporter::discover()` requires a `.qmd` beside the `.html`. The fix is for
that script to emit `gallery.qmd` so Quarto renders it like every other page,
which is phiz-repo follow-up.

**Corrections to this plan.** Task 1's acceptance named
`php85 vendor/bin/phpunit` as its test command. That command has never worked
in this repository, because `phpunit.xml` declares a Unit suite and
`tests/Unit` did not exist. Task 1 added `tests/Unit/.gitkeep`. Separately,
task 6's description said phibble's summary should take "whatever form task 11
settles on", which left a shared contract undecided while three workers were
already writing `site.json`; the form is settled above.

**Findings recorded, no task yet.**

- No package's CI checks that its committed docs HTML matches its `.qmd`. All
  five Quarto packages set `output-dir: _site` while committing HTML beside the
  sources, so `quarto render docs` deletes the committed files and writes them
  to `_site`, and every package's docs CI checks presence only. A
  render-and-diff step would make staleness impossible and should be added once
  across all five rather than reinvented per repo.
- phiz's overview will show 50 lines of raw Vega-Lite JSON under "Output",
  because `overview.blade.php` renders `example.output` in a `<pre>`. For the
  charting package that is the weakest output block on the site. Letting a
  package overview render a chart through the same path as task 13 is the fix.
- phiz's docs use `stats_*` function names while phit documents `fit_*` and
  `predict`. That becomes publicly visible the moment task 10 pins phiz, so it
  is gated on this group's work even though it belongs to group 3.

### Later revisions

**11a gains two items beyond the summary and tibble work.** It fixes the
`Package::example()` asymmetry described above, and it hides the overview's
`<aside>`, or drops the second grid column, when that aside has nothing in it.
juphyter has no sections, no relations, no `sync.json`, and no vignettes, so
every conditional inside the aside is false and `/juphyter` renders a 22rem
blank column on desktop. `resources/views/package/overview.blade.php` is 11a's
file, which is why this is not task 2's to fix. If Erik would rather put
something in that column for an unreleased package, that is a separate change.

**Task 4 gains one item.** Task 2 hides Benchmarks and Verification from the
package bar when a package has none, while task 4's sitemap lists those URLs
for every package, so the sitemap would submit for indexing the pages the
navigation deliberately hides. The sitemap skips a package's vignettes,
benchmarks, or verification URL when that section is empty. It needs no
dependency on task 2, because it can call `benchmarks()`, `verifications()`,
and `vignettes()` directly.

**Task 4 also replaces `public/robots.txt` with a route.** The static file is
served on every host and permits all crawling, and the canonical URL is built
from `url()->current()`, which echoes the request host by design. Staging and
tunnel previews are therefore indexable and each declares itself canonical,
which is the duplication canonical tags exist to prevent. The route emits
`Sitemap: {{ route('sitemap') }}` and `Disallow: /` when the host is not a
configured production host. The canonical stays as it is.

**Task 16, phiz's remaining org references.** `docs/_quarto.yml`'s `site-url`
and `repo-url`, `docs/robots.txt`, `docs/sitemap.xml`, the four dead links at
`docs/index.qmd:32`, and 22 rendered docs pages still name the old GitHub org
and Pages host. One commit in the phiz repo that fixes the sources and
re-renders the whole docs site. It does not gate task 10, because
`DocsImporter` and `Package::resolveLinks()` rewrite cross-package links, so
phpstats.org is unaffected. Hold it until after task 10 so a 20-page re-render
does not collide with the pin. The two references that need no re-render,
`src/Compile/Compiler.php:219` and `README.md:173`, are fixed in task 15.

**Merge ordering for the integrator.** phibble's task 15 commit must reach main
with or before phiz's, never after. `composer remove --dev <name>` exits 0 for
a package that is not required, so phibble's stale CI line becomes a silent
no-op once phiz is renamed, leaving phiz in `require-dev` on the PHP 8.3 and
8.4 legs where it cannot install. phibble alone is safe; phiz alone breaks
phibble's CI. phiz's tags v0.1.0 to v0.3.0 still declare the old name, so only
`dev-main` resolves under the new one until a tag is cut. Task 10 must not pin
cmdstanphp below 745edc2 or phiz below its task 15 commit.

**Two notes on generated files.** Quarto writes `lastmod` into
`docs/sitemap.xml` from file timestamps, and every file in a fresh worktree
carries the checkout time, so a render there rewrites every date and claims
pages changed that did not. Do not commit a sitemap whose dates cannot be
vouched for; this is the one generated file where a stale value beats an
invented one, and it must be excluded from any render-and-diff CI check.
Separately, adding `extra.branch-alias` to phibble and phplyr would let every
package use `dev-main` for its dev dependencies; without it, phplyr's
`phpstats/phibble: ^0.3` requirement forces `^0.3` constraints on anything that
dev-depends on both. That is a family packaging decision for Erik.

### Revisions after the first merges

**Task 14 is in this wave.** Erik approved it along with task 2 and the install
blocks. It covers phit's, phplyr's, and phpolars' install blocks, each needing
a commit in its own repo and a re-pin.

**Task 4 gains an indexable flag.** Host gating alone cannot keep staging out
of search, because on staging `PHPSTATS_ROOT_HOST` is the staging host, so the
check concludes staging is published. `config/phpstats.php` gains
`'indexable' => (bool) env('PHPSTATS_INDEXABLE', env('APP_ENV') === 'production')`,
and a host must be both a configured site host and indexable. Defaulting from
`APP_ENV` fails safe in both directions: production needs no new variable,
and staging, local, and preview environments are excluded automatically. Read
it through `config()`, never `env()` at runtime, or `config:cache` freezes it.

**Task 4 does not need a second robots route.** `RouteCollection::addToCollections()`
keys routes by domain plus URI in insertion order and `matchAgainstRoutes()`
deprioritizes only fallback routes, so registration order decides and a
domainless `/robots.txt` above the companion group serves every host.
Verified by removing the duplicate in a scratch clone: all 11 `SeoTest` tests
pass, including the one asserting exact content on
`phit-polyfill.phpstats.test`.

**Task 13 widens to phiz script loading site-wide.** Beyond charts in synced
docs, it moves phiz's runtime to `@phizScripts` in `layout.blade.php` with
`phiz.inject_scripts => false`. This is required rather than cosmetic. Task 3's
example cache has to tell a cached fragment whether it captured the phiz
runtime, which means reading and writing `Phiz\Laravel\ScriptState`, a class
phiz annotates `@internal`, and it stores a page-order fact inside a
per-example entry. The cache key covers the example files, so it does not
notice a change to which examples a page shows or in what order; editing
`resources/pages/home/4-charts.md` or reordering the explorer leaves stale
flags and the runtime is then emitted zero times or twice, with charts
silently failing to draw. Emitting it once from the layout removes the flag,
the internal-class dependency, and the page-order coupling, and fixes the docs
pages in the same change.

**Task 3 flushes the cache where the data changes.**
`app/Console/Commands/FitSchoolPosterior.php` regenerates
`database/data/stan/school-intercepts.json`, which feeds cmdstanphp's explorer
panel through its `stored` callable, and that file's mtime is not in the cache
key. The command calls `Example::flush()`, which is better than widening the
key because the dependency is declared where it changes.

### Task 17, the bare word "frame"

Erik's rule, confirmed 2026-10-19: `data frame`, `DataFrame`, and `dataFrame`
are all fine; the bare noun `frame` or `Frames` is not. It applies to prose,
not to code identifiers. There are a dozen `$frame` variables and a
`frame-table.blade.php` component, and a rule about the product's vocabulary
should not rename code unless Erik says so.

Audited at phpstats.org 449b92d and the package manifests: 21 occurrences.

- `config/phpstats.php:48`, the layer role "Frames, pipelines, and notebooks".
  This plan proposed that wording and task 2 implemented it faithfully, so the
  error is this plan's. Use "Tables, pipelines, and notebooks", which avoids
  the word rather than lengthening the line.
- `phibble/site/site.json`, four: "returns a new frame", "as a frame loads",
  "lay a frame out the way tibble does", and "Frames load from CSV".
  `cmdstanphp/site/site.json`, one: "as a phibble frame". Both manifests were
  approved in review without this being caught.
- `config/projects.php` lines 52, 54, 92, and 110; `config/phpstats.php` lines
  75, 77, 100, 111, 120, 132, 165, 167, and 170;
  `resources/pages/home/2-transform.md:6`; and
  `.claude/skills/phpstats-style/SKILL.md:84`. Most read "phibble frames",
  where the bare noun carries a package name in front of it. Insert "data" or
  use "table", whichever reads better.

phplyr, phpolars, and phiz are clean. The rule goes into
`.claude/skills/phpstats-style/SKILL.md` in the same commit, which is what
stops it returning; that file today both breaks the rule and fails to state
it.

### Notes from the later rounds

**Task 5 needs its Install paragraph rewritten against task 14's blocks.** The
copy explains a `pie` line and an `extension=phit` line. After task 14 the
extension blocks are a `git clone`, a `cargo build`, a one-off
`php -d extension=...`, and an `extension=` line carrying an absolute path, so
there is no `pie` line left to explain and `extension=phit` is not a form the
page shows.

**Two Blade traps worth recording.** `@if` immediately after a word character,
as in `Output@if`, is not treated as a directive, compiles to broken PHP, and
`view:cache` reports nothing; build such a label in PHP instead. And a Blade
echo cannot be used in a Markdown link destination, because CommonMark
percent-encodes it; write the literal path, which is the same in host mode and
path mode. Both belong in the `phpstats-style` skill.

**Still open for a follow-up list.** phpolars' `docs/deployment.qmd` says
release builds attach six archives while v0.1.0 attaches two. phit's empty
`example.output` is 11b's. Task 10's preview checklist should include the
six-line extension install card and phpolars' 69-character build line, for
wrapping.

### Executing PHP in Quarto docs, and what it requires of this site

Erik is weighing whether package docs should execute their PHP rather than
carry hand-written output. The facts below come from `2-juphyter-1-plan-review-azure-jaguar`,
measured against real renders of juphyter's own documentation site, and from
checks in this checkout. They change one task in this plan and add two.

**There is no Quarto extension to build.** A kernelspec written where
`jupyter --paths` looks, plus `jupyter: <name>` in a document's front matter,
is the whole integration. Quarto's jupyter engine resolves the name against
installed kernelspecs. Nothing belongs in `_extensions/`. Use the bare
`jupyter: <name>` form and not the explicit `kernelspec:` block, which also
requires a `path` key and raises `KeyError: 'path'` without it.

**Task 13's acceptance has to name phiz's loader, not just its libraries.**
The mechanism in this plan's earlier revision was partly wrong. phiz does not
put a chart's Vega-Lite spec in a script tag. It puts it in a `data-phiz`
attribute on a plain div, 36,056 bytes of JSON in one attribute in the render
checked. `script:not([type*="json"])` cannot reach an attribute, so the spec
survives the import and phiz needs no JSON-spec variant. What the importer
does remove is phiz's loader, a 3,960-byte inline `<script>` with no `type`
that finds `[data-phiz]` elements and hands each spec to vega-embed. A page
that ships vega, vega-lite, and vega-embed without that loader draws nothing,
and every chart is a silent empty div that no test asserting on script tags
would catch.

`@phizScripts` in `resources/views/components/layout.blade.php`, which this
plan already specifies, is the correct and sufficient fix. The directive calls
`Phiz\Laravel\PhizServiceProvider::scripts()`, which calls
`Assets::scripts(bool $loader = true)`, so one directive emits all four pieces
and sets `ScriptState::included`. Do not copy the loader's text into a Blade
file, where it would drift from the package. Task 13's acceptance must assert
that the loader is present, because asserting only on the three CDN URLs
passes while every chart stays blank. The pinned versions are vega 6.4.0,
vega-lite 6.4.3, and vega-embed 7.3.0, each with an integrity hash in
`Assets::INTEGRITY`.

**New task: style Quarto's jupyter-engine cell output.** The markdown engine
produces no cell wrappers, so this site has never styled any. The jupyter
engine wraps each executed cell as `div.cell[data-execution_count]`, holding
`div.sourceCode.cell-code` for the source and
`div.cell-output.cell-output-display` for the result. Those three classes are
Quarto's and arrive unstyled. Everything inside them is the kernel's: juphyter
prefixes its own classes `juphyter-`, with `juphyter-table` on the wrapper,
`juphyter-note` on the header and footer lines, and `juphyter-num`,
`juphyter-rowid`, `juphyter-type`, and `juphyter-na` on cells.

juphyter also ships a scoped `<style>` block inside the table wrapper, every
rule under `.juphyter-table`, written against JupyterLab CSS variables with
plain fallbacks such as `var(--jp-code-font-family, ui-monospace, ...)` and
`var(--jp-content-font-color1, #111827)`. That block survives the import:
`DocsImporter` removes `#title-block-header`, `.code-copy-button`,
`script:not([type*="json"])`, `.quarto-alternate-formats`, and
`.quarto-alternate-notebooks`, and no `style` element among them. Verified by
reading `app/Content/DocsImporter.php:94`. So the site should theme the table
by defining the `--jp-*` variables its layer colors want rather than
overriding `.juphyter-` rules, which keeps the site off markup the kernel owns
and works in light and dark mode through the same variables.

**New task: derive `docs.nav` from `_quarto.yml` at sync time.** Agreed with
the juphyter group as a cross-package rule. The nav is currently maintained by
hand in both `_quarto.yml` and `site.json`, and phibble's two copies are
identical, eleven files each. It has already failed once: phit has 52 rendered
`.qmd` and 16 in its nav, so 36 pages are rendered, synced, reachable by
direct URL, and absent from the sidebar. That is task 11b's symptom, and
deriving the nav removes its cause. `_quarto.yml` is the better source because
it must already be correct for the docs to render at all, so a stale
`_quarto.yml` breaks a build while a stale `site.json` is invisible. Task 9
already taught `SyncSiteContent` to carry a `section` key through the nav, so
the shape is in place. At minimum the sync should fail when a rendered page is
missing from the nav.

**Freeze makes execution adoptable, with one real limit.** Measured four ways
by the juphyter planner. `quarto render docs` writes to `docs/_site` and keeps
`_freeze/` separate, under `docs/_freeze/<page>/execute-results/html.json`, so
freeze does not interfere with this family's convention of committing the
`.html` beside the `.qmd`; `output-dir` decides that, independently. Quarto
validates that a kernelspec of the right name exists before it consults the
freeze cache, and needs nothing behind it: with a stub `kernel.json` naming
`/nonexistent/php`, a frozen page rendered from cache with no PHP, no engine,
no Python, no phit, and no CmdStan present. With the kernelspec absent
entirely, both `freeze: auto` and `freeze: true` fail with
`ERROR: Jupyter kernel not found`. On a cache miss the illusion ends and the
render fails on the missing interpreter. So freeze lets a contributor build
unchanged docs after copying one small JSON file, and anyone editing a PHP
cell needs the real kernel. For a site build or a prose fix, which is most
contributor traffic, a stub kernelspec is enough.

**Constraints to carry into any conversion.** phiz requires PHP 8.5 while
phibble and phplyr floor at 8.3, so any package whose docs plot needs a kernel
on 8.5 or newer. phibble's docs do not plot, which is one reason it is the
right second pilot after juphyter: eleven pages, PDO only, and this plan has
already verified its example output byte for byte, so a render can be diffed
against a known-good baseline. Quarto's `ipynb` output is not equivalent to
its HTML, because Pandoc drops HTML tables and error outputs on the way
through, for the stock python3 kernel as much as for juphyter. That costs this
site nothing: `DocsImporter` reads only the `.html` beside a `.qmd`, and the
single `ipynb` reference in the codebase is an extension strip on a nav path
in `SyncSiteContent.php:93`.

**juphyter's docs are already the pilot.** Its task 8 built a four-page
documentation site whose every example runs through the kernel the package
installs. The cell classes recorded above were read from that render.

**What deriving the nav actually covers, measured.** Against
`/Users/erik/Projects/phpstats/phit`: 52 `.qmd` under `docs/`, 51 named in
`docs/_quarto.yml`, and 16 named in `site/site.json`. So derivation picks up 35
of the 36 pages missing from the nav, and `data/lmm/index.qmd` stays orphaned
under either approach because no nav names it at all. Those 35 are 15
`notebooks/`, 9 `benchmarks/`, 6 `verifications/`, and 5 top level.

That breakdown carries a decision the derivation task has to make. phit's
`site.json` already holds a `benchmarks` key with 8 JSON result files and a
`verifications` key with 9, which feed the package host's own Benchmarks and
Verification sections, so taking `_quarto.yml` wholesale would add 15 pages to
the Docs nav whose subject matter already has a section of its own. The task
therefore has to settle how a package marks a rendered page as deliberately
outside the docs nav: an exclusion list in `site.json`, exclusion by directory,
or treating `_quarto.yml`'s sections as the unit and letting the site choose
which sections appear. Only phit has this overlap today, so the choice should
be made against all six derived navs rather than fixed here.

An earlier count in this plan put three pages in neither nav. That came from a
pattern that did not allow a dot inside a filename, which turned `v0.1.qmd` and
`v0.2.qmd` into `1.qmd` and `2.qmd`. The figures above are from a corrected
count; the total of 36 pages missing from phit's nav was right.

### Task 15 is a coordinated push, and this plan undercounted its consumers

Prompted by a question from `2-juphyter-1-plan-review-azure-jaguar`, and
measured here rather than reasoned about.

**Composer takes a package's name from the default branch and applies it to the
tags.** Verified with a dry run in a scratch directory against a VCS repository
pointed at `/Users/erik/Projects/phpstats/phiz`, under `php85`, where `main`
carries `phpstats/phiz` while the `v0.3.0` tag's own `composer.json` still says
`erikwestlund/phiz`:

    require phpstats/phiz:^0.3     Locking phpstats/phiz (v0.3.0)
    require erikwestlund/phiz:^0.3 could not be found in any version

So the existing `v0.3.0` tag answers the new name with no re-tagging and no new
release, which makes the rename cheaper than this plan assumed. The second line
is the part that matters more and that neither group had stated: **the old name
stops resolving at the same instant.** The rename is not additive. It moves
every tag from one name to the other.

**Three consumers break the moment phiz's `main` is pushed, and this plan named
only two.** Task 15 covers phiz's rename, CmdStanPHP's, and phibble's
`require-dev`. It does not cover this site, whose own `composer.json:12`
requires `erikwestlund/phiz: ^0.3`. That is a defect in the task as written:
pushing phiz's `main` breaks `composer install` in this repository. **Task 15
gains the site's own `composer.json`**, and all of phiz, phibble, and the site
have to reach main together. juphyter's group holds a fourth commit, already
written and reviewed, gated on the same push.

**Nothing in this plan holds the push, and nothing in it can authorize one.**
This workspace plans and reviews; it does not push. phiz's `main` is three
commits ahead of `origin/main`: `618416a` publishing site content for
phpstats.org, `ed768f3` renaming the Composer package, and `f304f45` pointing
the documentation links at phpstats.github.io. The rename commit is on no
remote branch, which is the sole reason `phpstats/phiz` is unavailable from the
GitHub URL that both this site and juphyter declare. The decision to push is
Erik's.

**This tightens task 10's gate.** `site:sync` clones each package from GitHub
at a pinned commit, so a pin can only name a pushed commit. Task 10 therefore
cannot pin phiz until that push happens, and the push also carries `618416a`,
which is the site content task 10 needs. "Not before task 15 lands" means
"not before phiz's main is pushed."

**Recommendation on the version, for Erik to settle.** Keep the rename within
0.3.x. phiz's `composer.json` declares no `version` key and its tags are
v0.1.0, v0.2.0, and v0.3.0, so the measurement above means no re-tag is needed
and no constraint moves anywhere. Publishing the new name at 0.4 or later would
force a constraint change in phibble's `require-dev`, in this site's
`composer.json`, and in juphyter's held commit, for no benefit. The rename
changes a name, not an API.

**One tip from the juphyter group that does not apply here.** The package now
installs under `vendor/phpstats` rather than `vendor/erikwestlund`, so anything
building a path from the vendor directory rather than using the autoloader
needs updating. This site has no such path: a search of `app/`, `config/`, and
`resources/` found no `vendor/` path construction beyond the autoloader.
`DocsImporter.php:143` already matches both `erikwestlund.github.io` and
`phpstats.github.io`, and that is a documentation URL rather than a package
name, so it needs no change for the rename.

### cmdstanphp carries a second defect underneath the rename

`1-website-docs-2-orchestrator-proud-llama` pointed out that this site's
`composer.json:11` requires `cmdstan/php: dev-phpstats-family` and said the
cmdstanphp rename breaks the same way phiz's does. The name break is the same.
Underneath it is a separate defect that predates the rename.

Measured against `/Users/erik/Projects/phpstats/cmdstanphp` with `php85`:

    require phpstats/cmdstanphp:dev-phpstats-family
      found phpstats/cmdstanphp[dev-family-renames,
      dev-worktree-agent-a5ec1af2c16c7f94b, dev-main]
      but it does not match the constraint
    require phpstats/cmdstanphp:dev-main     Locking (dev-main b3ae0e2)
    require cmdstan/php:dev-main             could not be found in any version

**The branch this site requires no longer exists.** `git ls-remote --heads`
reports exactly three remote branches: `family-renames`, `main`, and
`site-content`. There is no `phpstats-family`. cmdstanphp also has no tags at
all, so a `dev-<branch>` constraint is the only way to require it and the
branch name is load-bearing.

**Why nothing has failed yet.** `composer.lock` pins `cmdstan/php`
`dev-phpstats-family` at `59baa928ecc5`, and that commit is still an ancestor
of `origin/main`, `origin/family-renames`, and `origin/site-content`. So
`composer install` resolves from the lock and succeeds. `composer update` would
fail today, before any rename, because Composer cannot resolve a branch that is
not there.

**So task 15's change to this site's `composer.json` has to fix two things on
line 11, not one.** The package name goes from `cmdstan/php` to
`phpstats/cmdstanphp`, and the constraint has to name a branch that exists.
`dev-main` is the obvious target and it resolves. Changing only the name would
leave the requirement pointing at a branch that is gone, and the failure would
appear at the next `composer update` rather than at the push, which is a worse
place to find it.

**One more consequence for the ordering.** cmdstanphp's `main` is five commits
ahead of `origin/main` and already carries `phpstats/cmdstanphp`, while
`origin/main` and `origin/family-renames` both still say `cmdstan/php`. So
cmdstanphp joins phiz, phibble, and this site in the set that has to reach
origin together, and this site's line 11 cannot be corrected to
`phpstats/cmdstanphp` before cmdstanphp's `main` is pushed, because until then
the new name resolves from nowhere.

**A caution about the family-wide survey.** The juphyter group surveyed every
`composer.json` in the family for the old phiz name and found two files. That
survey cannot see this, because cmdstanphp's old name is the unrelated string
`cmdstan/php`. Any blast-radius count for these renames has to search for both
old names, and for `erikwestlund/phibble` and `erikwestlund/phplyr` in phiz's
`repositories`, rather than for one.

### How much of task 15 is already done, measured across all nine repositories

Surveyed every family `composer.json`, comparing `main` against `origin/main`
for the package name and for every family dependency, rather than grepping for
one old name. This corrects two things in this plan and narrows the ask.

**Only two name renames are unpushed.**

    phiz         main phpstats/phiz          origin/main erikwestlund/phiz
    cmdstanphp   main phpstats/cmdstanphp    origin/main cmdstan/php

Every other package's name is already correct on `origin/main`: phibble,
phplyr, phpolars, phit, and phit-polyfill all publish as `phpstats/*` today.

**phibble's unpushed change is its own `require-dev`, not its name.** Its
`origin/main` already says `phpstats/phibble`, and what is two commits ahead is
`require-dev` moving from `erikwestlund/phiz: dev-main` to
`phpstats/phiz: dev-main`. A consumer never installs a dependency's
`require-dev`, so this cannot break this site or juphyter. phibble needs the
push for its own CI, which installs its `require-dev` from the checked-out
branch. Credit to `2-juphyter-1-plan-review-azure-jaguar` for that distinction,
which this plan had wrong: task 15 described phibble's `require-dev` as
breaking "the other repo's dependency resolution", and it does not.

**phiz's stale `repositories` URLs are already fixed.** Task 15 says phiz
carries two URLs pointing at `github.com/erikwestlund/phibble` and
`erikwestlund/phplyr` and that they go in the same commit. On phiz's `main`
both already read `github.com/phpstats/phibble` and `github.com/phpstats/phplyr`.
That item is done and should come out of the brief rather than being handed to
a worker.

**phpstats.org is the only consumer still naming an old package.** Its
`composer.json` carries `cmdstan/php: dev-phpstats-family` and
`erikwestlund/phiz: ^0.3`. juphyter's `suggest` names `erikwestlund/phiz` in
descriptive text, which installs nothing and which juphyter's own held commit
fixes.

**So the remaining work is smaller than the blast radius suggests.** Two
renames to push, phiz and cmdstanphp. One push for phibble's own CI. Two lines
to change in this repository, one of which also needs its branch corrected. The
renames themselves exist across five repositories and are simply unpushed, so
the ask is to push work that is already done rather than to undertake a rename.

### Pre-check of the site's composer change, before it is written

The juphyter planner relayed that Erik authorized resolving the renames through
`0-reconcile-royal-dingo`, and sent reconcile a four-step sequence whose only
code change is this repository's `composer.json`. That step is this group's, so
it was worth testing before a worker writes it. Tested in a scratch directory
with VCS repositories pointed at the local checkouts, whose `main` branches
already carry the new names, under this site's exact stability settings
(`minimum-stability: stable`, `prefer-stable: true`):

    require phpstats/phiz:^0.3  +  phpstats/cmdstanphp:dev-main
      Locking phpstats/cmdstanphp (dev-main b3ae0e2)
      Locking phpstats/phiz (v0.3.0)

Both resolve. The one uncertainty worth settling was whether a `dev-main`
constraint is acceptable under `minimum-stability: stable`, and it is, because
an explicit root requirement carries its own stability. The site already relies
on that for `cmdstan/php: dev-phpstats-family`, so the change preserves an
arrangement that works rather than introducing one.

So the edit is correct as written: line 11 becomes
`"phpstats/cmdstanphp": "dev-main"` and line 12 becomes
`"phpstats/phiz": "^0.3"`, and the only remaining variable is the push. A
worker can write it before the push lands, expect `composer update` to fail
until then, and verify after. The brief should say so, because otherwise the
failure reads as a mistake in the edit.

**The version question is settled by the mechanism rather than by a decision.**
Keeping `^0.3` requires no re-tag, and the relayed sequence keeps it. Nothing
further is needed.

**One cosmetic item already owned.** phiz's `docs/_quarto.yml` sets `repo-url`
to `github.com/erikwestlund/phiz`, and the rendered HTML under `phiz/docs/`
carries the old owner in its "View source" and "Report an issue" links. Task 16
already owns phiz's remaining org references with a full docs re-render, so
this needs no new task.

### The renames landed, and task 10's gate is open

Verified against the remotes rather than taking the report, because task 10's
gate depends on it.

    phiz         origin/main  phpstats/phiz
    cmdstanphp   origin/main  phpstats/cmdstanphp

phpstats.org's `origin/main` requires `phpstats/cmdstanphp dev-main`,
`phpstats/phibble ^0.3`, `phpstats/phiz ^0.3`, and `phpstats/phplyr ^0.3`, and
a search of that manifest for `erikwestlund` and `cmdstan/php` returns nothing.
Every family repository is level with origin except juphyter, which is one
commit ahead locally; that is the juphyter group's own repository and does not
touch this site.

**The measurement held against the real remote.** The pushed lock records
`phpstats/phiz v0.3.0` at source reference `5d2f7a65c243`, which is the same
commit `erikwestlund/phiz v0.3.0` resolved to before the rename. The tag did
not move and only its name changed, which is what the dry run predicted.
`phpstats/cmdstanphp dev-main` locked at `b3ae0e2519e8`, the same reference the
pre-check in the previous section produced.

**Task 10 can now pin.** `site/site.json` is present on `origin/main` in all
three packages, at these commits:

    phibble      3298b97
    phiz         f304f45
    cmdstanphp   b3ae0e2

phiz's site-content commit `618416a` is an ancestor of `origin/main`, so the
content task 10 needs is published. phiz's `site/` holds `site.json` alone,
with no benchmarks, verifications, vignettes, or data, which matches this
plan's earlier finding that phiz ships no script vignettes. Task 10's gate
"not before task 15 lands" is satisfied, and its remaining acceptance is
unchanged: it records that phiz ships 18 of its 19 docs pages, with
`docs/gallery.html` knowingly absent for want of a `.qmd`.

**Task 16 is now the only work naming the old owner.** phiz's
`docs/_quarto.yml` `repo-url` and the rendered "View source" and "Report an
issue" links. `cmdstanphp/notes/phiz-readiness.md` also names it and is being
left alone deliberately as a dated historical note, which is right.

### Task 18: juphyter on the site, and what juphyter must ship first

Erik reports the kernel is ready. The kernel is; its site content is not. Three
things the site needs are absent from juphyter's `origin/main`, and they belong
to juphyter's repository rather than to this group. Verified against
`origin/main` at `e00c6a9`.

**What the site shows for juphyter today is wrong.** `/juphyter` is driven
entirely by `config/projects.php:113`, which carries `'status' => 'in-progress'`,
`'status_label' => 'In progress'`, and `'docs' => null`, and whose overview ends
with "The kernel is in progress. There is nothing to install yet, and this page
will say what it does as the work lands." That sentence is now false. juphyter
installs with `composer require --dev phpstats/juphyter` and
`vendor/bin/juphyter install`, and it has six documentation pages. This is the
one item worth doing immediately, because it is a false statement on a public
page and it needs nothing from juphyter's repository.

**Blockers that belong to juphyter, not here.**

1. *No `site/` directory, so no `site.json`.* `git ls-tree -r origin/main site/`
   returns nothing. Every field the package pages use comes from that file:
   tagline, summary, install, requires, repository, example, and `docs.nav`.
   Without it `site:sync` has nothing to read and `/juphyter` keeps falling back
   to `config/projects.php`.
2. *No rendered `.html` committed beside the `.qmd`.* `docs/` on `origin/main`
   holds `_quarto.yml` and six `.qmd` and no HTML. `DocsImporter::discover()`
   requires an `.html` with a `.qmd` beside it, so it would find zero pages. The
   family convention is to commit the rendered HTML next to the source, and
   juphyter does not follow it yet. Its `output-dir` is `_site` like every other
   package, so the same render-then-copy step the others use applies.
3. *`execute: freeze: false`.* Four of the six pages carry `jupyter: php`, so
   those pages execute on every render and building the docs needs the kernel
   plus phibble, phplyr, and phiz. `scripts/render-docs` arranges that for
   whoever has the stack. Setting `freeze: auto` and committing `_freeze/` would
   let a prose fix or a docs build succeed with only a stub kernelspec, which is
   the measured result recorded earlier in this plan. That is juphyter's call,
   not this group's, but it is worth their deciding deliberately rather than by
   default.

**What this group does once juphyter ships those.** Add a `content` pin for
juphyter in `config/phpstats.php`, which currently pins only phit, phpolars, and
phplyr, and sync it. Then juphyter becomes the first package whose synced docs
carry jupyter-engine cell output, which makes the cell-styling task recorded
earlier in this plan a prerequisite rather than a follow-up: `div.cell`,
`div.sourceCode.cell-code`, and `div.cell-output.cell-output-display` arrive
unstyled, and the kernel's own `--jp-*` variables are the hook for theming its
tables.

**One judgment call for Erik.** `config/phpstats.php:68` notes that a package
with no `explorer` entry, such as juphyter, gets no panel on the landing page's
package explorer. Every other package's panel shows an example from
`resources/examples/packages/{key}.php` with its printed output. A kernel does
not have an API to call in the same way, so either juphyter stays off the
explorer, or its panel shows something else, such as a notebook cell and its
rendered result. This plan does not decide it.

**Ordering.** The `config/projects.php` correction has no dependencies and
should go first. The pin and sync wait on juphyter's `site.json` and committed
HTML. The cell styling must land with or before the sync, or juphyter's docs
pages arrive unstyled on a public host.

### Task 16 is larger than stated, and the host decision breaks the importer

**Task 16's scope was wrong, and the juphyter planner's wider sweep is why.**
This plan described it as phiz's `_quarto.yml` `site-url` and `repo-url` plus
the rendered HTML's "View source" links. `phiz/docs/index.qmd:32` carries four
more in one sentence, all under `erikwestlund`: php-stats, phibble, phplyr, and
cmdstanphp. The important part is that `php-stats` is phit's old package name,
so this is a rename and not only a reowned link. It appears in prose beyond
that one line:

    phiz/docs/index.qmd:27        "php-stats model results"
    phiz/docs/index.qmd:32        the four old-owner links
    phiz/docs/ggplot2.qmd:133     "statistics helpers for php-stats fits"
    phiz/docs/statistics.qmd:5    "the arrays that php-stats returns"
    phiz/docs/statistics.qmd:7    "produced by php-stats from the mtcars data"
    phiz/docs/statistics.qmd:57   "php-stats evaluates the information matrix"

So task 16 is a prose pass over phiz's docs naming phit correctly, plus the
full docs re-render this plan already requires, rather than two lines of YAML.
It also sits next to the `stats_*` against `fit_*` function-name mismatch this
plan already recorded, and whoever does one should see the other.

**New task: the importer assumes docs are published on GitHub Pages.** Erik has
told the juphyter group that docs will be served from `{package}.phpstats.org`
rather than `phpstats.github.io/{package}`. `App\Content\DocsImporter` is built
on the other assumption in three places:

    :143  the regex that turns an inter-package docs link into a
          phpstats:// placeholder matches only
          https?://(?:erikwestlund|phpstats)\.github\.io/{repo}/...
    :178  an unresolvable page link falls back to
          https://phpstats.github.io/{pagesRepo}/{path}
    :199  a missing asset falls back to the same host

The consequences are different for each. A docs link written as
`{package}.phpstats.org` will not match line 143, so it passes through as an
absolute cross-host URL instead of becoming a placeholder. That is not broken
in production, but it defeats the host-aware rewriting the placeholders exist
for, so in `PHPSTATS_PATHS=true` mode and on tunnel previews those links leave
the preview and point at production. Lines 178 and 199 are worse: they are
already producing dead links today, because only cmdstanphp has a Pages
workflow, so `phpstats.github.io/phibble/...` does not exist to fall back to.

The fix is for the importer to build both the match and the fallbacks from
configuration rather than from a literal host, so the same code serves whatever
`PHPSTATS_PACKAGE_HOST` is set to and the regex keeps accepting the github.io
forms already committed in synced HTML. This needs doing before any package
changes its `site-url`, or the links in that package's docs silently stop being
rewritten.

**The explorer panel: adopt the form, not the data.** The juphyter planner's
answer to what a kernel's panel should show is right in shape. A kernel has no
API to call the way a library does, so the honest panel is one PHP cell and the
output a front end drew from it, which is also the only panel whose output comes
from the mechanism being advertised rather than from this site's renderer. Their
specific suggestion is the first two cells of `juphyter/docs/index.qmd`, and
those use `Phiz\Datasets::mpg()`. The `phpstats-style` skill requires realistic
application data such as the `orders` table instead of textbook datasets, so the
panel should keep the form and use `orders`. Their cell also imports
`Phplyr\summarise`, where the same skill asks for `summarize()` when an API
offers both.

**Freeze, recorded for the other packages.** The juphyter planner's
recommendation is `freeze: auto` with `_freeze/` committed, plus a CI step that
removes `_freeze/` before rendering, so a contributor needs only a stub
kernelspec while CI still executes every cell. That is a better answer than
either setting alone and it applies to any package this plan asks to execute its
docs. One detail worth keeping: Quarto 1.10.18 has no `--no-freeze` flag. The
only freeze-related flag is `--use-freezer`, which forces the opposite, so
deleting the directory is the way to force execution.

### Correction: the importer's fallbacks are latent, not live

The previous section said lines 178 and 199 of `DocsImporter` "are already
producing dead links today". That is wrong, and it was my claim rather than
anyone else's. Measured in the main checkout across all synced content, which
is phit, phplyr, and phpolars:

    occurrences of phpstats.github.io in resources/content/   0

Nothing in the committed synced HTML reaches either fallback. The path would
produce a dead link if it were reached, because only cmdstanphp has a Pages
workflow, but no link or asset currently falls through to it. The reasoning that
led me wrong was sound as far as it went, that the fallback host does not exist,
and I did not check whether anything actually takes the branch. So the importer
task stands on line 143's preview correctness and on the gap below, and it is
not urgent repair work.

**The juphyter ordering concern does not apply, and the juphyter planner was
right.** Every sibling reference in juphyter's docs is a repository link on
`github.com`, not a docs link on `github.io`, so line 143 never matched them and
cannot stop matching them. Its two `phpstats.github.io` links are both in
`README.md`, which `DocsImporter` never reads. There is no ordering risk for
anything juphyter has committed. The concern is real only for the first page
that writes an inter-package docs link in the new host form, which is a reason
for the importer task to land before that happens rather than a reason for
juphyter to hold anything.

**A gap the host question uncovered, independent of it.**
`DocsImporter::PAGES_REPOS` lists phit, phit-polyfill, phibble, phplyr, phiz,
phpolars, cmdstanphp, and CmdStanPHP. **It has no `juphyter` entry.** Line 144
reads `self::PAGES_REPOS[$m[1]] ?? null` and returns the href unchanged when the
lookup misses, so once juphyter's docs are synced, any link in them written in
the `github.io/juphyter/...` form would pass through as an absolute URL instead
of becoming a `phpstats://` placeholder. That is a one-line addition and it
belongs with the importer task. It also means the map has to be kept in step
with the package list, which is the same duplication problem as the two navs, so
deriving it from `config/phpstats.php` is worth considering in the same change.

**Task 18's blockers hold against juphyter's unpushed work too.** The three
prerequisites above were measured against `origin/main` at `e00c6a9`. Reconcile
has merged five further commits that it is deliberately holding, because Erik's
push approval covered the rename sequence and these are later docs work. Checked
against juphyter's local `main` at `363ad9b`: still no `site/` directory, and
still zero committed `.html` under `docs/`. All five are documentation and CI
changes, so pushing them would fix the README's links, which now correctly read
`juphyter.phpstats.org/install.html` and `.../limitations.html`, and would not
advance anything task 18 needs. The earlier note that juphyter's README fix
looked unpushed was reading an accurate but stale `origin/main`; the fix is real
and held.

### Task 20: Pint lints content the site does not own

This was a review note in two rounds and never a task, which is why nothing
has happened to it. Writing it down here, with an owner, is the fix.

`pint.json` reads:

    {"preset": "laravel", "exclude": ["resources/examples"]}

`pint --test` fails on 21 files. Nine are under `resources/content`, the
vignette scripts that task 10 synced from phplyr, phit, and phpolars. The site
cannot fix those files, because `site:sync` rewrites the directory from each
package's pinned commit, so any fix Pint applied would be reverted by the next
sync and would also put the site's formatting opinions on another repository's
code. `resources/examples` is already excluded for the same reason in spirit,
that it holds example code written to read a particular way, and
`resources/content` was never added because it did not exist when `pint.json`
was written.

**Deliverable.** `resources/content` added to the exclude list.

**Files owned.** `pint.json` only.

**Acceptance criteria.** `vendor/bin/pint --test` reports no failure under
`resources/content`. The remaining 12 failures are in files no task in this
group touched and they are out of scope here; list them in the handoff so they
are visible rather than fixing them. Do not pass `--dirty` or run the fixer
over the repository, because that would reformat those 12 files as a side
effect of a one-line config change.

**Model.** The smallest available. This is one line and a test run.

**Sequencing.** Independent of everything else. It can go at any time.

### Status of the "frame" rule, measured

For the record, because the rule now lives in three places and I have given
partial accounts of it across rounds:

| Where | Enforced by | State |
|---|---|---|
| `config/phpstats.php`, `config/projects.php` | `ProseTest` | clean |
| `resources/pages` | `ProseTest` | clean |
| `resources/views/vignettes/*.blade.php` | `ProseTest` | clean |
| the other Blade views | nothing | 5 uses, all correctly exempt |
| `resources/content/{package}` | nothing | 35 uses, 34 to fix, task 19 |

The vignette views were added to `ProseTest` in frosty-wasp's `6fdedee`, in the
same commit as the three string fixes, which is what I asked for. The scan is
scoped to `views/vignettes` rather than all of `resources/views`, and that is
the right scope: the five remaining uses elsewhere are `@props(['frame'])` in
`frame-table.blade.php`, three Blade comments, and `layout.blade.php`'s "page
frame", which is a different sense of the word that the rule should not reach.
Widening the scan to all views would have to special-case all five, and a test
with five exemptions stops being a rule.

Task 19 must widen the scan to `resources/content` as its **last** step, after
the packages' own commits are pushed and the pins are moved. Widening it first
fails the suite on content that no commit in this repository can change.

### Correction: task 19 is 34 uses, not 111

My figure of 111 was wrong, and in the same way my phiz chart count of 187 was
wrong: I matched the substring "frame", which counts every `DataFrame`,
`PolarsTable`-adjacent type name, and `frame-table`, rather than the noun. The
orchestrator's count of 35 is right. Measured independently over
`resources/content` with `<pre>`, `<code>`, `<script>`, and `<style>` dropped
and the same word-boundary rule `ProseTest` uses:

| Package | Uses | Files |
|---|---|---|
| phibble | 23 | 5 (`getting-started.html` has 12) |
| phplyr | 5 | 2 |
| phpolars | 4 | 3 |
| phit | 2 | 1 |
| phiz | 1 | 1 |
| cmdstanphp | 0 | 0 |
| **Total** | **35** | **12** |

The raw count with nothing stripped is 45, so 10 of them are inside code, and
the bare substring count is what produced 111.

**One stays.** phit's `formulas.qmd` says "as in R's model frame", which is R's
own term for the object. Its other use, "a seeded synthetic frame with factors",
is the plain noun and should change. So 34 need fixing.

**The `.qmd` sources carry exactly the same 35, in the same files, with the
same per-file counts.** Nothing drifted between source and rendered output, so
the work is an edit to each package's `.qmd` and a re-render, with no
HTML-only cases to hunt.

**Two items are already fixed and must not be handed out again.** phibble's
four uses in `site/site.json` were fixed by this group's own worker in
`1-website-docs-4-tasks-upper-ant`, commit `63abd7a`, and cmdstanphp's one by
`1-website-docs-6-tasks-opal-grizzly` in `c78fd31`. Both are unmerged, so a
reader of either package's `main` still sees them. Giving task 19 `site.json`
would collide with commits this group already approved.

**The `docs.json` hits are generated.** phibble's one and phpolars' one are
heading text that `DocsImporter` extracts, such as phpolars' "Creating frames",
so they are the same uses already counted in the HTML and they correct
themselves on re-render. They are not separate work.

**Two vignette scripts are separate work.** `phpolars/site/vignettes/phplyr-on-polars.php`
says "a Polars frame from phpolars" and `time-series.php` says "The frame holds
a year of daily unit sales". Both are prose inside the script's returned
blocks, not comments, so they reach the page.

**phiz's single use is a capitalization error as well.** `large-data.qmd` says
"a million-row phibble Frame". phibble's type is `DataFrame`, so this is wrong
twice over.

### Task 22: juphyter's panel in the Data & Workflow explorer

Erik asked for a Quarto kernel section in Data & Workflow. The layer already
lists juphyter, with the tagline "PHP in Jupyter notebooks and Quarto
documents", so the menu and the package page are not the gap. The gap is the
landing page's package explorer, where the other six packages each get a
summary in the list and three panels on the right, and juphyter gets nothing.

`config/phpstats.php` says so itself: "A package with no entry here, such as
juphyter, gets no panel." `package-explorer.blade.php` intersects the layer
order with the explorer keys, so a package without an entry is dropped from
both the list and the panels.

**Why this is not just another explorer entry.** Every existing entry resolves
to `resources/examples/packages/{key}.php`, and `Example::path()` appends
`.php` while `Example::display()` strips the opening tag, the setup above a
`// ---` line, and the final `return`. All of that is PHP-specific. juphyter's
example is not a PHP script; it is a Quarto document, whose point is the front
matter line `jupyter: php` and a PHP cell with `#|` options. Running it is a
`quarto render`, which a page request must never do.

**The approach.** Add an optional `source` key to an explorer entry, naming a
file and its highlight language, such as
`['file' => 'packages/juphyter.qmd', 'language' => 'markdown']`. When an entry
has `source`, the explorer highlights that file through `App\Content\Highlight`
instead of calling `Example::code()`. The eight existing examples keep their
present path untouched, so this adds a branch rather than changing the contract
the other six panels rely on.

Pair it with `stored`, the mechanism cmdstanphp already uses through
`[\App\Data\SchoolPosterior::class, 'explorerSummary']`. juphyter's stored
callable reads a committed fixture holding the cell output that
`quarto render` produced from that exact document, captured once by a human or
a worker with juphyter and Quarto installed, the way the Stan draws were
captured. The house rule holds: never invent output, and compute it with the
package's own code.

**What the two panels should show.** The code panel shows the document source:
the front matter with `jupyter: php`, then one PHP cell that loads the `orders`
table and prints it. The output panel shows what Quarto emitted for that cell.
Showing a data frame alone would make the panel indistinguishable from
phibble's, so the output has to be the rendered cell, which is the thing
juphyter produces and no other package does.

**Deliverable.** juphyter in `explorer` with `summary`, `example`, `does`,
`output`, `source`, and `stored`; the source document; the fixture and the
class that reads it; and the explorer's `source` branch.

**Files owned.** `config/phpstats.php` (the `explorer` array only),
`resources/views/components/package-explorer.blade.php`,
`resources/examples/packages/juphyter.qmd`, a new class under `app/Data`, its
fixture under `database/data`, and `tests/Feature/LandingPageTest.php`.

**A test asserts the current absence and has to be inverted.**
`LandingPageTest::test_juphyter_is_on_the_site_without_an_explorer_panel` at
line 63 asserts `assertDontSee('package-tab-juphyter')` and
`assertDontSee('package-code-juphyter')`. That test is correct today and wrong
the moment this lands. Replace it rather than delete it: assert the panel is
present, that its code panel shows the `jupyter: php` line, and that its output
comes from the fixture rather than a render. Leave
`PackagePagesTest`'s "juphyter is the only package with no content to install
from" alone, because this task gives juphyter no `site.json`.

**Acceptance criteria.** The landing page lists juphyter under Data & Workflow
with the other three, its panels render, the page runs no Quarto and no
notebook, and `Example::runs()` records no run for juphyter. The full suite
passes. The fixture's provenance is written in the class docblock: the juphyter
commit, the Quarto version, and the command.

**Model.** A strong one. The `source` branch touches the explorer every package
depends on, and the fixture has to be captured honestly rather than typed.

**Sequencing.** After task 18, which corrects juphyter's `config/projects.php`
entry, because both touch juphyter's description and task 18 is already in
review. Independent of tasks 19 and 21.

**Open question for Erik, not blocking.** The four About bullets should say
what the kernel does, and juphyter's own docs are the source: the two-line VCS
install with `--dev`, `vendor/bin/juphyter install`, the `jupyter: php` front
matter, cell options through `#|`, session state across cells, and the
limitations page. Whether the bullets mention the limitations is his call; the
other six packages' bullets describe capabilities only.
