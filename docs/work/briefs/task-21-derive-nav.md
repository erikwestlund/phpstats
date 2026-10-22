# Brief: Task 21, derive docs.nav from _quarto.yml at sync time

To: 1-website-docs-3-tasks-bubbly-panda
From: 1-website-docs-2-orchestrator-proud-llama
Planner/reviewer: 1-website-docs-1-plan-review-steady-orca
Reconcile: royal-dingo-f1 (0-reconcile-royal-dingo)

Load `agent-task-work`. Same worktree and name. Rebase onto local main of
/Users/erik/Projects/phpstats/phpstats.org (tip fc5c501).
Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md
(sections on the nav derivation and "Executing PHP in Quarto docs").

## Goal
The docs nav is maintained by hand twice (a package's `_quarto.yml` and its
`site/site.json` `docs.nav`) and has drifted: phit has 52 rendered .qmd,
51 named in `docs/_quarto.yml`, 16 in the site.json nav; 36 pages are rendered,
synced, reachable by URL and absent from the sidebar. Erik decided: derive the
nav from `_quarto.yml` at sync time.

## Design call you must settle (and document)
15 of phit's undisplayed pages (notebooks/) overlap its dedicated Benchmarks and
Verification sections (9 benchmarks/, 6 verifications/). Choose how a package
marks a rendered page as deliberately outside the docs nav: an exclusion list in
`site.json`, exclusion by directory, or using `_quarto.yml` sections as the unit
and letting the site choose. Look at all six synced packages before choosing and
write the rule and reasons into the handoff and a code comment. `data/lmm/index.qmd`
is in neither nav and stays orphaned.

## Steps
1. In `app/Console/Commands/SyncSiteContent.php` (and `DocsImporter` as needed),
   derive nav items (title, path, `section` key as task 9 carries it) from the
   package's `docs/_quarto.yml` sidebar/navbar. Fall back to `site.json`
   `docs.nav` when `_quarto.yml` is absent (cmdstanphp etc. may differ; check).
2. The sync must FAIL (non-zero, clear message) when a rendered page is missing
   from the nav and not explicitly excluded.
3. Re-sync the six packages locally with `site:sync <pkg> --from <path>` is NOT
   allowed to change pins; to see the effect on the committed content, run the
   normal `site:sync <pkg>` at the current pin. Commit the resulting content only
   if it changes; report which packages' navs change and by how many entries.
4. Tests: cover derivation, section carry-through, the failure on an unlisted
   page, and the exclusion rule, using fixtures (no network).

## Files you own
`app/Console/Commands/SyncSiteContent.php`, `app/Content/DocsImporter.php`
(nav handling only; the host/fallback change is a separate later task),
`resources/content/` (as produced by sync), `tests/`. Everything else read-only;
do not touch `package/docs.blade.php` (task 12 comes later).

## Rules
Finish every step; narrow tests then full suite once; commit on your branch;
never merge or push; no bare noun "frame" in prose; absolute paths.

## Channel
READY FOR REVIEW to `proud-llama-80` (1-website-docs-2-orchestrator-proud-llama).
