# phpstats site

The website for the PHPstats family: a Laravel 13 + Livewire 4 app.

- Requires PHP 8.5. With Herd, use `php85`; the default `php` is 8.4.
- The landing page is served on `PHPSTATS_ROOT_HOST` (phpstats.org). Each
  package in `config/projects.php` gets its own host from
  `PHPSTATS_PACKAGE_HOST` (`{package}.phpstats.org`). Staging uses
  phpstats.pequod.sh and phpstats-{package}.pequod.sh. The local defaults are
  phpstats.test and {package}.phpstats.test, and `*.localhost` names work with
  `PHPSTATS_ROOT_HOST=phpstats.localhost PHPSTATS_PACKAGE_HOST={package}.phpstats.localhost`.
- `PHPSTATS_PATHS=true` serves everything on one host, with each package under
  `/{package}`, for previews behind one tunnel URL (Polyscope or Expose). Add
  `TRUSTED_PROXIES=*` so links use the tunnel's host.
- `robots.txt` is a route, not a file: it invites crawlers only on the hosts
  above and only when `PHPSTATS_INDEXABLE` is true, which it is by default
  just when `APP_ENV=production`, so staging and previews stay out of search.
- Each package host has Overview, Docs, Examples, Benchmarks, and
  Verification sections, built from the package's synced content.
- Examples are pages in each package's docs, under its "Examples" nav
  section, listed by site.json's `examples` with a one-sentence description.
  `/examples` lists every package's, and each package's Examples tab lists its
  own and those that name it under `also`. A few interactive pages stay in
  this app as Livewire components, listed under `vignettes` in
  `config/phpstats.php` and served at `/vignettes/{slug}`. Old example URLs
  redirect through `example_redirects` there. Charts render with phiz's
  `<x-phiz>` component.
- Pages written for this site are Markdown in `resources/pages`, rendered by
  `App\Content\Markdown`: front matter, GitHub tables, highlighted fences
  (```` ```r label ````), and Blade components and `{{ }}` echoes outside code,
  with `$stats` and `$meta` in scope. `about.md` serves /about, and the
  landing page's example tabs are `resources/pages/home/*.md`.
- Each landing page example shows code and then its result. `<x-example name="orders/transform" />`
  runs `resources/examples/orders/transform.php` (the pipe version, which returns its result) and
  shows it next to `transform.chain.php`. A test checks that both versions agree. Each example's
  rendered output is cached, keyed by the example file's modification time, and not cached at all
  when `APP_DEBUG` is true. Clear it on deploy and after `site:sync` with
  `php artisan examples:clear`, because the key does not follow the code or the data the example
  reads. The examples
  read the `orders` table, simulated by `App\Data\Orders` and loaded by `DatabaseSeeder`.
- `config/phpstats.php` defines the layers (data and workflow, and statistics, modeling and visualization)
  with their colors, and the relations between packages that package pages
  list as "Uses" and "Used by". Its `companions` are packages folded into another's pages, such
  as phit-polyfill into phit's: no menu entry, and their host redirects. Its `menus` give the bar's
  two dropdowns, one per layer: Data & Workflow and Statistics, Modeling & Visualization. Layer colors reach the markup as CSS
  variables: `class="layer-violet"` sets `--l50` to `--l950`.
- Colors: `php-*` is PHP's elephant purple, and the `zinc` grays are
  redefined in `resources/css/app.css` to lean purple. Code is highlighted on
  the server by tempest/highlight (with a small R grammar in
  `app/Content/Highlight`) and shown on a dark ink background.
- Example datasets come from phit `docs/data` and are copied to
  `database/data`, then seeded by `DatabaseSeeder`.

## Style rules

Follow the `phpstats-style` skill in `.claude/skills` for site text and
views. The two most important rules:

- Package names are set in the monospace font (`font-mono`) wherever they
  stand as labels, such as headings, menus, lists, and links. They are
  lowercase except CmdStanPHP.
- Dark mode is the `.dark` class on `<html>`, set by the theme toggle or the
  system setting. Use `dark:` variants, never `prefers-color-scheme` media
  queries.

## Generated data

These committed files are generated, not hand-edited:

- `database/data/stan/school-intercepts.json`: posterior draws for the
  Bayesian vignette. Regenerate with `php artisan stan:fit-school-posterior`
  (needs CmdStan and phit). Pages never run CmdStan.
- `database/data/juphyter/revenue-by-channel.html`: the cell that juphyter's
  kernel rendered for the landing page's juphyter panel. A page never runs a
  kernel or Quarto. Capture it again as `App\Data\JuphyterCell` describes
  when `resources/examples/packages/juphyter.qmd` changes, then run
  `php artisan examples:clear`.
- `database/data/verification/references.json`: R's and Python's results for
  the landing page's verification section, on the same public Rdatasets CSVs
  the section's phit code reads. Rebuild with
  `PYTHON=/path/to/python scripts/verification/build` (R with lme4 and
  jsonlite, Python with statsmodels and scipy). phit's own values come from
  running `resources/examples/verified/*.php`.
- `resources/content/{package}/` and `public/content/{package}/`: each
  package's `site/` folder (site.json, benchmarks, verifications, vignettes,
  and data) and its rendered Quarto docs, at the commit pinned in
  `config/phpstats.php`. Refresh with `php artisan site:sync [package]`, and
  add `--update` to move the pin to the newest main. The sync needs git and
  network access. It reads the committed .html next to each .qmd and never
  runs Quarto. `site:sync {package} --from /path/to/checkout` reads a local
  checkout instead of cloning, for content that is not pushed yet: it writes
  no pin, marks `sync.json` as local, and takes exactly one package.

## Local phit

Herd's PHP 8.5 does not load phit by default. Load a local build per command:

```sh
EXT=-dextension=/path/to/phit/target/release/libphit.dylib
# artisan serve and artisan test do not pass -d to their child processes.
(cd public && php85 $EXT -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php)
php85 $EXT vendor/bin/phpunit
```
