# Brief: Task 3, cache the examples the landing page computes

To: 1-website-docs-3-tasks-bubbly-panda (your worktree, /Users/erik/.polyscope/clones/48422c57/bubbly-panda)
From: 1-website-docs-2-orchestrator-proud-llama

Task 2 is approved by the planner (31006f4..4bdad0b); I asked reconcile to hold its merge until Erik has seen the preview. Keep working on the same branch with new commits after 4bdad0b. Do not amend, rebase, or rename.

Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md (read Task 3 and Risks).

## Goal

The landing page runs 11 examples on every request (a Polars aggregation, a linear model fit, two chart specs) and nothing in the application calls `Cache::`. Stop recomputing them.

- Cache the rendered output fragment per example name, not the result object: results are `Phiz\Plot` instances, phibble frames, and Polars tables, none of which should be serialized.
- The key includes the example file's modification time (and, for the pipe/chain pair, both files) so an edit invalidates it.
- Caching is off when `APP_DEBUG` is true, so local work still sees changes.
- A missing extension still produces the existing "does not load" sentence and is NOT cached as a success.
- Provide a public flush method (a static method on the class, plus an artisan command such as `examples:clear`) that task 9 will call from `site:sync`, and document that the cache must be cleared on deploy and after a sync (add a line to CLAUDE.md under the relevant section, and mention it in your handoff).

## Steps

1. Read `app/Content/Example.php`, `resources/views/components/example.blade.php`, `package-explorer.blade.php`, `example-result.blade.php`, and `package-output.blade.php`, and find every place the landing page calls `Example::run` or renders a result (explorer panels, the seven tab panels).
2. Design the cache seam so the HTML fragment for an example is produced once and stored; both the explorer and `<x-example>` use it. Use the default cache store (check `config/cache.php` and `.env.example`; tests use the array store).
3. Implement; add the flush method and command.
4. Tests in `tests/Feature/ExamplesTest.php` (you own it; also a new `tests/Feature/ExampleCacheTest.php` if cleaner): two requests to `/` return identical HTML and the second runs none of the cached examples, verified by a counter or spy, not by timing; the HTML is identical warm and cold; a changed example file (touch it, or use a temp example path) recomputes; the missing-extension path is not cached as a success; caching is skipped when `APP_DEBUG` is true; flush clears it.

## Acceptance

A second request to `/` runs none of the cached examples. Rendered HTML is identical with the cache warm and cold. An edited example recomputes. A missing extension still shows the existing sentence and is not cached as success. Full suite green (11 phit skips expected).

## Files you own

`app/Content/Example.php`, `resources/views/components/example.blade.php`, `resources/views/components/package-explorer.blade.php`, `resources/views/components/example-result.blade.php` and `package-output.blade.php` only if the seam needs them, a new console command, `tests/Feature/ExamplesTest.php` (and `ExampleCacheTest.php`), and one line in `CLAUDE.md`. Everything else read-only.

## Preview (required; this task's point is that the landing page looks unchanged)

Keep the preview server on http://127.0.0.1:8000 (path mode, `PHPSTATS_PATHS=true TRUSTED_PROXIES=*`) serving your branch with `APP_DEBUG=false` and the array or file cache so caching is on. Load `/` twice and compare the HTML between cold and warm. State in the handoff which panels are blank because phit and phpolars are not built here; if you can build them cheaply (`cargo build --release` in the family copies under /Users/erik/Projects/phpstats), do so for the preview and say so.

## Rules, channel

As in the earlier briefs: finish every step, focused tests first then the full suite once, `phpstats-style`, `commits` skill, never merge or push, absolute paths. Send `READY FOR REVIEW` to me (`1-website-docs-2-orchestrator-proud-llama`, session `proud-llama-80`) with the range 4bdad0b..HEAD, files changed, commands and results, risks.
