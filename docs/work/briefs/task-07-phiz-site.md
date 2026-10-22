# Brief: Task 7, phiz publishes its site content

Package: phiz (family directory `phiz`). Owns `phiz/site/` only.

## Goal

Same shape as task 6, over phiz's 18 Quarto sources and 20 rendered pages.
Add `site/site.json` so the package becomes syncable and its Docs tab can
move on-site.

## Steps

1. Read phplyr's `phplyr/site/site.json` for the schema, and
   `phpstats.org/app/Console/Commands/SyncSiteContent.php`,
   `phpstats.org/app/Content/DocsImporter.php`, and
   `phpstats.org/app/Content/Package.php` for how it is read.
2. Write `phiz/site/site.json`: package `phiz`, title, tagline, summary
   (three or four shaped paragraphs, as `phpstats.org/config/projects.php`
   has for phiz, not one dense block), install, requires, repository,
   `example` with `pipe`, `chain`, and `output`, and a `docs` block. The nav
   lists only real pages. phiz's docs tree also holds `_generated`, `figures`,
   and `examples` directories: they are not nav entries, but figures must be
   reachable under `/content/phiz/` after a sync, so confirm the importer
   copies them and that the rendered HTML references them by paths that
   survive the copy.
3. Make the example run for real and copy its output into `example.output`.
   If the output is a chart, follow how phplyr or phpolars represent a
   non-text result, or describe the gap in your handoff.
4. Confirm every nav path has a committed `.html`; render with Quarto only
   if one is missing or stale.
5. If `site:sync --from` (task 9) does not exist yet, verify structurally:
   parse the JSON, check nav paths, check relative links and figure
   references in the rendered HTML.

## Acceptance

`site.json` names package `phiz`. Every nav path has a committed `.html`.
Figures resolve. The example is from a real run. phiz's own tests stay green.

## Setup (do this first)

1. Load the `agent-task-work` and `agent-communication` skills.
2. Rename yourself before anything else, as `agent-communication` describes.
   Your name is `1-website-docs-5-tasks-<folder>` where `<folder>` is the basename of this workspace's
   root folder. Change both the git branch of the family repo and the
   Polyscope `worktrees` row.
3. From the family workspace root, run `scripts/workspace add phiz`, then
   work and commit inside `phiz/` (its own repository). If you rename
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
- Commit in `phiz/` on your work branch. Never merge or push.
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
