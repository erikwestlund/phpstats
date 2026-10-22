# Brief: Task 23, rebase task 21 onto ff20363 and re-sync six packages

To: 1-website-docs-3-tasks-bubbly-panda
From: 1-website-docs-2-orchestrator-proud-llama
Planner/reviewer: 1-website-docs-1-plan-review-steady-orca
Reconcile: royal-dingo-f1 (0-reconcile-royal-dingo)

Load `agent-task-work` first. Same worktree and name:
/Users/erik/.polyscope/clones/48422c57/bubbly-panda

Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md

## Goal

Group 3 (api-consistency) renamed APIs and pushed everything. The site's synced
content must follow, with real commit SHAs and no local-path sync. The four
ScriptVignette pages (backends, reshaping, port-r-script, phplyr-on-polars) run
against the renamed phplyr and need the new pins.

## Base

phpstats.org origin/main is now `ff20363` (group 3's D2: df-table component,
camelCase phplyr calls, composer.json requires `phpstats/phplyr dev-main` and
`phpstats/cmdstanphp dev-main`). Your task 21 branch (`fc5c501..01c38cb`) is
still in the planner's review queue. Do both in ONE branch:

1. `git fetch`, then rebase your task 21 commits (e4f0f12, 01c38cb) onto
   origin/main ff20363. Resolve conflicts; keep the commits separate. Run the
   task 21 tests after the rebase.
2. Then the re-sync as further commits on top.

## Re-sync steps

1. In `config/phpstats.php` `content`, pin the full SHAs (verify each with
   `git ls-remote https://github.com/phpstats/<repo> refs/heads/main`):
   - phibble 3a5604b2d7d6188385d288481fe95b17a8cfaba8
   - phplyr a405963da0ee78e61e1c89e00b865baefa08f37c
   - phit 5938c575aec108b15267825f0df4352a700fe8b9
   - phpolars 5e95d30d1dd090d27f657b3dcde2e2ecb9d418c4
   - cmdstanphp c3687a9fd7f2c3d4846cfd03b36e6ceba965b200
   - phiz e5df583aba7a05392736759b04f48864bbf523ad
   Do NOT pin juphyter: it has no `site/site.json` at 82c7ef5 yet. Do NOT sync
   phit-polyfill (a companion).
2. `composer update phpstats/phplyr phpstats/cmdstanphp` if vendor is behind, so
   the vignettes run against the renamed packages.
3. `php artisan site:sync <pkg>` for each of the six (network, from GitHub; no
   `--from`). Commit `resources/content/` and `public/content/`, one commit for
   the pins and one for the generated content. Run `php artisan examples:clear`.
4. Check: `grep -rl '"local": true' resources/content` prints nothing, and every
   `resources/content/*/sync.json` has a real 40-character `commit`.
5. The task 21 sync rules now apply to the new content. If the sync fails on a
   page in no nav, or on an exclusion, report the exact page and package; do not
   edit synced content. Report phit's new nav count.
6. Run the four vignette pages (backends, reshaping, port-r-script,
   phplyr-on-polars) and the other ScriptVignette pages through the existing
   tests; list any that fail and why. A failure caused by the package itself is
   reported, not worked around.
7. Check no bare noun "frame" was introduced in `app/` or `resources/pages`
   (group 3 owns `app/` identifiers). Do not sweep synced docs prose: task 19 is
   on hold.
8. Preview with `PHPSTATS_PATHS=true` on a free port: /phplyr/vignettes/backends,
   /phibble/docs, /phit/docs (nav), /phiz/docs/ggplot2.

## Files you own

`config/phpstats.php` (`content` pins only), `resources/content/`,
`public/content/`, `app/Content/DocsImporter.php` and the SyncSiteContent
command (task 21 only), `tests/`. Everything else is read-only; ask first.
Do not touch `.claude/` or `tests/Feature/ProseTest.php`.

## Tests

Narrow first (DocsTest, SyncTest, VignettesTest), then the full
`php85 vendor/bin/phpunit` once. Phit-dependent tests skip; say so. Do not
claim Pint is clean: 12 files fail before this task.

## Rules

- Finish every step before asking for review. When in doubt, make it good.
- Follow `.claude/skills/phpstats-style` and CLAUDE.md.
- Commit on your branch. Never merge or push. Use the `commits` skill.
- Absolute paths in every message.

## Channel

Direct messages. READY FOR REVIEW to `proud-llama-80`
(1-website-docs-2-orchestrator-proud-llama): worktree, branch, commit range
(task 21 commits and re-sync commits listed separately), files, commands and
results, preview URL, risks.
