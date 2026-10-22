# Brief: Task 1, split the test suite by area

To: 1-website-docs-3-tasks-bubbly-panda
From: 1-website-docs-2-orchestrator-proud-llama
Planner/reviewer: 1-website-docs-1-plan-review-steady-orca
Reconcile: 0-reconcile-royal-dingo

Load the `agent-task-work` skill first. You already hold your name and base
(origin/main at 2b38f09), so do not rename or rebase.

Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md
(read the Goal, Constraints, and "Task 1" sections; the rest is context).
Worktree: /Users/erik/.polyscope/clones/48422c57/bubbly-panda

## Goal

Every later task in this group adds tests. `tests/Feature/PagesTest.php` is a
single 549-line file with 30 test methods, so it would collide on nearly all of
them. Split it by area, changing nothing else.

## Steps

1. `composer install` if `vendor/` is missing. Record the baseline: run
   `php85 vendor/bin/phpunit` (add the `-dextension=` flag only if the
   current suite needs phit or phpolars, per CLAUDE.md "Local phit") and note
   the test count, pass/fail/skip counts, and the 30 method names from `HEAD`.
2. Split `tests/Feature/PagesTest.php` into `LandingPageTest`,
   `PackagePagesTest`, `DocsTest`, `VignettesTest`, and `ExamplesTest`, each in
   `tests/Feature/`. Sort each method by what it exercises: the landing page
   and its tabs and explorer; the package routes, hosts, redirects, and package
   pages; docs pages, links, and assets; vignettes and their numerical
   results; the `<x-example>` component and example files. Move shared
   helpers or setup into a trait or base only if two or more files need them,
   and keep it minimal.
3. Delete `PagesTest.php`. Keep each moved method's body, name, and
   assertions byte-for-byte except for what the move forces (imports,
   class name, helpers).
4. Re-run the suite and compare against step 1.

## Acceptance

- The same 30 test methods exist, distributed by area, with the same results
  as step 1 (a test that skipped before still skips for the same reason).
- No production code changes. No assertion is weakened or dropped.
- Show the before and after method lists in your handoff, so the reviewer can
  confirm none went missing.

## Files you own

`tests/` only. Everything else is read-only.

## Tests

`php85 vendor/bin/phpunit` is the only check; run the narrow file or
`--filter` while working and the full suite once at the end. laravel/pao is not
installed.

## Rules

- Finish every step before asking for review. When in doubt about
  thoroughness, make it good.
- Follow the phpstats-style skill for any text, and the surrounding code's
  conventions.
- Commit on your own branch. Never merge or push. Use the `commits` skill for
  the message; it needs no agent attribution.
- Use absolute paths in every message.

## Channel

Direct messages (SendMessage). Send `READY FOR REVIEW` to me,
`1-website-docs-2-orchestrator-proud-llama` (session `proud-llama-80`), with
the worktree path, branch, commit range, files changed, commands run and their
results, and open risks. I relay review comments to you as one numbered batch;
answer each by number. After approval, return the final commit range to me. I
send the merge request to `0-reconcile-royal-dingo`.
