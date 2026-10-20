<?php

namespace App\Console\Commands;

use App\Content\DocsImporter;
use App\Content\Example;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Copy each package's site/ content and rendered docs at its pinned commit
 * into resources/content/{package}, and figures into public/content.
 */
class SyncSiteContent extends Command
{
    protected $signature = 'site:sync
        {package?* : Packages to sync (default: every package in config/phpstats.php content)}
        {--update : Move the pinned ref to the newest commit on main first}
        {--from= : Read site/ and docs/ from this local checkout instead of cloning, and write no pin}';

    protected $description = 'Sync package docs, benchmarks, verifications, and vignettes at their pinned commits';

    /**
     * Rendered pages that are deliberately outside the docs nav, as page
     * paths or `directory/` prefixes.
     *
     * The nav itself is derived from the package's own `docs/_quarto.yml`, so
     * the question this answers is the other half: how a package says that a
     * page it renders is not documentation of the library. It declares
     * `docs.exclude` in `site/site.json`, and this is the default when it
     * declares nothing.
     *
     * The default is a directory rather than a list of pages because the only
     * case the family has is a directory, and because the directory is already
     * a convention: four of the six synced packages keep a `docs/data` of the
     * CSVs, checksums and provenance notes their examples read, and `site/data`
     * and `database/data` give `data` that same meaning elsewhere in the sync.
     * phit is the one package that renders a page in there,
     * `docs/data/lmm/index.qmd`, which documents the datasets rather than the
     * library and which no nav has ever named. Naming the directory means a
     * package can add another dataset note without this site having to agree.
     *
     * Neither half may go quiet, which is the whole point of deriving the nav:
     * a rendered page that is in no nav and no exclusion fails the sync, a
     * declared exclusion that covers no page fails it, and an exclusion that
     * covers a page the nav lists fails it too. Every rule is printed with the
     * pages it covered, so an exclusion swallowing real documentation shows up
     * in the sync's own output. The default is exempt from the covers-nothing
     * check, because it is this site's convention rather than a package's
     * claim, and in five of the six packages it covers no page.
     */
    private const DOCS_EXCLUDE = ['data/'];

    public function handle(): int
    {
        $content = config('phpstats.content');
        $named = $this->argument('package');
        $packages = $named ?: array_keys($content);
        $from = $this->option('from');

        if ($from !== null) {
            if ($this->option('update')) {
                $this->error('--from reads a local checkout, so there is no commit to pin: use it without --update.');

                return self::FAILURE;
            }
            if (count($named) !== 1) {
                $this->error('--from syncs one package: name exactly one, as in `site:sync phibble --from ../phibble`.');

                return self::FAILURE;
            }
            if (! is_dir($from)) {
                $this->error("{$from} is not a directory.");

                return self::FAILURE;
            }
        }

        foreach ($packages as $package) {
            // A local sync needs no content entry, because the packages it is
            // for are the ones whose commit cannot be pinned yet.
            if ($from === null && ! isset($content[$package])) {
                $this->error("{$package} has no content entry in config/phpstats.php.");

                return self::FAILURE;
            }
            if ($from !== null) {
                $this->syncFromPath($package, $content[$package]['repo'] ?? null, $from);

                continue;
            }
            $ref = $content[$package]['ref'];
            if ($this->option('update')) {
                $ref = $this->newestMain($content[$package]['repo']);
                $this->pin($package, $ref);
            }
            $this->syncFromGit($package, $content[$package]['repo'], $ref);
        }

        // The landing page's examples are cached per example file, so synced
        // content they read would otherwise stay stale.
        Example::flush();
        $this->info('Cleared the cached example output.');

        return self::SUCCESS;
    }

