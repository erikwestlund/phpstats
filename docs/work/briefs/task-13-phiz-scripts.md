# Brief: Task 13, phiz script loading, site-wide

To: 1-website-docs-3-tasks-bubbly-panda
From: 1-website-docs-2-orchestrator-proud-llama
Planner/reviewer: 1-website-docs-1-plan-review-steady-orca
Reconcile: royal-dingo-f1 (0-reconcile-royal-dingo)

Load `agent-task-work` first. Same worktree and name:
/Users/erik/.polyscope/clones/48422c57/bubbly-panda

Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md
(read "Task 13" and "Executing PHP in Quarto docs, and what it requires of this site").

Base: your task 10 branch tip (6760a03). Task 10 is approved and goes to reconcile
first; once it reports MERGED, rebase onto local main of
/Users/erik/Projects/phpstats/phpstats.org if told to.

## Goal

phiz's 18 synced docs pages hold 187 `data-phiz` divs and no images. Nothing on
a docs page draws them: `PhizComponent` injects the vega scripts only for
`<x-phiz>`, and the importer strips phiz's inline loader `<script>`. Merging task
10 alone publishes 187 empty divs. Fix it for the whole site.

Mechanism (measured): phiz puts the Vega-Lite spec in a `data-phiz` attribute on
a plain div (it survives import). The importer strips phiz's ~3,960-byte inline
loader script (no `type`), which finds `[data-phiz]` elements and hands each
spec to vega-embed. A page with vega, vega-lite and vega-embed but WITHOUT that
loader renders silent empty divs.

## Steps

1. `resources/views/components/layout.blade.php`: add `@phizScripts` so every
   page emits all four pieces (vega 6.4.0, vega-lite 6.4.3, vega-embed 7.3.0,
   and the loader, with `Assets::INTEGRITY` hashes). It calls
   `PhizServiceProvider::scripts()` -> `Assets::scripts(bool $loader = true)`.
   NEVER copy the loader text into a Blade file.
2. `config/phiz.php`: set `inject_scripts` to false so `<x-phiz>` does not inject
   a second copy. Check the key name in phiz's source.
3. Remove task 3's `ScriptState` workaround in `Example::output()` (it is
   `@internal` in phiz and its cached flag encodes page order). Keep the example
   cache behavior otherwise. Landing-page and vignette `<x-phiz>` charts must
   still draw, once.
4. Add one rule for `.phiz-figure` (margin, `overflow-x: auto`) in
   `resources/css/app.css`.
5. Tests: (a) a docs page of phiz (pick one with `data-phiz`) response contains
   phiz's loader specifically, not merely the CDN URLs; (b) the scripts appear
   exactly once on a page that also renders `<x-phiz>` (a vignette or the
   landing page); (c) an assertion that a chart container is initialised, not
   that script tags exist. If a headless browser is unavailable, assert on the
   loader's presence next to a `[data-phiz]` element AND that the loader text
   equals `Assets` output (so it cannot drift), and say plainly in the handoff
   that actual drawing was not exercised, so Erik can check visually.
6. Preview: serve with `PHPSTATS_PATHS=true`; Erik will look at a phiz docs page
   with charts (for example /phiz/docs/ggplot2) and a landing page chart.

## Files you own

`resources/views/components/layout.blade.php`, `config/phiz.php`,
`resources/css/app.css`, `app/**/Example.php` (the workaround only),
`resources/views/package/docs.blade.php` only if truly needed (owner order is
9, 13, 12), `tests/`. Everything else is read-only.

## Tests

Narrow first (`--filter` or one file), then the full `php85 vendor/bin/phpunit`
once. Phit-dependent tests skip; say so.

## Rules

- Finish every step before asking for review. When in doubt, make it good.
- phpstats-style skill; no bare "frame" in prose.
- Commit on your branch. Never merge or push. Use the `commits` skill.
- Absolute paths in every message.

## Channel

Direct messages. READY FOR REVIEW to `proud-llama-80`
(1-website-docs-2-orchestrator-proud-llama): worktree, branch, commit range,
files, commands and results, preview URL, risks.
