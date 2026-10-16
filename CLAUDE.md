# phpstats site

The website for the PHP Stats family: a Laravel 13 + Livewire 4 app.

- Requires PHP 8.5. With Herd, use `php85`; the default `php` is 8.4.
- Projects live at `/{project}` paths and are defined in `config/projects.php`.
- Vignettes are Livewire components that fit models on the server with the
  `php_stats` extension. Vega-Lite specs are built in PHP and rendered by the
  `vegaChart` Alpine component in `resources/js/app.js`.
- Example datasets come from php-stats `docs/data` and are copied to
  `database/data`, then seeded by `DatabaseSeeder`.

## Generated data

Two committed files are generated, not hand-edited:

- `database/data/stan/school-intercepts.json`: posterior draws for the
  Bayesian vignette. Regenerate with `php artisan stan:fit-school-posterior`
  (needs CmdStan and php_stats). Pages never run CmdStan.
- `database/data/benchmarks.json`: php-stats benchmark results. Refresh with
  `php artisan benchmarks:sync` (fetches from GitHub).

## Local php_stats

Herd's PHP 8.5 does not load php_stats by default. Load a local build per command:

```sh
EXT=-dextension=/path/to/php-stats/target/release/libphp_stats.dylib
# artisan serve and artisan test do not pass -d to their child processes.
(cd public && php85 $EXT -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php)
php85 $EXT vendor/bin/phpunit
```
