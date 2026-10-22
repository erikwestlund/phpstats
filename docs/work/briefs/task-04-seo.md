# Brief: Task 4, meta tags and a sitemap

Name: `1-website-docs-7-tasks-<folder>` where `<folder>` is the basename of this workspace's root folder. Load `agent-task-work` and `agent-communication`, then rename yourself FIRST (git branch plus the Polyscope `worktrees` row, as `agent-communication` describes) and never rename again.

Base: this workspace was cut from origin/main, which lacks Task 1. Task 1 (a split of the test suite, plus tests/Unit and an npm package name) is approved and lives on another worktree's branch. Pull it in first:

```
git fetch /Users/erik/.polyscope/clones/48422c57/bubbly-panda 1-website-docs-3-tasks-bubbly-panda
git reset --hard FETCH_HEAD   # fresh workspace, nothing of yours to lose yet
```

Your commit range for this task starts at FETCH_HEAD (31006f4). Run `composer install`, `cp .env.example .env` + `php artisan key:generate`, and `npm install && npm run build` (without the Vite build, many tests fail on a missing manifest). `npm install` must leave git status clean; if it does not, tell me.

## Goal

`layout.blade.php` emits no meta description, canonical URL, or Open Graph tags; there is no sitemap and `robots.txt` is bare allow-all.

- `resources/views/components/layout.blade.php` takes a `description` and emits a meta description, a canonical URL, and Open Graph and Twitter card tags. Package pages fill them from the package tagline, Markdown pages from a `description` front-matter key (add the key to the existing pages, and make `App\Content\Markdown` expose it), docs pages from the page title and package.
- A `sitemap.xml` route lists the landing page, the Markdown pages, and every package overview, docs page, vignette, benchmarks and verification URL across the configured hosts. `public/robots.txt` points at it.

## Acceptance

Every route type carries a description and a canonical URL. `sitemap.xml` is valid XML, contains no 404, and uses the right host in host mode and in `PHPSTATS_PATHS` mode. The `{page}` catch-all route still matches `about` and does not swallow `sitemap.xml` (declare the sitemap route before it). Companion packages (e.g. phit-polyfill, which redirect) are not listed as their own URLs.

## Tests (`tests/Feature/SeoTest.php`)

A crawl of every URL in the sitemap returning 200; an assertion that the canonical URL matches the request URL on each page type; XML validity; both host modes. Use the narrowest filter while working.

## Files you own

`resources/views/components/layout.blade.php`, `routes/web.php`, `public/robots.txt`, a new sitemap controller, `tests/Feature/SeoTest.php`, and the small `app/Content/Markdown.php` / `resources/pages/*.md` front-matter additions the description key needs. Task 2 (running in parallel on another branch) owns `config/phpstats.php`, `config/projects.php`, the explorer and package-header views, `about.md` text, `LandingPageTest.php` and `PackagePagesTest.php`: do not edit those, and add only the `description:` front-matter line to `about.md` and `resources/pages/home/*.md` where needed (one line each, to keep merge conflicts trivial). The sitemap must read packages and menus from config generically so Task 2's regrouping and juphyter flow through without edits from you.

## Rules

- Finish every step before asking for review. When in doubt about thoroughness, make it good.
- Run only focused tests, narrowest first (`php85 vendor/bin/phpunit --filter ...` or one file); the full `php85 vendor/bin/phpunit` once at the end. Plain phpunit now works (tests/Unit exists). Tests that need phit skip here; say so rather than hide it.
- Follow `.claude/skills/phpstats-style` and CLAUDE.md: package names in `font-mono` as labels, lowercase except CmdStanPHP; dark mode is the `.dark` class with `dark:` variants, never a media query.
- Own only the files listed; everything else is read-only. If you need another file, ask me.
- Commit on your own branch (use the `commits` skill: loose conventional style, no agent attribution). Never merge or push.
- Absolute paths in every message.
- Channel: direct messages (SendMessage). Send `READY FOR REVIEW` to `1-website-docs-2-orchestrator-proud-llama` (session `proud-llama-80`) with the worktree path, branch, commit range, files changed, commands run and results, and risks. I relay review as one numbered batch; answer each by number. After approval, return the final commit range; I send the merge request to `0-reconcile-royal-dingo`.
- Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md (read Goal, Constraints, Decisions, and your task). Planner: 1-website-docs-1-plan-review-steady-orca.

