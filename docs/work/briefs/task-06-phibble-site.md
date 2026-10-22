# Brief: Task 6, phibble publishes its site content

Package: phibble (family directory `phibble`). Owns `phibble/site/` only.

## Goal

phibble has 11 rendered Quarto docs pages but no `site/` folder, so
`site:sync` in phpstats.org cannot take it and its Docs tab links off-site.
Add `site/site.json` so the package becomes syncable.

## Steps

1. Read phplyr's `phplyr/site/site.json` (and `phpolars/site/site.json`) for
   the schema, and `phpstats.org/app/Console/Commands/SyncSiteContent.php`,
   `phpstats.org/app/Content/DocsImporter.php`, and
   `phpstats.org/app/Content/Package.php` for how it is read.
2. Write `phibble/site/site.json`: package `phibble`, title, tagline,
   summary, install, requires, repository, `example` with `pipe`, `chain`,
   and `output`, and a `docs` block whose nav covers all 11 pages. Write the
   summary as three or four shaped paragraphs (as `phpstats.org/config/projects.php`
   does for phibble) instead of one dense block, in a form
   `Package::summary()` can take as a list (task 11 later settles the exact
   form; check Package.php and, if the list form is not yet supported, use
   the shape that works today and say so in your handoff).
3. Make the example run: execute it for real and copy the output verbatim
   into `example.output`.
4. Confirm every nav path has a committed `.html` beside its `.qmd`; render
   with Quarto (installed) only if one is missing or stale.
5. If the site's `site:sync --from` option (task 9) does not exist yet, verify
   structurally: parse the JSON, check every nav path, and check relative
   links in the rendered HTML resolve.

## Acceptance

`site.json` names package `phibble`. Every nav path has a committed `.html`.
The example runs and its output is from a real run. phibble's own test suite
stays green (run it, narrowest first).

## Setup (do this first)

1. Load the `agent-task-work` and `agent-communication` skills.
2. Rename yourself before anything else, as `agent-communication` describes.
   Your name is `1-website-docs-4-tasks-<folder>` where `<folder>` is the basename of this workspace's
   root folder. Change both the git branch of the family repo and the
   Polyscope `worktrees` row.
3. From the family workspace root, run `scripts/workspace add phibble`, then
   work and commit inside `phibble/` (its own repository). If you rename
   again for any reason, run `scripts/workspace sync`.
4. Base: the package's `main` as cloned in this workspace (the latest
   integrated code). Do not edit any other package or the phpstats.org clone,
   except that you may read `phpstats.org` and `phplyr` for reference and
   run the site's `php artisan` commands there for verification only without
   committing there.

## Rules

- Finish every step before asking for review. When in doubt about
  thoroughness, make it good.
- Run only focused tests, narrowest first.
- Commit in `phibble/` on your work branch. Never merge or push.
- Follow `phpstats-style` (in `phpstats.org/.claude/skills/phpstats-style`)
  for any text: package names lowercase and monospaced as labels, except
  CmdStanPHP.
- Absolute paths in every message.
- Channel: direct messages (SendMessage). When done, send `READY FOR REVIEW`
  to `1-website-docs-2-orchestrator-proud-llama` (session `proud-llama-80`)
  with the worktree path, package branch, commit range, files changed,
  commands run with results, and risks. I relay review as one numbered batch;
  answer each by number. After approval, return the final commit range; I send
  the merge request to `0-reconcile-royal-dingo`.
- Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md
  (read Goal, Constraints, and your task). Planner: 1-website-docs-1-plan-review-steady-orca.

Note: your commits land in the package's own repository (reconcile fetches branch `<workspace>/<pkg>`), not in phpstats.org. Rename first, then run `scripts/workspace add`; `scripts/workspace sync` repairs branches if the order slipped.
