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

            $nav = [];
            foreach ($docs['nav'] ?? [] as $item) {
                $page = preg_replace('/\.(qmd|md|ipynb)$/', '', $item['path']);
                if (! in_array($page, $paths, true)) {
                    throw new RuntimeException("{$label}: docs.nav lists {$item['path']}, but its rendered HTML is missing.");
                }
                // Package::docsGroups() groups by an item's own section first.
                $nav[] = array_filter([
                    'title' => $item['title'],
                    'path' => $page,
                    'section' => $item['section'] ?? null,
                ], fn (mixed $value) => $value !== null);
            }

            $index = [];
            foreach ($paths as $path) {
                $page = $importer->import($path);
                File::ensureDirectoryExists(dirname("{$target}/docs/{$path}.html"));
                File::put("{$target}/docs/{$path}.html", $page['html']."\n");
                $index[$path] = ['title' => $page['title'], 'headings' => $page['headings'], 'math' => $page['math']];
            }
            $pageCount = count($paths);

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
