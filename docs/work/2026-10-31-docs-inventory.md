# Documentation inventory and dispositions (2026-10-31)

The first-pass cleanup in `briefs/2026-10-31-docs-cleanup.md`. This file
covers phpstats.org's own pages. Each package's inventory is appended below as
its session reports.

Every public URL below has a path-mode twin under `/{package}` on preview
hosts.

## phpstats.org

| URL | Source | Reader and purpose | State | Disposition | Problems found | Verified by |
|---|---|---|---|---|---|---|
| `/` | `resources/views/home.blade.php`, `resources/pages/home/*.md`, `config/phpstats.php` explorer | Visitor: what the family does, one example per package | Released | Keep | phpolars bullet and relations gave `PolarsTable` to phpolars; "10 to 100 times faster" and "a million rows takes milliseconds" were unsupported; juphyter bullets carried marketing lines; phit "fits models fastest" | phibble namespace `Phibble\Polars\PolarsTable`; phplyr `benchmarks/backends.json` (1.3–15×, 51 ms at 1M rows, M5 Ultra, 2026-10-18) |
| `/get-started` | `resources/pages/get-started.md`, `components/{package-chooser,install,requirements}` | New user: which package, what it needs, how to install and check | Released | Rewritten | VCS and `dev-main` installs; open-ended PHP claims; juphyter install incomplete; no task chooser; Markdown code blocks drew a second box | Packagist versions; composer.json `php` constraints; PIE `download` resolved phit v0.3.0 and phpolars v0.2.0; install lines now come from each package's `site.json` |
| `/about` | `resources/pages/about.md` | Visitor: what PHPstats is, packages, design, license | Released | Rewritten | Lead restated the title; "This site" described repo internals | composer.json licenses (all MIT) |
| `/csv-options` | `resources/pages/csv-options.md` | Reader mapping readr CSV options to Polars ones | Released | Keep, off the menu | Only the sitemap links it | `RootPagesTest` option lists |
| `/vignettes` (titled Examples) | `resources/views/vignette-index.blade.php` | Index of every example | Released | Keep; Erik curates content | "Vignettes" labels | Route and title tests |
| `{package}.phpstats.org/` | `resources/views/package/overview.blade.php`, `config/projects.php`, synced `site.json` | Package overview | Released | Keep | phpolars "targets PHP 8.5"; juphyter "Nothing in the family is on Packagist"; phit-polyfill paragraphs contradict phpstats.php (pending phit-polyfill facts) | composer.json; Packagist |
| `{package}.phpstats.org/docs/*` | imported from each package's committed Quarto HTML at the pin in `config/phpstats.php` | Package docs | Pinned main | Package sessions own | See package sections | `site:sync`, `DocsTest`, `ProseTest` |
| `{package}.phpstats.org/vignettes[/slug]` (Examples) | `resources/views/vignettes/*`, synced `site/vignettes` | Worked examples | Pinned main | Keep; Erik curates | Labels | `VignettesTest` |
| `{package}.phpstats.org/benchmarks`, `/verification` | synced `site/benchmarks`, `site/verifications` | Evidence | Historical, dated | Keep | none found | `DocsTest` renders every file |
| `phit-polyfill.phpstats.org/*` | route redirect | — | — | Redirect to phit (301) | — | `PackagePagesTest` |
| `/robots.txt`, `/sitemap.xml` | `RobotsController`, `SitemapController` | Crawlers | — | Keep | Cloudflare's managed robots.txt hides the `Sitemap:` line in production | `SeoTest`; curl of origin and public |

## Packages

Appended as each session reports.
