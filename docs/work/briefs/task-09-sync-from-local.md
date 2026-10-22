# Brief: Task 9, sync from a local checkout (plus two small sync/sidebar fixes)

To: 1-website-docs-3-tasks-bubbly-panda (your worktree, /Users/erik/.polyscope/clones/48422c57/bubbly-panda)
From: 1-website-docs-2-orchestrator-proud-llama

Tasks 2 and 3 are approved (task 2 merged to main as 4bdad0b; task 3 is 4bdad0b..56a3ece, sent for merge after Erik sees the preview). Stay on your branch, add new commits after 56a3ece, no amend, no rebase, no rename.

Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md (read Task 9). Pending notes with later rulings: /Users/erik/.polyscope/clones/48422c57/proud-llama/docs/work/briefs/pending-brief-notes.md.

## Goal

`site:sync --from <path>` reads a package's `site/` and `docs/` from a local checkout instead of cloning from GitHub, so a package's site content can be verified on the site before it is pushed.

## Deliverables

1. `php artisan site:sync <package> --from <path>`: reads from the local checkout at `<path>`. It writes `resources/content/{package}` and `public/content/{package}` identically to what a clone of the same commit would produce. It writes NO pin: `config/phpstats.php` is not modified by a local sync. It marks `sync.json` as local (for example a `"local": true` field and the source path, with no commit) so a local run is never mistaken for a pinned one, and the command prints that clearly. Without `--from`, the command behaves exactly as it does now. Reject `--from` with `--update` and with no package (or all packages) with a clear error.
2. The command flushes the example cache: call `Example::flush()` at the end of every successful sync (local or pinned), and say so in the output.
3. Carry each nav item's `section` key through: `SyncSiteContent.php` (around line 97) builds nav entries with only `title` and `path` and drops everything else. Keep `section` when the package's site.json provides one. Check `Package::docsGroups()` for what it expects, and add a test with a site.json fixture that has sections and one without.
4. In `resources/views/package/docs.blade.php`: suppress the sidebar group heading when `docsGroups()` returns exactly one group (the same convention `config/phpstats.php` states: a menu with one layer shows no layer heading), so a package with a flat nav (phibble, phiz, cmdstanphp) gets no lone "Guide" heading. Packages with several groups render as today. You own that file for this task. Later tasks 13 and 12 edit it after you, so keep the change minimal and well tested.

## Tests (`tests/Feature/SyncTest.php`, new)

Build a small fake package checkout in a temp directory (a `site/site.json`, a docs folder with a `.qmd` and rendered `.html`, a figure) and assert: `--from` produces the same `resources/content` and `public/content` files as the clone-based path for the same files (design the code so the two share the copy/import logic, so this is a real equivalence test, not two implementations); `config/phpstats.php` is unchanged; `sync.json` is marked local; the cache is flushed; `--from` with `--update` errors; without `--from` nothing changes (assert on the existing behavior without network access, via the shared path or a faked git step). Docs sidebar: a one-group package shows no group heading, a multi-group package does. Use temp directories and restore the real content dirs; never leave the working tree's `resources/content` or `public/content` modified by the tests.

## Files you own

`app/Console/Commands/SyncSiteContent.php`, `resources/views/package/docs.blade.php`, `tests/Feature/SyncTest.php`, `tests/Feature/DocsTest.php` only for the sidebar-heading cases (task 10 also edits DocsTest later; keep your additions in their own test methods), and one line in `CLAUDE.md` describing `--from`. Everything else is read-only.

## Rules, channel

As in the earlier briefs. Run focused tests first and the full suite once at the end; follow `phpstats-style` and the `commits` skill; never merge or push; absolute paths. No preview is required for this task, but check the docs sidebar of phit (many groups) and phibble (flat) over HTTP and report what each shows. Send `READY FOR REVIEW` to me (`1-website-docs-2-orchestrator-proud-llama`, session `proud-llama-80`) with the range 56a3ece..HEAD, files changed, commands and results, and risks.
