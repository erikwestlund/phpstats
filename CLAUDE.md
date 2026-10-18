# phpstats site

The website for the PHPstats family: a Laravel 13 + Livewire 4 app.

- Requires PHP 8.5. With Herd, use `php85`; the default `php` is 8.4.
- The landing page is served on `PHPSTATS_ROOT_HOST` (phpstats.org). Each
  package in `config/projects.php` gets its own host from
  `PHPSTATS_PACKAGE_HOST` (`{package}.phpstats.org`). Staging uses
  phpstats.pequod.sh and phpstats-{package}.pequod.sh. The local defaults are
  phpstats.test and {package}.phpstats.test, and `*.localhost` names work with
  `PHPSTATS_ROOT_HOST=phpstats.localhost PHPSTATS_PACKAGE_HOST={package}.phpstats.localhost`.
- Each package host has Overview, Docs, Vignettes, Benchmarks, and
  Verification sections, built from the package's synced content.
- Vignettes are either Livewire components in this app, listed under
  `vignettes` in `config/phpstats.php`, or scripts from a package's
  `site/vignettes`, run by `App\Livewire\ScriptVignette`. Charts render with
  phiz's `<x-phiz>` component.
- Pages written for this site are Markdown in `resources/pages`, rendered by
  `App\Content\Markdown`: front matter, GitHub tables, highlighted fences
  (```` ```r label ````), and Blade components and `{{ }}` echoes outside code,
  with `$stats` and `$meta` in scope. `about.md` serves /about, and the
  landing page's "What you can do" tabs are `resources/pages/home/*.md`.
- `config/phpstats.php` defines the layers (data, verbs, models, graphics)
  with their colors, and the relations between packages that package pages
  list as "Uses" and "Used by". Layer colors reach the markup as CSS
  variables: `class="layer-violet"` sets `--l50` to `--l950`.
- Colors: `php-*` is PHP's elephant purple, and the `zinc` grays are
  redefined in `resources/css/app.css` to lean purple. Code is highlighted on
  the server by tempest/highlight (with a small R grammar in
  `app/Content/Highlight`) and shown on a dark ink background.
- Example datasets come from phit `docs/data` and are copied to
  `database/data`, then seeded by `DatabaseSeeder`.

## Generated data

These committed files are generated, not hand-edited:

- `database/data/stan/school-intercepts.json`: posterior draws for the
  Bayesian vignette. Regenerate with `php artisan stan:fit-school-posterior`
  (needs CmdStan and phit). Pages never run CmdStan.
- `resources/content/{package}/` and `public/content/{package}/`: each
  package's `site/` folder (site.json, benchmarks, verifications, vignettes,
  and data) and its rendered Quarto docs, at the commit pinned in
  `config/phpstats.php`. Refresh with `php artisan site:sync [package]`, and
  add `--update` to move the pin to the newest main. The sync needs git and
  network access. It reads the committed .html next to each .qmd and never
  runs Quarto.

## Local phit

Herd's PHP 8.5 does not load phit by default. Load a local build per command:

```sh
EXT=-dextension=/path/to/phit/target/release/libphit.dylib
# artisan serve and artisan test do not pass -d to their child processes.
(cd public && php85 $EXT -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php)
php85 $EXT vendor/bin/phpunit
```