    private function syncFromGit(string $package, string $repo, string $ref): void
    {
        $checkout = storage_path("app/site-sync/{$package}");
        File::deleteDirectory($checkout);
        File::ensureDirectoryExists(dirname($checkout));

        $run = fn (array $command, ?string $cwd = null) => Process::path($cwd ?? base_path())->timeout(600)->run($command)->throw();
        $run(['git', 'clone', '--quiet', '--filter=blob:none', '--no-checkout', "https://github.com/{$repo}.git", $checkout]);
        $run(['git', 'sparse-checkout', 'set', '--no-cone', '/site/', '/docs/'], $checkout);
        $run(['git', 'checkout', '--quiet', $ref], $checkout);
        $commit = trim($run(['git', 'rev-parse', 'HEAD'], $checkout)->output());

        $this->import($package, $repo, $checkout, ['repo' => $repo, 'commit' => $commit]);

        File::deleteDirectory($checkout);
    }

    /**
     * Sync from a checkout on this machine. Nothing is pinned, because the
     * working tree it reads has no commit the site could name.
     */
    private function syncFromPath(string $package, ?string $repo, string $path): void
    {
        $source = realpath($path) ?: throw new RuntimeException("{$path} cannot be resolved.");
        $manifest = $this->manifest($package, $source, $source);
        // config/phpstats.php has no entry for a package that is not pinned yet.
        $repo ??= preg_replace('#^https?://github\.com/#', '', rtrim($manifest['repository'] ?? '', '/'));
        if ($repo === '' || $repo === null) {
            throw new RuntimeException("{$source}/site/site.json has no repository, and {$package} has no content entry in config/phpstats.php.");
        }

        $this->import($package, $repo, $source, ['repo' => $repo, 'local' => true, 'source' => $source]);
    }

