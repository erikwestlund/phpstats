<?php

namespace App\Console\Commands;

use App\Content\DocsImporter;
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
        {--update : Move the pinned ref to the newest commit on main first}';

    protected $description = 'Sync package docs, benchmarks, verifications, and vignettes at their pinned commits';

    public function handle(): int
    {
        $content = config('phpstats.content');
        $packages = $this->argument('package') ?: array_keys($content);

        foreach ($packages as $package) {
            if (! isset($content[$package])) {
                $this->error("{$package} has no content entry in config/phpstats.php.");

                return self::FAILURE;
            }
            $ref = $content[$package]['ref'];
            if ($this->option('update')) {
                $ref = $this->newestMain($content[$package]['repo']);
                $this->pin($package, $ref);
            }
            $this->sync($package, $content[$package]['repo'], $ref);
        }

        return self::SUCCESS;
    }

    private function sync(string $package, string $repo, string $ref): void
    {
        $checkout = storage_path("app/site-sync/{$package}");
        File::deleteDirectory($checkout);
        File::ensureDirectoryExists(dirname($checkout));

        $run = fn (array $command, ?string $cwd = null) => Process::path($cwd ?? base_path())->timeout(600)->run($command)->throw();
        $run(['git', 'clone', '--quiet', '--filter=blob:none', '--no-checkout', "https://github.com/{$repo}.git", $checkout]);
        $run(['git', 'sparse-checkout', 'set', '--no-cone', '/site/', '/docs/'], $checkout);
        $run(['git', 'checkout', '--quiet', $ref], $checkout);
        $commit = trim($run(['git', 'rev-parse', 'HEAD'], $checkout)->output());

        $manifest = json_decode(File::get("{$checkout}/site/site.json"), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['package'] ?? null) !== $package) {
            throw new RuntimeException("{$repo}: site/site.json names package ".json_encode($manifest['package'] ?? null).", expected {$package}.");
        }

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
                    throw new RuntimeException("{$repo}@{$commit}: docs.nav lists {$item['path']}, but its rendered HTML is missing.");
                }
                $nav[] = ['title' => $item['title'], 'path' => $page];
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
            'repo' => $repo,
            'commit' => $commit,
            'synced_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        File::deleteDirectory($checkout);

        $this->info(sprintf('%s @ %s: %d docs pages, %d benchmarks, %d verifications, %d vignettes.',
            $package, substr($commit, 0, 7), $pageCount, $copied['benchmarks'], $copied['verifications'], $copied['vignettes']));
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
