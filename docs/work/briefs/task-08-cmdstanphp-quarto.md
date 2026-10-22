# Brief: Task 8, convert CmdStanPHP's docs to Quarto

Package: CmdStanPHP (family directory `cmdstanphp`, repository
phpstats/CmdStanPHP). Owns `cmdstanphp/docs/` and `cmdstanphp/site/` only.
This is the largest unit in the group.

## Goal

CmdStanPHP's six docs pages are Jekyll Markdown with no Quarto build, so
`DocsImporter` cannot read them. Convert them so `site:sync` can.

## Steps

1. Read how phibble, phiz, phit, phplyr, and phpolars lay out `docs/`
   (`_quarto.yml`, `.qmd` plus a committed rendered `.html` beside it) and
   their `site/site.json`. Read `phpstats.org/app/Content/DocsImporter.php`,
   `SyncSiteContent.php`, and `Package::resolveLinks` for what the importer
   expects.
2. Convert `docs/index.md`, `installation.md`, `inference.md`, `api.md`,
   `compatibility.md`, and `testing.md` to `.qmd`, with a `_quarto.yml`. Lose
   no content against the originals; keep code fences with their languages.
   Make code chunks static (not executed) unless a chunk can run in CI
   without CmdStan.
3. Render with Quarto (1.10.18 installed) and commit each `.html` beside its
   source.
4. Internal links between pages must be relative `.html` targets or
   `phpstats://` placeholders, so the importer and `Package::resolveLinks`
   handle them.
5. Write `cmdstanphp/site/site.json` following the same schema as the other
   packages: nav, install lines, and an example with real output (an example
   that needs CmdStan may use verified output from a prior run; state in the
   handoff where it came from). The summary is three or four shaped
   paragraphs, not one block.
6. The Jekyll `_config.yml` and `Gemfile` are removed or left inert, whichever
   keeps the existing GitHub Pages site serving until the phpstats.org takes
   over. Say which and why in your handoff.
7. Link check across the rendered HTML (every internal href resolves; every
   nav path has an `.html`). If `site:sync --from` (task 9) exists in
   phpstats.org by then, also run it against your checkout.

## Acceptance

`quarto render` produces the committed HTML. Every nav path has an `.html`.
Links are importer-friendly. No content lost against the Markdown originals.
This task may ship in a later round without blocking tasks 6 and 7: send me
an interim `READY FOR REVIEW` with a clearly scoped partial only if I ask.

## Setup (do this first)

1. Load the `agent-task-work` and `agent-communication` skills.
2. Rename yourself before anything else, as `agent-communication` describes.
   Your name is `1-website-docs-6-tasks-<folder>` where `<folder>` is the basename of this workspace's
   root folder. Change both the git branch of the family repo and the
   Polyscope `worktrees` row.
3. From the family workspace root, run `scripts/workspace add cmdstanphp`, then
   work and commit inside `cmdstanphp/` (its own repository). If you rename
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
- Commit in `cmdstanphp/` on your work branch. Never merge or push.
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
