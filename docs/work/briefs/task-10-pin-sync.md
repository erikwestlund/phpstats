# Brief: Task 10, pin and sync phibble, phiz and cmdstanphp (and re-pin the rest)

To: 1-website-docs-3-tasks-bubbly-panda
From: 1-website-docs-2-orchestrator-proud-llama
Planner/reviewer: 1-website-docs-1-plan-review-steady-orca
Reconcile: royal-dingo-f1 (0-reconcile-royal-dingo)

Load `agent-task-work` first. You keep your name. This is a new task in your
existing worktree: /Users/erik/.polyscope/clones/48422c57/bubbly-panda

Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md
(read "Task 10" and the sections "The renames landed, and task 10's gate is open").

## Base

Everything is pushed. Fetch and rebase onto `origin/main` of phpstats.org
(8fcac91 or later). It already contains tasks 1-4, 9, 11a and the composer.json
rename. Task 5 (`/get-started`) is NOT merged yet; do not depend on it.

## Goal

Make phibble, phiz and cmdstanphp appear on the site, with docs, and refresh
the pins of the other packages to their merged commits.

## Steps

1. In `config/phpstats.php` `content`, pin (all on origin, `site/site.json`
   present): phibble 3298b97, phiz f304f45, cmdstanphp b3ae0e2 (use the full
   SHA from `git ls-remote`/GitHub). Re-pin phit 3840541, phplyr 646f029,
   phpolars 67e2f29 (the task 14 install blocks). Check each is an ancestor of
   the package's origin/main.
2. Run `php artisan site:sync <pkg>` for each of the six (network). Commit the
   resulting `resources/content/` and `public/content/`. Run
   `php artisan examples:clear` afterwards.
3. phpolars' heading id changed (`installing-with-pie` -> `installing`): confirm
   no link on the site still uses the old fragment.
4. Extend DocsTest (and PackagePagesTest where it fits) to cover the new
   packages: a docs index and one docs page each resolve 200, benchmarks and
   verification pages resolve where the package ships them, and nothing renders
   a raw `phpstats://` placeholder.
5. phiz ships 18 of its 19 docs pages: `docs/gallery.html` has no `.qmd`, so
   `DocsImporter::discover()` skips it. This is known and accepted. phiz `site/`
   holds only `site.json`: do not expect vignette, benchmark or data folders,
   and do not treat their absence as a sync failure. Write one test or comment
   that records the 18-of-19 fact.
6. Open the package pages for the six in a browser preview and check: the
   six-line extension install card wraps sensibly, and phpolars' 69-character
   build line wraps or scrolls without breaking the layout; the Overview, Docs
   and nav render for phibble, phiz, cmdstanphp. Serve with
   `PHPSTATS_PATHS=true` on a free port (Erik reviews it).
7. On the preview, confirm each package's Install card and its synced docs install
   page agree (same repositories and commands). Report any disagreement; do not
   edit synced content.
8. Do NOT touch phiz script loading (task 13) or the docs nav derivation.

## Files you own

`config/phpstats.php` (`content` pins only), `resources/content/`,
`public/content/`, `tests/`. Everything else is read-only; ask first.

## Tests

Narrow: `php85 vendor/bin/phpunit tests/Feature/DocsTest.php` and
PackagePagesTest, then the full `php85 vendor/bin/phpunit` once at the end
(phit-dependent tests skip here; say so).

## Rules

- Finish every step before asking for review. When in doubt, make it good.
- Follow `.claude/skills/phpstats-style` and CLAUDE.md; the bare noun "frame"
  must not appear in prose (data frame / DataFrame are fine).
- Commit on your own branch. Never merge or push. Use the `commits` skill.
- Absolute paths in every message.

## Channel

Direct messages. Send READY FOR REVIEW to `proud-llama-80`
(1-website-docs-2-orchestrator-proud-llama) with worktree path, branch,
commit range, files changed, commands and results, preview URL/port, and risks.
I relay review as one numbered batch.