    /**
     * Copy a checkout's site/ content and rendered docs into
     * resources/content/{package} and public/content/{package}. Both the
     * pinned and the local path come through here, so they produce the same
     * files; $provenance is what sync.json records about the source.
     */
    private function import(string $package, string $repo, string $checkout, array $provenance): void
    {
        $local = $provenance['local'] ?? false;
        $label = $local ? $checkout : "{$repo}@{$provenance['commit']}";

        $manifest = $this->manifest($package, $checkout, $label);
        $target = config('phpstats.content_path')."/{$package}";
        $assets = public_path("content/{$package}");
        File::deleteDirectory($target);
        File::deleteDirectory($assets);
        File::ensureDirectoryExists($target);

        // Benchmark, verification, and vignette files keep their paths under site/.
        $copied = ['benchmarks' => 0, 'verifications' => 0, 'vignettes' => 0];
        foreach (['benchmarks', 'verifications'] as $kind) {
            foreach ($manifest[$kind] ?? [] as $file) {
                $this->copyFromSite($checkout, $file, $target);
                $copied[$kind]++;
            }
        }
        foreach ($manifest['vignettes'] ?? [] as $vignette) {
            $this->copyFromSite($checkout, $vignette['script'], $target);
            $copied['vignettes']++;
        }
        if (is_dir("{$checkout}/site/data")) {
            File::copyDirectory("{$checkout}/site/data", "{$target}/data");
        }

        $docs = $manifest['docs'] ?? null;
        $pageCount = 0;
        if ($docs !== null) {
            $root = "{$checkout}/".trim($docs['root'] ?? 'docs', '/');
            $importer = new DocsImporter($package, $root, "{$assets}/docs", "/content/{$package}/docs", basename($repo));
            $paths = $importer->discover();

            // The pages are imported before the nav is built, because a nav
            // entry with no title of its own falls back to the page's heading.
            $index = [];
            foreach ($paths as $path) {
                $page = $importer->import($path);
                File::ensureDirectoryExists(dirname("{$target}/docs/{$path}.html"));
                File::put("{$target}/docs/{$path}.html", $page['html']."\n");
                $index[$path] = ['title' => $page['title'], 'headings' => $page['headings'], 'math' => $page['math']];
            }
            $pageCount = count($paths);

            $nav = $this->nav($importer, $docs, $index, $label);

            File::put("{$target}/docs.json", json_encode(['nav' => $nav, 'pages' => $index], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        }

        File::put("{$target}/site.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
        File::put("{$target}/sync.json", json_encode([
            ...$provenance,
            'synced_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        $this->info(sprintf('%s @ %s: %d docs pages, %d benchmarks, %d verifications, %d vignettes.',
            $package, $local ? "local {$checkout}" : substr($provenance['commit'], 0, 7),
            $pageCount, $copied['benchmarks'], $copied['verifications'], $copied['vignettes']));
        if ($local) {
            $this->warn("Not pinned: {$package} was read from a local checkout, so config/phpstats.php is unchanged and sync.json records no commit.");
        }
    }

    /**
     * The docs nav, derived from the package's own `docs/_quarto.yml` sidebar
     * and falling back to site.json's `docs.nav` when the docs hold none.
     *
     * Deriving it is what stops the nav going stale: `_quarto.yml` has to name
     * a page for Quarto to render it, while a page missing from site.json is
     * invisible. When this was written phit rendered 52 pages and named 16, so
     * 36 were synced, reachable by URL, and in no sidebar.
     *
     * Titles stay curated. An entry's own `text` in `_quarto.yml` wins, then
     * the title site.json gives that page, then the page's own heading, which
     * is what the package's docs site shows for a bare entry. So site.json's
     * `docs.nav` keeps its labels and stops deciding which pages exist.
     *
     * @param  array<string, mixed>  $docs  site.json's docs block
     * @param  array<string, array{title: string, headings: list<array<string, mixed>>, math: bool}>  $pages
     * @return list<array{title: string, path: string, section?: string}>
     */
    private function nav(DocsImporter $importer, array $docs, array $pages, string $label): array
    {
        $labels = [];
        foreach ($docs['nav'] ?? [] as $item) {
            $labels[$this->page($item['path'])] = $item['title'];
        }

        $derived = $importer->quartoNav();
        if ($derived === null) {
            $this->warn("{$label}: the docs hold no _quarto.yml sidebar, so the nav is site.json's.");
            $derived = array_map(fn (array $item) => [
                'path' => $this->page($item['path']),
                'title' => null,
                'section' => $item['section'] ?? null,
            ], $docs['nav'] ?? []);
        }

        // A label for a page the sidebar no longer names is only cosmetic, so
        // it is said out loud rather than failing the sync.
        $stale = array_values(array_diff(array_keys($labels), array_column($derived, 'path')));
        if ($stale !== []) {
            $this->line("  {$label}: site.json docs.nav titles ".implode(', ', $stale).', which the nav does not list, so they go unused.');
        }

        foreach ($importer->skippedNavEntries() as $entry) {
            if ($entry['reason'] === 'unrendered') {
                throw new RuntimeException("{$label}: the docs nav lists {$entry['target']}, but its rendered HTML is missing.");
            }
            $this->line("  {$label}: the nav entry {$entry['target']} is no page of this site, and is left out.");
        }

        $nav = [];
        foreach ($derived as $item) {
            $path = $item['path'];
            if (! isset($pages[$path])) {
                throw new RuntimeException("{$label}: the docs nav lists {$path}, but its rendered HTML is missing.");
            }
            // Package::docsGroups() groups by an item's own section first.
            $nav[] = array_filter([
                'title' => $item['title'] ?? $labels[$path] ?? $pages[$path]['title'],
                'path' => $path,
                'section' => $item['section'] ?? null,
            ], fn (mixed $value) => $value !== null);
        }

        $this->checkEveryPageIsPlaced(array_keys($pages), array_column($nav, 'path'), $docs['exclude'] ?? null, $label);

        return $nav;
    }

    /**
     * Fail the sync unless every rendered page is either in the nav or
     * excluded on purpose, and unless every exclusion still earns its place.
     * See DOCS_EXCLUDE for the rule and why it is this one.
     *
     * @param  list<string>  $pages  every rendered page
     * @param  list<string>  $navigated  the pages the nav lists
     * @param  ?list<string>  $declared  site.json's docs.exclude, when it has one
     */
    private function checkEveryPageIsPlaced(array $pages, array $navigated, ?array $declared, string $label): void
    {
        $covered = [];
        foreach ($declared ?? self::DOCS_EXCLUDE as $rule) {
            $covered[$rule] = array_values(array_filter(
                $pages,
                fn (string $page) => str_ends_with($rule, '/') ? str_starts_with($page, $rule) : $page === $rule,
            ));
        }
        $excluded = array_merge([], ...array_values($covered));

        $hidden = array_values(array_intersect($excluded, $navigated));
        if ($hidden !== []) {
            throw new RuntimeException("{$label}: docs.exclude covers ".implode(', ', $hidden)
                .', which the nav lists. Drop the exclusion or the nav entry.');
        }
        foreach ($covered as $rule => $matched) {
            if ($matched === [] && $declared !== null) {
                throw new RuntimeException("{$label}: docs.exclude has {$rule}, which covers no rendered page.");
            }
            if ($matched !== []) {
                $this->line(sprintf('  %s: docs.exclude %s leaves out %d page%s: %s.',
                    $label, $rule, count($matched), count($matched) === 1 ? '' : 's', implode(', ', $matched)));
            }
        }

        $unplaced = array_values(array_diff($pages, $navigated, $excluded));
        if ($unplaced !== []) {
            throw new RuntimeException(sprintf('%s: %d rendered page%s in no nav and no docs.exclude rule: %s. '
                ."Name each one in docs/_quarto.yml's sidebar, or declare it in site.json docs.exclude.",
                $label, count($unplaced), count($unplaced) === 1 ? ' is' : 's are', implode(', ', $unplaced)));
        }
    }

    /** A nav path as the site keys its pages: the source extension dropped. */
    private function page(string $path): string
    {
        return preg_replace('/\.(qmd|md|ipynb)$/', '', $path);
    }

    /** @return array<string, mixed> the checkout's site/site.json, checked against the package it is for */
    private function manifest(string $package, string $checkout, string $label): array
    {
        $file = "{$checkout}/site/site.json";
        if (! is_file($file)) {
            throw new RuntimeException("{$label}: site/site.json is missing.");
        }
        $manifest = json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['package'] ?? null) !== $package) {
            throw new RuntimeException("{$label}: site/site.json names package ".json_encode($manifest['package'] ?? null).", expected {$package}.");
        }

        return $manifest;
    }

    private function copyFromSite(string $checkout, string $file, string $target): void
    {
        if (! str_starts_with($file, 'site/') || str_contains($file, '..')) {
            throw new RuntimeException("{$file} is not under site/.");
        }
        $source = "{$checkout}/{$file}";
        if (! is_file($source)) {
            throw new RuntimeException("site.json lists {$file}, but the file is missing.");
        }
        $destination = "{$target}/".substr($file, strlen('site/'));
        File::ensureDirectoryExists(dirname($destination));
        File::copy($source, $destination);
    }

    private function newestMain(string $repo): string
    {
        $output = Process::run(['git', 'ls-remote', "https://github.com/{$repo}.git", 'refs/heads/main'])->throw()->output();

        return strtok($output, "\t");
    }

    /** Rewrite the ref in config/phpstats.php so the new pin is committed with the content. */
    private function pin(string $package, string $ref): void
    {
        $file = config_path('phpstats.php');
        $pattern = "/('".preg_quote($package, '/')."' => \['repo' => '[^']+', 'ref' => ')[0-9a-f]+(')/";
        $updated = preg_replace($pattern, '${1}'.$ref.'${2}', File::get($file), 1, $count);
        if ($count !== 1) {
            throw new RuntimeException("Could not find the {$package} ref in config/phpstats.php.");
        }
        File::put($file, $updated);
        $this->line("Pinned {$package} to {$ref}.");
    }
}
