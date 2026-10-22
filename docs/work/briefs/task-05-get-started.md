# Brief: Task 5, a getting-started page and an index of vignettes

To: 1-website-docs-7-tasks-frosty-wasp (your worktree, /Users/erik/.polyscope/clones/48422c57/frosty-wasp)
From: 1-website-docs-2-orchestrator-proud-llama

Task 4 is merged into local main as 449b92d (three commits on 6986cce, after task 3). Your old branch commits are not shas in main. Start clean: `git fetch /Users/erik/Projects/phpstats/phpstats.org main` (the main checkout holds local main) and `git reset --hard FETCH_HEAD` (check `git log -1` shows 449b92d), and keep your branch name. Then run `php85 "$(which composer)" install` if vendor is stale, and `npm run build` if needed. Your range for this task is 449b92d..HEAD. No rename.

Plan: /Users/erik/.polyscope/clones/48422c57/steady-orca/docs/work/2026-10-19-website-docs-plan.md (read Task 5). Pending rulings: /Users/erik/.polyscope/clones/48422c57/proud-llama/docs/work/briefs/pending-brief-notes.md.

## Goal

The root host (phpstats.org) serves only `/` and `/about`. Add a getting-started page and a cross-package index of vignettes, both reachable from the site bar.

## Deliverables

1. `resources/pages/get-started.md` (Markdown page via `App\Content\Markdown`, with a `description:` front-matter key since task 4 uses it): the PHP 8.5 requirement; the install block for each package, built from `Package::install()` and `requires()` so it stays correct as packages sync (a Blade loop over `Package::all()` in the page, outside code fences); and the shortest worthwhile example (use an existing example under `resources/examples`, shown with `<x-example>`; pick one that runs without phit or phpolars if possible). Packages whose `site.json` is not synced have no `install()`: either take the install lines from the repository or omit that package, never invent. Write the copy to `phpstats-style` and `writing`: plain, no marketing, package names in `font-mono` as labels, lowercase except CmdStanPHP, no bare word "frame" (say DataFrame or table).
   Include ONE short paragraph (a single home for it; do not repeat it in package pages) saying: the `composer config` lines assume a project that already has a `composer.json` (run `composer init` first when starting from nothing), and that Composer may ask for a GitHub token on anonymous API limits while it reads the VCS repositories. State that nothing in the family is on Packagist yet, so installs go through the repositories shown.
2. A `/vignettes` route on the root host listing every vignette across all packages: the 10 Livewire vignettes (from `config/phpstats.php` `vignettes`) and every synced script vignette, each with its title, summary, the packages it uses, and a link to its owning package's vignette page. `/vignettes/{slug}` must still redirect to the owning package, and `/vignettes` must be declared before the `{page}` catch-all. Check how task 4's sitemap and robots routes were registered in `routes/web.php` and follow the same care with root-host ordering; works in `PHPSTATS_PATHS` mode too (links via the package route helpers).
3. Both pages reachable from the site bar (`resources/views/components/site-header.blade.php`): add entries in the existing style, desktop and mobile; do not touch the package menus.
4. Move `test_markdown_pages_render_blade_outside_code_only` from `LandingPageTest.php` into your new `tests/Feature/RootPagesTest.php`, unchanged in body.
5. Add both new URLs to the sitemap if task 4's `SitemapController` lists Markdown pages generically (get-started will appear automatically through `resources/pages/*.md`); add `/vignettes` explicitly. Test it.

## Tests (`tests/Feature/RootPagesTest.php`)

`/get-started` and `/vignettes` render on the root host and in `PHPSTATS_PATHS` mode (200, expected content); `/vignettes` is not swallowed by the catch-all and `/vignettes/{slug}` still redirects; the index lists all 10 Livewire vignettes plus each synced script vignette (compute the expected count from config and the synced content, not a literal); install blocks on get-started match `Package::install()` for each package that has one and invent none; package names are monospace on both pages; the new pages carry a description and canonical (task 4's layout) and appear in the sitemap.

## Files you own

`resources/pages/get-started.md`, a new view for the vignettes index plus a small controller or route closure, `resources/views/components/site-header.blade.php`, `routes/web.php`, `tests/Feature/RootPagesTest.php`, `tests/Feature/LandingPageTest.php` (only to remove the moved test), and the sitemap controller only for the explicit `/vignettes` entry. Task 9 (another worker, on its own branch) edits `package/docs.blade.php`, `overview.blade.php` and `vignette.blade.php`: do not touch those.

## Preview (required: new public pages; Erik reviews it)

Serve a preview on http://127.0.0.1:8003 in path mode (`PHPSTATS_PATHS=true TRUSTED_PROXIES=*`), `php artisan migrate --seed` and `npm run build` done. Pin it to a detached checkout of your final commit so it cannot drift while you work (the built-in server reads files per request). Check `/get-started` and `/vignettes` in light and dark mode and at phone width as far as you can without a browser, and state what you could and could not see. Phit and phpolars are not built here, so say which panels are blank.

## Rules, channel

As before: finish every step; focused tests first and the full suite once; `phpstats-style`, the `commits` skill; never merge or push; absolute paths. Send `READY FOR REVIEW` to me (`1-website-docs-2-orchestrator-proud-llama`, session `proud-llama-80`) with the range 449b92d..HEAD, files changed, commands and results, the preview URL and commit it serves, and risks.
