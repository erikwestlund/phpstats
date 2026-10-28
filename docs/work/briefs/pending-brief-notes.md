# Notes to fold into later briefs (orchestrator's running list)

Source: planner reviews and worker handoffs, 2026-10-19.

## Task 5
- Move `test_markdown_pages_render_blade_outside_code_only` from LandingPageTest into the new `RootPagesTest.php`.

## Task 9 (sync from a local checkout)
- Also owns `app/Console/Commands/SyncSiteContent.php:97`: carry the nav item's `section` key through (it currently keeps only title and path).
- Also owns `resources/views/package/docs.blade.php`: hide the group heading when `docsGroups()` returns exactly one group (convention in `config/phpstats.php`: a menu with one layer shows no heading). Task 12 must treat that file as taken.

## Task 10
- DocsTest also covers benchmarks and verification.
- phiz charts (Gap 3): `package/docs.blade.php` must load phiz's scripts (`@phizScripts`) when page HTML contains `data-phiz`; add one rule for `.phiz-figure` (margin, overflow-x: auto). The importer drops head includes. Coordinate with task 9's edit to the same view file.
- Depends on 11a landing first (summary list support), and on reconcile pushing phibble, phiz, cmdstanphp.

## Task 11a (new, split from 11)
- `Package::summary()`: prefer `site['summary']` when a non-empty list; else fall back to `project['overview']`. Cases: list, string, no site.json.
- `package/overview.blade.php`: strip leading `# A tibble:` line from `example.output` (phibble and phplyr ship it).
- Tests in PackagePagesTest additions.
- Single call site: `resources/views/package/overview.blade.php:87`.

## Open decisions awaiting planner (phiz review)
- install line `erikwestlund/phiz` vs `phpstats/phiz`; code-tab label for chain-only packages; gallery.html.

## Planner rulings, round of phiz review (2026-10-19)
- Erik: package names become `phpstats/*` (phiz, CmdStanPHP -> `phpstats/cmdstanphp`); the composer.json renames are NOT this group's work (raise with reconcile or group 3).
- Erik: install blocks use two-line VCS form (`composer config repositories.X vcs https://github.com/phpstats/X` + `composer require phpstats/X:dev-main`). phibble: task 6 round 3. phiz: task 7 round 2. CmdStanPHP currently `cmdstan/php:dev-main`, needs `phpstats/cmdstanphp` form once renamed. phit, phplyr, phpolars site.json carry the broken one-line form: new "task 14", ask Erik if this wave.
- Task 10 acceptance: phiz not pinned until phiz's composer.json declares `phpstats/phiz`; phiz ships 18 of 19 pages (gallery knowingly absent).
- Task 11a: also `Package::example()` must use `($example['chain'] ?? null) ?: null`, with a test for a site.json that has `pipe` only.
- Task 9: SyncSiteContent line 97 carries `section`; docs.blade.php single-group heading suppression.
- New task 13 (after 10): `docs.blade.php` loads phiz scripts when page has `data-phiz`; `.phiz-figure` rule. docs.blade.php owners in order: 9, 13, 12.
- Gated on task 10 for group 3 / reconcile: phiz docs say `stats_*`, phit says `fit_*`/`predict`.
- Idea for later: overview renders a chart for phiz via `<x-phiz>`.

## Task 4 review follow-ups (planner, 2026-10-19)
- Task 11a depends on task 4 (Package::docsPaths() added in 67f62c2); build on task 4's version of Package.php.
- about.md is edited by task 2 (body) and task 4 (front matter `description:`): tell reconcile to expect both.
- For Erik (operational, not code): a 1200x630 og:image would be the best link-preview upgrade (design decision); the sitemap lists package-host URLs from the root host, so all hosts must be verified under one Search Console property, or switch to a sitemap per host plus an index.
- Later one-liners: 301 `/docs/index` -> `/docs`; move the landing page description sentence from layout.blade.php to config/phpstats.php when that file is next touched.
- Task 5 brief: optional sentence on /get-started about starting from an empty directory (`composer init` first), since `composer config` needs a composer.json.
- Planner's cross-repo suggestion after this wave: a render-and-diff CI step in all five Quarto packages (committed HTML must match .qmd output; `output-dir: _site` trap).

