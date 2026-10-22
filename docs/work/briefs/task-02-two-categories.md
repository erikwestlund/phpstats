# Brief: Task 2, two categories, and juphyter on the site

To: 1-website-docs-3-tasks-bubbly-panda (continuing in your worktree, /Users/erik/.polyscope/clones/48422c57/bubbly-panda)
From: 1-website-docs-2-orchestrator-proud-llama

Load `agent-task-work` again if it is no longer in context. Your branch already holds Task 1 (2b38f09..31006f4, approved and sent to reconcile). Keep working on the same branch; your commit range for this task starts at 31006f4. Do not rename.

## Goal

Regroup the family into two categories and put juphyter on the site. Read the plan's "Decisions" section for the exact keys, colors and roles; the essentials:

- Layers and menus become **Data & Workflow** (phibble, phpolars, phplyr, juphyter) and **Statistics, Modeling & Visualization** (phit, phiz, cmdstanphp), in that order.
- Layer keys `data` and `stats` (`processing` and `models` go away). Colors: indigo for Data & Workflow, purple for Statistics, Modeling & Visualization. Retire fuchsia from the layer palette but keep `.layer-fuchsia` defined in `resources/css/app.css`, since docs callouts use it independently.
- Layer roles: "Frames, pipelines, and notebooks" and "Models, inference, and charts". Menu `short` labels: "Data" and "Statistics".
- juphyter gets a `config/projects.php` entry: status `in-progress`, `from_r` of `IRkernel and knitr`, `docs` of `null`, and an overview written from the repository's stated purpose (read its README at /Users/erik/Projects/phpstats/juphyter, or https://github.com/phpstats/juphyter) and not from unbuilt features. It has no `explorer` entry, so it appears in menus, footer, and family table but gets no explorer panel (the explorer already skips packages without one; verify).
- The package bar hides Vignettes, Benchmarks, or Verification when that section has no content, which also stops phibble, phiz and cmdstanphp advertising three empty sections.

## Steps

1. Read `config/phpstats.php`, `config/projects.php`, `resources/views/components/package-explorer.blade.php`, `package-header.blade.php`, `resources/css/app.css` (layer classes), and `app/Content/Package.php`, then grep for every use of the old layer keys, colors and names across `app/`, `resources/`, `routes/`, `tests/`, docs and skills.
2. Regroup config, menus, layers; update every consumer of the old keys.
3. Add juphyter; hide empty package-bar sections.
4. Update text: `resources/pages/about.md`, `CLAUDE.md` (no more "three dropdowns"), `README.md` (list phiz and juphyter), `.claude/skills/phpstats-style/SKILL.md` (two categories).
5. Tests: `tests/Feature/PackagePagesTest.php` lines 23 and 24 assert the old names (`'Processing &amp; Visualization'`, `'Statistical Modeling'`); update them. `test_landing_page_links_every_package` and `test_every_package_has_code_on_the_landing_page_and_it_runs` iterate `Package::keys()` and will fail when juphyter joins; narrow the second to the packages in `explorer`, and add a case asserting juphyter has a page and no explorer panel. Add a case that every package appears in exactly one menu and that empty package-bar sections are hidden.
6. Check every package page in light and dark mode and at phone width.

## Acceptance

Two menus, each package in exactly one. `/juphyter` renders with its overview, repository link and no Docs tab. The explorer still lists six packages and opens on phibble. `about.md` and the style skill describe two categories; CLAUDE.md no longer says three dropdowns; the README lists phiz and juphyter. Every package page works in light and dark. Full suite green (11 phit-dependent skips are expected).

## Files you own

`config/phpstats.php`, `config/projects.php`, `resources/views/components/package-explorer.blade.php`, `resources/views/components/package-header.blade.php`, `resources/css/app.css` (layer classes only), `resources/pages/about.md`, `CLAUDE.md`, `README.md`, `.claude/skills/phpstats-style/SKILL.md`, `tests/Feature/LandingPageTest.php`, `tests/Feature/PackagePagesTest.php`, plus any other view/class that merely reads the old layer keys (list them in the handoff).

## Preview (required before approval)

Erik decides the categories, colors and menus. Serve it behind one URL with `PHPSTATS_PATHS=true` and `TRUSTED_PROXIES=*` (CLAUDE.md "Local phit"): `composer install`, `php artisan key:generate`, `php artisan migrate --seed`, `npm install && npm run build`. Neither phit nor phpolars has a built dylib here, so several panels will say "This server does not load phit"; either `cargo build --release` in each (family copies exist under /Users/erik/Projects/phpstats) and pass `-dextension=`, or state in your handoff which panels will be blank. I'll tell you how to expose the URL; for now give me the local URL and keep the server running. Check the two menus, package colors and `/juphyter`.

## Rules

- Finish every step before asking for review. When in doubt about thoroughness, make it good.
- Run only focused tests, narrowest first (`php85 vendor/bin/phpunit --filter ...` or one file); the full `php85 vendor/bin/phpunit` once at the end. Plain phpunit now works (tests/Unit exists). Tests that need phit skip here; say so rather than hide it.
- Follow `.claude/skills/phpstats-style` and CLAUDE.md: package names in `font-mono` as labels, lowercase except CmdStanPHP; dark mode is the `.dark` class with `dark:` variants, never a media query.
- Own only the files listed; everything else is read-only. If you need another file, ask me.
- Commit on your own branch (use the `commits` skill: loose conventional style, no agent attribution). Never merge or push.
- Absolute paths in every message.
- Channel: direct messages (SendMessage). Send `READY FOR REVIEW` to `1-website-docs-2-orchestrator-proud-llama` (session `proud-llama-80`) with the worktree path, branch, commit range, files changed, commands run and results, and risks. I relay review as one numbered batch; answer each by number. After approval, return the final commit range; I send the merge request to `0-reconcile-royal-dingo`.
- Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md (read Goal, Constraints, Decisions, and your task). Planner: 1-website-docs-1-plan-review-steady-orca.

