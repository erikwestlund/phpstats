# Brief: Task 18, correct the juphyter entry in config/projects.php

To: 1-website-docs-7-tasks-frosty-wasp
From: 1-website-docs-2-orchestrator-proud-llama
Planner/reviewer: 1-website-docs-1-plan-review-steady-orca
Reconcile: royal-dingo-f1 (0-reconcile-royal-dingo)

Load `agent-task-work`. Same worktree and name; rebase onto local main of
/Users/erik/Projects/phpstats/phpstats.org (tip fc5c501, contains your work).
Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md,
section "Task 18".

## Goal
`/juphyter` states something false. `config/projects.php` (~line 113) has
`'status' => 'in-progress'`, `'status_label' => 'In progress'`, `'docs' => null`,
and an overview whose fourth paragraph says "The kernel is in progress. There is
nothing to install yet, and this page will say what it does as the work lands."
juphyter's kernel is ready: it installs with
`composer require --dev phpstats/juphyter` and `vendor/bin/juphyter install`,
and has six documentation pages.

## Steps
1. Read juphyter's real state (read-only): /Users/erik/Projects/phpstats/juphyter
   (origin/main at e00c6a9 or later): README, `composer.json`, docs titles. Do not
   state anything you did not verify there.
2. Correct the status fields to what is true (look at how other packages in the
   file mark a released package). For `docs`, only point to a URL that exists;
   if none exists yet, leave null and say so in the handoff. Nothing is pinned or
   synced for juphyter (it has no site.json yet), so do not add content config.
3. Rewrite the fourth overview paragraph: what the kernel does, how to install it
   (nothing is on Packagist: the install is the two-line VCS form the style skill
   and other packages use, plus `vendor/bin/juphyter install`). Match the voice
   and length of the other paragraphs. The "frame" rule applies (ProseTest checks
   this file). Package names in prose follow the phpstats-style skill.
4. Update or add a test in `tests/Feature/PackagePagesTest.php` so /juphyter no
   longer renders "in progress" or "nothing to install yet". Do not add a landing
   page explorer panel (Erik's call, pending).

## Files you own
`config/projects.php` (juphyter entry only), `tests/Feature/PackagePagesTest.php`.

## Tests
Narrow: PackagePagesTest and ProseTest; full `php85 vendor/bin/phpunit` once at
the end. Report whole-repo `pint --test` failures honestly (12 known).

## Rules
Finish every step; commit on your branch; never merge or push; absolute paths.

## Channel
READY FOR REVIEW to `proud-llama-80` (1-website-docs-2-orchestrator-proud-llama).
