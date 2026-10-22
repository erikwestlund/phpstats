# Brief: Task 19, bare "frame" in the packages' docs prose

To: 1-website-docs-3-tasks-bubbly-panda
From: 1-website-docs-2-orchestrator-proud-llama
Planner/reviewer: 1-website-docs-1-plan-review-steady-orca
Reconcile: royal-dingo-f1 (0-reconcile-royal-dingo)

Load `agent-task-work` first. Same name. This task spans package repos, so use
the family workspace tooling as the `polyscope-launch` skill describes
(`scripts/workspace add <package>` from the family workspace root, which puts
the package on a branch named after this workspace). Package checkouts in
`/Users/erik/Projects/phpstats/` are merge targets: never edit them directly.

## Rule (Erik)

"Frame" as a noun on its own is out. A verb use is fine. "data frame",
"DataFrame" and "dataFrame" are fine and stay as they are; do NOT rewrite
"data frame" prose. Prefer "data frame", "DataFrame", "table", or `df` (in code
only). Prose only: do not touch code fences, printed output, identifiers,
`CHANGELOG.md`, migration or release notes, or `site.json`. An ambiguous hit
(a time frame, a stack frame, R's "model frame") is listed for Erik, not changed.

## Hit list

Group 3's recount at the current origin/main tips:
`/private/tmp/claude-501/inv-recount/frame-hits.md` (section "Bare hits by
file"). Docs `.qmd` files with bare hits, which are yours:

- phibble: `docs/coming-from-tibble.qmd` 1, `docs/getting-started.qmd` 12,
  `docs/index.qmd` 3, `docs/laravel.qmd` 2, `docs/types.qmd` 5 (+ `README.md` 1)
- phit: `docs/formulas.qmd` 2, `docs/io.qmd` 1 (R's "model frame" stays)
- phiz: `docs/large-data.qmd` 1 (also check `docs/` for "phibble Frame")
- phpolars: `docs/performance.qmd` 1, `docs/reference/dataframe.qmd` 2,
  `docs/reference/expressions.qmd` 1

Check each hit in context before changing it: some may sit in code, output or a
heading id. The list is a guide, not a promise. Group 3 owns identifiers and
error messages; if a prose sentence names an identifier that group 3 renamed,
use the current name from the package's origin/main.

## Steps

1. For each package above, edit the `.qmd` prose. Then re-render the docs so the
   committed `.html` and `search.json` match (`quarto render docs` in the
   package, as its README or CI says; report if Quarto or its engines are
   unavailable and stop for that package instead of hand-editing HTML).
2. Check the rendered diff is only the prose you changed (plus the usual
   re-render noise: report any other change).
3. In each package run its narrow docs or prose check if it has one.
4. List every ambiguous or unchanged hit with file, line and reason.
5. Commit per package on the workspace branch. Do NOT sync into phpstats.org in
   this task: after reconcile merges and pushes, a later re-sync does that.

## Files you own

The `.qmd`, rendered `.html` and `search.json` of the docs of the four
packages above. Everything else is read-only.

## Rules

- Finish every step before asking for review. When in doubt, make it good.
- Follow `.claude/skills/phpstats-style` and CLAUDE.md.
- Commit on your branch. Never merge or push. Use the `commits` skill.
- Absolute paths in every message.

## Channel

Direct messages. READY FOR REVIEW to `proud-llama-80`
(1-website-docs-2-orchestrator-proud-llama): per package the worktree, branch,
commit range, files, render command and result, remaining ambiguous hits, risks.