## Round of 2026-10-19 (later)
- Task 13 widened: "phiz script loading, site-wide": `@phizScripts` in layout.blade.php + `phiz.inject_scripts => false`; remove task 3's `ScriptState` workaround in `Example::output()` (it is @internal in phiz; cached flag encodes page order); docs pages with `data-phiz` get the runtime; `.phiz-figure` rule. Owns layout.blade.php and config/phiz.php for this. Consider also letting the phiz overview render a chart.
- Task 16 (after task 10): phiz repo, replace remaining erikwestlund org/Pages references (docs/_quarto.yml site-url and repo-url, robots.txt, sitemap.xml, docs/index.qmd:32 four dead links, 20 rendered html), full re-render with `quarto render docs` + rsync, not committing fabricated sitemap lastmod.
- Task 14 launched (worker 8, grand-camel): install blocks for phit, phplyr, phpolars.
- Pins: task 10 pins cmdstanphp >= b3ae0e2, phiz >= resulting commit of a140439/48ccf87, phibble >= 3298b97 (all local, unpushed; push needs Erik's approval via reconcile).
- Possible follow-ups for Erik: branch-alias in phibble and phplyr so every package can use dev-main; render-and-diff CI for Quarto packages (exclude sitemap.xml); og:image; Search Console hosts; `/docs/index` 301.
- Task 4 gets PHPSTATS_INDEXABLE; Task 3 gets flush in stan:fit-school-posterior.

## Later rulings
- Task 5 `/get-started`: add ONE sentence covering (a) `composer config` needs an existing composer.json (run `composer init` first when starting from nothing), and (b) Composer may prompt for a GitHub token on anonymous API limits while reading VCS repositories. One home for it; do not repeat in package manifests. Task 5 also moves `test_markdown_pages_render_blade_outside_code_only` into RootPagesTest.
- When packages are published (Packagist/PIE), ALL install blocks go back to the short form: `pie install phpstats/phit`, `composer require phpstats/x`. One story, not seven. Remind Erik at the end.
- Task 9 reproducer for 11a: phibble site.json `summary` is a list => overview.blade.php 500 (`htmlspecialchars(): Argument #1 must be string, array given`). Task 9's worker guards `$sync['local']` in overview.blade.php and vignette.blade.php; 11a builds on that.
- Task 4 comment on robots route: don't delete the second registration (see its commit).
- Task 11a also: dependency on task 4 being merged first (Package::docsPaths()).

## Plan revision (planner, 2026-10-19, "Executing PHP in Quarto docs")
- Task 13 acceptance CORRECTED: phiz puts the Vega-Lite spec in a `data-phiz` attribute (survives import); the importer strips phiz's inline LOADER `<script>` (no type). Vega/vega-lite/vega-embed CDN tags without the loader leave every chart a silent empty div. Acceptance must require phiz's loader specifically, and the test must assert a chart container is initialized, not that script tags exist. Fix unchanged: `@phizScripts` in layout.blade.php (calls PhizServiceProvider::scripts() -> Assets::scripts(bool $loader = true), sets ScriptState::included). Never copy the loader text into Blade. Versions: vega 6.4.0, vega-lite 6.4.3, vega-embed 7.3.0, hashes in Assets::INTEGRITY.
- NEW task (not urgent, no dependency until a package converts): theme Quarto jupyter-engine cell output (`div.cell[data-execution_count]`, `div.sourceCode.cell-code`, `div.cell-output.cell-output-display`). Kernel markup ships a scoped `<style>` against `--jp-*` variables with fallbacks; define those variables for layer colors, do not override kernel rules.
- NEW task: derive `docs.nav` from `_quarto.yml` at sync time (cross-package rule, agreed with juphyter group). phit: 52 rendered .qmd, 16 in nav. Minimum: sync fails when a rendered page is missing from the nav. Task 9 already carries `section`.
- DECISION FOR ERIK before 11b goes out: if nav is derived, phit's 36 orphans are fixed by the sync, and hand-written 11b nav entries would be discarded. Do not dispatch 11b's nav part until Erik decides the order. (11b's example.output part is unaffected.)
- Notes: no Quarto extension needed (kernelspec + `jupyter: <name>` front matter). phiz requires PHP 8.5; phibble/phplyr floor at 8.3, so a plotting package's kernel needs 8.5+.

## Nav derivation detail (planner, 2026-10-19)
- phit measured: 52 .qmd, 51 in _quarto.yml, 16 in site.json nav; `data/lmm/index.qmd` is in neither. Derivation picks up 35 of the 36 orphans (15 notebooks/, 9 benchmarks/, 6 verifications/, 5 top level).
- Design call the nav task must settle: 15 of those duplicate phit's existing Benchmarks (8 JSON) and Verification (9) sections. The task needs an exclusion rule: exclusion list in site.json, exclusion by directory, or treat _quarto.yml sections as the unit and let the site pick. Decide in the task, not the plan.
- Question for Erik is therefore: derive nav + settle one exclusion rule (fixes 35 pages across all packages), or hand-write phit's 36 and keep two copies of every nav. Planner recommends derive.

## Task 15 coordinated landing (planner, 2026-10-19)
- Verified in /Users/erik/Projects/phpstats/phpstats.org/composer.json: line 11 `cmdstan/php: dev-phpstats-family`, line 12 `erikwestlund/phiz: ^0.3`. Both old names stop resolving once phiz / cmdstanphp main are pushed. Task 15 gains the site's composer.json (phiz -> phpstats/phiz ^0.3; cmdstan/php -> phpstats/cmdstanphp, constraint to settle; check lock file and the `repositories` block at ~line 95).
- No re-tag needed: v0.3.0 answers the new name once main is pushed. Planner recommends staying within 0.3.x; Erik to confirm. Do NOT dispatch the site composer.json fix until the version question is settled.
- phiz, phibble, the site (and cmdstanphp, and juphyter's held commit) must reach origin together: one coordinated push. Task 10's pins need phiz main pushed (618416a, ed768f3, f304f45).

## Site composer.json: two fixes on line 11 (planner, 2026-10-19)
- `cmdstan/php` -> `phpstats/cmdstanphp` AND `dev-phpstats-family` -> `dev-main` (branch `phpstats-family` no longer exists on the remote; remotes are family-renames, main, site-content; cmdstanphp has no tags, so dev-<branch> is the only form). composer.lock pins 59baa928ecc5 so install works today; `composer update` already fails. Brief must name both changes, plus a `composer update phpstats/cmdstanphp phpstats/phiz` check once pushed (cannot resolve before cmdstanphp main is pushed).
- Blast radius must be counted with four strings: `erikwestlund/phiz`, `cmdstan/php`, `erikwestlund/phibble`, `erikwestlund/phplyr`.

## Task 15 narrowed (planner, 2026-10-19)
- Only phiz (-> phpstats/phiz) and cmdstanphp (-> phpstats/cmdstanphp) name renames are unpushed. phibble's unpushed change is only its require-dev (cannot break consumers; needed for its own CI). phiz's repositories URLs are ALREADY phpstats/* on main: drop that item from any brief.
- Remaining task 15 work = this repo only: composer.json line 11 -> `phpstats/cmdstanphp: dev-main`, line 12 -> `phpstats/phiz: ^0.3`, regenerate composer.lock. Brief must say: make the change now; `composer update` cannot resolve until phiz and cmdstanphp main are pushed, so verify after the push (do not report BLOCKED). Hand-edit lock only if the owner approves; otherwise regenerate after push.

## Erik's answers + landed pushes (2026-10-19)
- Erik: renames stay in 0.3.x; push approved; DERIVE nav from _quarto.yml (11b nav entries dropped; new nav task settles exclusion rule for the 15 notebooks etc.).
- Reconcile pushed phibble, phplyr, phit, phpolars, cmdstanphp, phiz, then phpstats.org 8fcac91 (composer.json edited by reconcile; task 15 DONE, do not dispatch). Pins available: phibble 3298b97, phiz f304f45, cmdstanphp b3ae0e2 (+ phit 3840541, phplyr 646f029, phpolars 67e2f29). phiz site/ holds site.json only (no vignettes/benchmarks dirs expected; not a sync failure).
- Task 18 (dispatchable now): correct config/projects.php juphyter entry (status in-progress -> real, docs null -> real, overview paragraph 4 false: juphyter installs with `composer require --dev phpstats/juphyter` + `vendor/bin/juphyter install`, six doc pages). Owns config/projects.php only; sequence with task 17 (sweeps same file) and obey the "frame" rule. NOT dispatchable: juphyter pin/sync (no site/, no committed html, freeze:false). Explorer panel for juphyter is Erik's call (one PHP cell + rendered output; use orders data not Datasets::mpg(); summarize not summarise).
- Cell-styling task must land with/before the juphyter sync.
- Task 16 widened: prose pass over phiz docs naming phit correctly (`php-stats` old name, 6 places in index.qmd, ggplot2.qmd, statistics.qmd; index.qmd:32 four links), full re-render, tell worker about stats_* vs fit_* mismatch.
- Importer task (low priority, before juphyter sync): DocsImporter :143 regex, :178/:199 fallbacks built from config; add juphyter to PAGES_REPOS (or derive from config/phpstats.php). Fallbacks are latent, not live (0 github.io occurrences in content).

## Task 17 follow-through (planner, 2026-10-19)
- phibble part APPROVED (11db34f..63abd7a), MERGE sent to reconcile. cmdstanphp part (745edc2..c78fd31) READY, planner relay was dropped: fold into one later message with task 5 round 2 and the phpstats.org part.
- NEW task 19 (family-wide): bare "frame" in synced docs prose: 111 live (phibble 69, phpolars 10, cmdstanphp 9, phplyr 9, phit 8, phiz 6). Sequence AFTER the style skill gets the rule (phpstats.org part of 17). Prose only: leave code fences, inline code, printed output alone (phibble docs/backends.qmd uses are all in fences). Leave CHANGELOG.md alone (dated history), also cmdstanphp notes/phiz-readiness.md. Quarto re-render for each .qmd (+ search.json); do not commit fabricated sitemap lastmod. phibble counts outside fences: getting-started 16, types 7, laravel 6, index 3, coming-from-tibble 1, README 1. Leave heading anchor `#data-frames-and-the-phpstats-family` in cmdstanphp.
- Standing rule: any package change meant to reach the site needs a pin bump. Hold phibble and cmdstanphp pin bumps until task 19 lands, then bump once (Erik may ask for earlier).

## Task 19 corrected (planner, 2026-10-20): 34 uses, not 111
- Total 35 in 12 files of synced prose (code/pre/script/style excluded, word-boundary): phibble 23 in 5 files, phplyr 5 in 2, phpolars 4 in 3, phit 2 in 1, phiz 1 in 1, cmdstanphp 0. 34 to fix; leave "as in R's model frame" in phit formulas.qmd (R's own term); phit's other use, "a seeded synthetic frame with factors", is plain noun, fix it.
- .qmd sources carry the same 35 in the same files: edit each .qmd + re-render, no HTML-only cases.
- Do NOT touch site.json in task 19 (phibble 63abd7a, cmdstanphp c78fd31 already approved, unmerged). docs.json headings (phibble "Building frames", phpolars "Creating frames") correct themselves on re-render.
- phiz large-data.qmd "a million-row phibble Frame" -> DataFrame.
- phpolars vignette scripts (phplyr-on-polars.php, time-series.php): separate small work; both uses are prose in returned blocks.
- Task 20 approved (bd3a5ee). 6fdedee round 2 pending: ProseTest slot regex must handle `</x-slot:pipe>` closes. Hold the reconcile request for the whole frosty-wasp range (10037ed..) until round 2 passes.
- Reconcile still owes MERGE confirmation for phibble 63abd7a (cherry-pick onto 3298b97) and cmdstanphp 745edc2..c78fd31 (cherry-pick onto b3ae0e2): my message may have been dropped; resend once after hearing from reconcile.

## Task 22 (planner built it directly, 2026-10-20)
- Planner implemented task 22 at Erik's instruction: fc5c501..c060595 in /Users/erik/.polyscope/clones/48422c57/steady-orca (44c87bc plan, c060595 feature). My dispatch to frosty-wasp CANCELLED (brief deleted). Needs independent review (bubbly-panda asked, read-only), not the planner's own. Merge order: 18, 21, then 22 (rebase onto then-main). Approved: Erik allowed venv; About bullets capabilities only.
- Follow-up: deploy script does not run `examples:clear`; Example::output cache otherwise stale (separate task).
- juphyter has no staging host (nginx site + certificate missing): server work, Erik informed by planner.

## Task 22 status (2026-10-20)
- Independent review by bubbly-panda: no correctness bug; findings 1-3 fixed by planner in 48fb4b5 (final range fc5c501..48fb4b5, worktree /Users/erik/.polyscope/clones/48422c57/steady-orca, branch 1-website-docs-1-plan-review-steady-orca). 104 tests, 93 passed, 11 skipped, 3170 assertions. Findings 4 (fixture mtime not in cache key) and 5 (entity decode of raw .qmd) left on purpose.
- Merge order: 18 (c57898b), 21 (fc5c501..01c38cb), then 22 rebased onto main. MERGE for 22 goes to reconcile as a cherry-pick/rebase request after 18 and 21 land.
- Reviewer tip: review a commit from a detached checkout; a worktree with symlinked vendor loads the wrong app (autoload_psr4.php has absolute paths).
- Follow-ups: deploy script should run `examples:clear`; staging juphyter nginx host + certificate (server work, Erik).

## Group 3 overlap (from reconcile, FYI)

Group 3 (api-consistency) will clean bare "frame" in phpstats.org `app/` identifiers and error messages near the end of its sequence. Group 1 keeps `resources/` content (task 19). No `app/` renames from group 1 workers. Tasks 18, 21 and 22 are already based on fc5c501 and touch no such renames; recheck when each merges.

## Group 3 coordination (plucky-cheetah-31, replied)

Agreed split: group 3 owns identifiers, signatures, error messages and tests (incl. phpstats.org app/, phibble, cmdstanphp); group 1 owns prose in package docs and resources/. Shared hit list = group 3 task 1 inventory (family repo, docs/work/api-inventory/, branch 3-api-consistency-3-tasks-misty-vulture), awaiting Erik review. HOLD task 19 until Erik has reviewed it. Their 245 hits vs my 34 likely differ by fences/output/CHANGELOG/site.json; asked them to check. Ask for old->new names if public names change.

## Group 3 inventory review (sent)

Inventory: /Users/erik/.polyscope/clones/49454953/misty-vulture/docs/work/api-inventory/frame-hits.md. Told group 3 the classifier buckets "data frame(s)" as bare (it is allowed); asked for recount before Erik rules. Split: they take tests/ and config/ (not ProseTest.php) and Blade identifiers with their app/ renames; I take .claude/ (rule text, leave), prose in resources/pages and views; synced content is fixed in package .qmd (task 19, on hold).

## Group 3 agreements (final)

- Group 3 D2 fixes the bare "frame" in phpolars phplyr-on-polars.php; time-series.php stays with task 19.
- Send group 3 (plucky-cheetah-31) the new phpstats.org main SHA when reconcile reports tasks 18, 21, 22 MERGED.
- Group 3 will tell me when their package doc renders merge; then ONE re-sync (pins, resources/content, public/content).

## Follow-up from group 3 D2

D2 renames Blade component frame-table -> df-table (prop :df) in phpstats.org (branch 3-api-consistency-6-tasks-nimble-frog, on fc5c501, unmerged). .claude/skills/phpstats-style/SKILL.md:62 names frame-table and goes stale. Update that line (and AGENTS.md copy if it mentions it) AFTER D2 merges; small follow-up with task 19. Do not edit before then.

## Re-sync ownership (answered group 3)

Group 1 owns the single re-sync of all six packages after group 3 Task F is merged AND pushed. Needs pushed SHAs per package; verify sync.json has real commit and no local:true; then examples:clear; planner review; reconcile pushes only with Erik approval. Also SKILL.md:62 frame-table -> df-table after D2 merges.

## Base moved to ff20363; task 23 dispatched

Reconcile (Erik: get everything in) says all package mains pushed; phpstats.org origin/main = ff20363. Task 23 (task-23-resync.md) to bubbly-panda: rebase task 21 + re-sync six pins. frosty-wasp rebases task 18. Planner told (task 22 rebase theirs). juphyter 82c7ef5 has no site/site.json -> not pinned. phit-polyfill not synced. After MERGED: send plucky-cheetah-31 the new main SHA; update SKILL.md:62 frame-table->df-table; reconcile must not push without Erik approval (relayed "go" noted). No deploy until re-sync lands; deploy is Erik call.

## Task 18 rebased

frosty-wasp: task 18 now 50ec563 on ff20363 (ff20363..50ec563, range-diff identical to c57898b). Suite 106/95/11. Pint 18 failing (6 new are group 3 tools/phpcs). Needs planner review of 50ec563; tell planner.

## Task 22 sent to reconcile

Task 22 rebased by planner: ff20363..15bbf3a (08b9884 plan, 1964edf feat, 15bbf3a test). I range-diffed (only docblock resolution differs) and sent MERGE to royal-dingo-f1. Task 24 (deploy script runs examples:clear): planner to write acceptance criteria; brief to next free worker. Mention juphyter page test (still "has not published its site content yet") in task 23 handoff.

## Task 22 MERGED and PUSHED

Reconcile: phpstats.org origin/main = 15bbf3a (08b9884, 1964edf, 15bbf3a). 105/94/11/3246, composer lint clean. Pushed on relayed "Erik: dont ask, just go". Task 23 told to base on 15bbf3a. Still to send reconcile: 18 (50ec563) and 21+23. Tell plucky-cheetah-31 the main SHA after all land.

## Task 18 round 1: CHANGES REQUESTED (sent to frosty-wasp)

1) add get-started assertions pinning status=working (install-steps branch); 2) split 57-word paragraph 4 into two (36 + 20 words). Rebase on 15bbf3a. Planner will check diff only. Follow-up for planner: /get-started says no install lines for juphyter while /juphyter has them (plan question; do not widen task 18).

## Task 21+23 READY (bubbly-panda)

15bbf3a..f8bec62; 21 = e82a6c5+fce401d; 23 = bc7c1d7 pins, f0a138b content, a28ebca test, f8bec62 composer.lock cmdstanphp (ask planner keep/drop). Suite 118/107/11. 3 failures with phit loaded are php_stats missing, pre-existing. Preview http://127.0.0.1:8002. Pass GlmModels.php:14 "the phibble frame" to group 3 (plucky-cheetah-31). Task 18 round 2 = 484d40b awaiting planner. After MERGED: send plucky main SHA, SKILL.md:62 df-table fix.

## Task 18 MERGED and PUSHED

origin/main = 484d40b (106/95/11/3256, lint clean). Task 21+23 (15bbf3a..f8bec62) in planner review; after verdict, bubbly-panda rebases onto 484d40b (planner range-diffs), then MERGE to reconcile. Then: send plucky-cheetah-31 the main SHA; SKILL.md:62 df-table fix; GlmModels.php:14 to group 3.

## Task 21+23 APPROVED at f8bec62

Planner kept composer.lock (fixed real defect: lock b3ae0e2 vs camelCase call sites incl. stan:fit-school-posterior). bubbly-panda rebasing onto 484d40b; then MERGE to reconcile. Follow-ups from planner: (1) SyncSiteContent handle() has no try/catch -> abort mid-run, Example::flush not run (operator-local); (2) walkSidebar docblock note on `section: x.qmd`; (3) pin/lock drift: only dev-main deps (phplyr, cmdstanphp) agree; phibble/phiz lock at v0.3.0 tags vs pins; planner writes task. Planner CORRECTION: examples cache does NOT survive deploy (key includes mtimes) so no live staleness bug; task 24 shrinks, my earlier follow-up "deploy script doesnt run examples:clear" is not a bug.

## Task 21+23 final: MERGE sent

484d40b..21e565d (2b9b35c, 9c67d07 | 09e6c68, 7cf2353, 2ec59e2, 21e565d). MERGE sent to royal-dingo-f1. After MERGED: send plucky-cheetah-31 new main SHA; SKILL.md:62 df-table; GlmModels.php:14 to group 3; then final report to Erik. Queued: task 19 (hold on Erik/inventory recount), task 16, importer follow-ups, task 12, 11b, task 24 shrunk.

## Tasks 21+23 MERGED locally (21e565d), NOT pushed

Reconcile holds push: family repos are PUBLIC, asked Erik. Local main 21e565d (119/108/11/3411). Sent plucky-cheetah-31 SHA + GlmModels.php:14. Task 25 (SKILL.md frame-table->df-table) to frosty-wasp. Final report to Erik next: includes push hold (public repos) and deploy his call.

## Tasks 24-26 numbering

Planner: task 25 = pin/lock drift test (-> bubbly-panda); task 24 = example cache key from stored-entry files (-> frosty-wasp after 26). My SKILL.md frame-table->df-table fix renumbered task 26 (frosty-wasp, first). Planner plan commit dafd234. Deploy examples:clear is NOT a task: Forge deploy script, Erik, and per planner no live staleness bug (mtimes reset each release). All based on local main 21e565d (unpushed).

## Task 26 done (373ac58)

21e565d..373ac58, one word in .claude/skills/phpstats-style/SKILL.md:62 (frame-table -> df-table), I verified the diff myself (1 line). Remaining frame-table hits are ProseTest.php:62,64 (deliberate negative fixtures, leave) and the plan doc (historical). Observation: the sentence is a weaker example now. HOLD the MERGE to batch with tasks 24/25 (push is on hold anyway).

## Task 25 ready (08871bc)

21e565d..08871bc tests/Feature/PinLockTest.php; 3 mutations; full 120/109/11/3413. Sent to planner. Batch MERGE with 26 (373ac58) and 24.

## Tasks 25 + 26 APPROVED; MERGE sent

25 = 21e565d..08871bc (bubbly-panda), 26 = 21e565d..373ac58 (frosty-wasp). Planner reviewed 26 too and asks that every task commit, even single-word, goes to him before MERGE (do that: no batching on my own check). Task 24 in progress (frosty-wasp). Remaining: group 3 inventory recount/Erik ruling -> task 19; task 16; importer follow-ups; task 12; 11b; Erik: push public-repo question, Forge examples:clear (not needed), juphyter staging host.

## PUSHED: origin/main = fdc8895

Includes 18, 21, 22, 23, 25, 26 (26 = 9de6df3 cherry-pick) + group 3 comment commits. 120/109/11. Hold lifted by Erik ("push"). frosty-wasp told to rebase task 24 onto fdc8895. plucky told. Deploy is Erik call. Remaining: task 24, task 19 (hold), 16, importer follow-ups, 12, 11b, final report.

## Task 19 DISPATCHED (bubbly-panda)

Brief task-19-frame-docs-sweep.md. Recount at /private/tmp/claude-501/inv-recount/frame-hits.md (506 bare). Scope: docs .qmd bare-noun prose only in phibble 23, phit 3, phiz 1, phpolars 4; data frame stays; ambiguous listed. After merge+push: one re-sync into phpstats.org. Tell Erik: data frame ruling still open per group 3 but my relayed rule says it is allowed.

## Task 24 ready (bd1151d)

fdc8895..bd1151d; stored entry shape result+files; full 123/112/11/3431. Sent to planner. Open question: JuphyterCell public path() (planner decides). CLAUDE.md note about examples:clear after capture now unnecessary for the two stored panels (planner/Erik).

## Task 24 round 1 CHANGES REQUESTED

Item 1 (JuphyterCell::path(), delete drift test) sent to frosty-wasp. Item 2: CLAUDE.md lines 69-73 should say panel cache key follows the capture so nothing needs clearing; HELD FOR ERIK (peer cannot authorise CLAUDE.md edits). Replacement text from planner:
"- `database/data/juphyter/revenue-by-channel.html`: the cell that juphyters kernel rendered for the landing pages juphyter panel. A page never runs a kernel or Quarto. Capture it again as App\Data\JuphyterCell describes when resources/examples/packages/juphyter.qmd changes; the panels cache key follows the capture, so nothing needs clearing."
Ask Erik in final report.

## Task 19 REASSIGNED to opal-grizzly (family workspace /Users/erik/.polyscope/clones/49454953/opal-grizzly)

bubbly-panda blocked: phpstats.org-only clone, no scripts/workspace. His verified 31-hit list, wordings, render commands forwarded to opal-grizzly-37 (30 change, formulas.qmd:109 R model frame stays). phibble has no render script (do by hand like phit render-docs). README in scope. Anchors #building-frames, #creating-frames move; later re-sync into phpstats.org. Task 16 owns phiz index.qmd:32 dead links.

## Task 24 APPROVED at c168a66; MERGE sent. Planner lesson: send commit ranges, keep worktrees alive until read. CLAUDE.md wording with Erik via planner. Waiting: task 19 (opal-grizzly).

## Task 19 READY (opal-grizzly) sent to planner

Family workspace /Users/erik/.polyscope/clones/49454953/opal-grizzly, branch 1-website-docs-6-tasks-opal-grizzly: phibble 1351797..f146a90, phit 4f30ec3..7d93d54, phiz e5df583..82ad2df, phpolars 5e95d30..bb7a609. phiz docs-examples.php step could not run (php_stats vs phit extension skew, pre-existing) -> render+publish only. Anchors moved. After APPROVED: 4 MERGEs to reconcile, then one re-sync into phpstats.org (bubbly-panda or whoever).

## Task 19 APPROVED (all four)

MERGE sent for phibble f146a90, phit 7d93d54, phiz 82ad2df. phpolars bb7a609 + extra commit for site/vignettes/time-series.php:60 ("The frame holds" -> "The data frame holds") requested from opal-grizzly. Planner notes: (1) juphyter docs have bare hits in 5 .qmd files, sweep when it gets a content pin; (2) resources/views/components/layout.blade.php:2 "The page frame." Blade comment: ambiguous, goes to Erik, planner would leave; (3) LAST step: widen ProseTest to resources/content after the re-sync lands (upstream prose regressions will then fail this suite). After all merged+pushed: one re-sync into phpstats.org with new pins.

## phpolars task 19 APPROVED 5e95d30..5936fb7; MERGE sent

Planner swept all vignette text blocks of 4 packages: only time-series.php:60. phit-polyfill choosing-the-polyfill.php has 6 bare uses but is a companion, never synced. NEXT: after phpolars pushed -> re-sync task (phibble f146a90, phit 7d93d54, phiz 82ad2df, phpolars <sha>, phplyr a405963, cmdstanphp c3687a9) then LAST: widen ProseTest to resources/content (needs planner cost note in plan). Worker for both: bubbly-panda (phpstats.org clone; branch from origin/main c168a66+).

## Task 19 MERGED and PUSHED (all four); task 27 dispatched

phpolars main 5936fb7. Pins: phibble f146a90, phit 7d93d54, phiz 82ad2df, phpolars 5936fb7, phplyr a405963, cmdstanphp c3687a9. Task 27 (bubbly-panda): re-sync + ProseTest widen to resources/content as separate commits. Then planner review -> MERGE.

## Task 27 READY (330959f) sent to planner

c168a66..330959f: a1fd392 pins, 1711e7e content (20 files; io.html also carries phit 6e6a8c0/0ed60a3 blank-line docs from the pin move), 330959f ProseTest scans 115 synced pages, 1 allowance (phit formulas "R model frame"). 122/111/11. Next: planner verdict -> MERGE to reconcile -> final report to Erik (includes: CLAUDE.md wording with Erik via planner, deploy is Erik call, juphyter staging host, juphyter pin blocked, layout.blade.php:2 comment).

## Task 27 APPROVED; MERGE sent (c168a66..330959f)

Planner non-blocking notes on ProseTest: (1) strip tags before decoding entities in syncedDocs(); (2) assert set of package dirs equals synced packages in config rather than non-empty. Queue empty on planner side. Remaining for final report: juphyter pin blocked; staging host; CLAUDE.md wording (Erik via planner); deploy Erik; task 16 (phiz docs links), importer follow-ups, task 12 search, task 11b, SyncSiteContent try/catch, walkSidebar docblock, pin/lock for tagged phibble/phiz; layout.blade.php:2 comment ambiguous.

## Task 27 MERGED (2026-10-22)
origin/main 1976dec. 122 tests, 111 passed, 11 skipped, 3431 assertions; lint clean; CI green. Also landed: a59f1fb scripts/deploy (not wired into Forge; Erik pastes `scripts/deploy`), planner plan updates, 18 briefs. Staging deployed at 330959f by Erik; phpstats-juphyter.pequod.sh has no subdomain. juphyter origin/main 47a45cf (install docs, bare frame fixed); pin only after task 13 lands.
