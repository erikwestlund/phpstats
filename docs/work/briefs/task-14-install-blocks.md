# Brief: Task 14, working install blocks for phit, phplyr, and phpolars

Name: `1-website-docs-8-tasks-<folder>` where `<folder>` is the basename of this workspace's root folder. Load `agent-task-work` and `agent-communication`, then rename yourself FIRST (git branch of the family repo plus the Polyscope `worktrees` row, as `agent-communication` describes) and never rename again. Then, from the family workspace root, run `scripts/workspace add phit phplyr phpolars`; commit in each package's own repository. (`scripts/workspace sync` repairs branches if the order slipped.)

Orchestrator: 1-website-docs-2-orchestrator-proud-llama (session `proud-llama-80`). Reconcile: 0-reconcile-royal-dingo. Planner/reviewer: 1-website-docs-1-plan-review-steady-orca.
Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md (read Goal and Constraints; this is "task 14", added after the plan was written).

## Goal

Nothing in the family is on Packagist, so the one-line `composer require phpstats/x` install blocks on the site are instructions that fail. Erik decided every Composer package's install block uses the two-line VCS form:

    composer config repositories.NAME vcs https://github.com/phpstats/REPO
    composer require phpstats/NAME:dev-main

phibble, phiz, and CmdStanPHP already do (tasks 6, 7, 8). phit, phplyr, and phpolars carry the old form in `site/site.json`, already synced into the site at pinned commits. Fix them at the source.

## Steps

1. For each of phit, phplyr, phpolars: read `site/site.json` `install` and the repo's `composer.json` `name`. Decide the correct package name and the GitHub repo for the vcs line from what the repo actually declares and where it lives (phpstats org; phpolars may be an extension installed through PIE or a build, not a Composer library; phit is a PHP extension with `phit-polyfill` as its Composer polyfill). Do NOT blindly apply the two-line form to a package that is not a Composer library: for an extension, the install block should be whatever actually works today (PIE, or cargo build, or a Composer line for its polyfill), verified by running it where possible. Report each decision with the evidence.
2. For Composer libraries, use the two-line form with the destination name; verify by running both lines in a throwaway project (as the phibble worker did) where the repo declares that name today. If a repo does not yet declare `phpstats/NAME`, say so and do not rename its composer.json; tell me.
3. Update `site/site.json` `install` in each repo. Also update the same install instruction wherever the repo repeats it in its README and docs `.qmd` (with committed `.html` re-rendered the project's own way; remember `quarto render docs` can delete committed copies and `docs/sitemap.xml` `<lastmod>` churn is expected and must NOT be committed unless you can vouch for the dates; follow each repo's own render script if it has one). Keep this to the install block; make no other docs edits.
4. Run each repo's focused tests for anything you touch (docs-only changes need none, but run the repo's link/doc checks if it has them) and report.

## Acceptance

Each of the three `site.json` install blocks works as written when run (or, for extensions, is the verified working form), names the correct package, and the repo's README and docs say the same thing. `git grep` for the old form in each repo returns nothing you did not intentionally leave. Nothing in phpstats.org changes: task 10 later re-pins these three repos, and I will say when.

## Rules

Finish every step before asking for review; commit on your own branch in each package repo, never merge or push; run only focused tests; follow `phpstats-style` (in `phpstats.org/.claude/skills/phpstats-style`); absolute paths in every message. Send `READY FOR REVIEW` to me with, per repo: worktree path, branch, commit range, files changed, commands run and results, risks.
