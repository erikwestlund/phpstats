<?php

namespace App\Content;

use Illuminate\Support\Arr;

/**
 * A package of the family: its entry in config/projects.php plus whatever
 * `site:sync` copied from its repo into resources/content/{key}.
 */
class Package
{
    /** @var array<string, self> */
    private static array $cache = [];

    private ?array $site;

    private ?array $docs;

    private function __construct(public readonly string $key, public readonly array $project)
    {
        $this->site = $this->readJson('site.json');
        $this->docs = $this->readJson('docs.json');
    }

    public static function find(string $key): ?self
    {
        $project = config("projects.{$key}");
        if ($project === null) {
            return null;
        }

        return self::$cache[$key] ??= new self($key, $project);
    }

    /** @return array<string, self> */
    public static function all(): array
    {
        return array_map(fn (string $key) => self::find($key), array_combine(array_keys(config('projects')), array_keys(config('projects'))));
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    public function path(string $relative = ''): string
    {
        return config('phpstats.content_path')."/{$this->key}".($relative === '' ? '' : "/{$relative}");
    }

    public function synced(): bool
    {
        return $this->site !== null;
    }

    public function sync(): ?array
    {
        return $this->readJson('sync.json');
    }

    public function name(): string
    {
        return $this->site['title'] ?? $this->project['name'];
    }

    public function tagline(): string
    {
        return $this->site['tagline'] ?? $this->project['summary'];
    }

    /** @return list<string> */
    public function summary(): array
    {
        return isset($this->site['summary']) ? [$this->site['summary']] : $this->project['overview'];
    }

    /** @return list<string> */
    public function install(): array
    {
        return $this->site['install'] ?? [];
    }

    /** @return list<string> */
    public function requires(): array
    {
        return $this->site['requires'] ?? [];
    }

    public function repository(): ?string
    {
        return $this->site['repository'] ?? $this->project['repo'];
    }

    /** @return array{chain: ?string, pipe: ?string, output: ?string}|null */
    public function example(): ?array
    {
        $example = $this->site['example'] ?? null;
        if (! is_array($example) || (empty($example['chain']) && empty($example['pipe']))) {
            return null;
        }

        return ['chain' => $example['chain'] ?: null, 'pipe' => $example['pipe'] ?? null, 'output' => $example['output'] ?? null];
    }

    public function url(string $section = 'overview', array $parameters = []): string
    {
        return route("package.{$section}", ['package' => $this->key, ...$parameters]);
    }

    // Docs

    public function hasDocs(): bool
    {
        return $this->docs !== null;
    }

    /** The external documentation site, for packages whose docs are not synced. */
    public function externalDocs(): ?string
    {
        return $this->project['docs'] ?? null;
    }

    /** @return list<array{title: string, path: string}> */
    public function docsNav(): array
    {
        return $this->docs['nav'] ?? [];
    }

    /** @return array{title: string, headings: list<array>, math: bool, html: string}|null */
    public function docsPage(string $path): ?array
    {
        $meta = $this->docs['pages'][$path] ?? null;
        if ($meta === null || ! preg_match('#^[A-Za-z0-9._/-]+$#', $path) || str_contains($path, '..')) {
            return null;
        }

        return [...$meta, 'html' => self::resolveLinks(file_get_contents($this->path("docs/{$path}.html")))];
    }

    /** Replace phpstats://{package}/docs/{path} placeholders with links for the current hosts. */
    public static function resolveLinks(string $html): string
    {
        return preg_replace_callback('#phpstats://([a-z0-9-]+)/docs/([^"\'\#\s]*)(\#[^"\'\s]*)?#', function (array $m) {
            $target = self::find($m[1]);
            $page = $m[2];
            $fragment = $m[3] ?? '';
            if ($target?->hasDocs() && isset($target->docs['pages'][$page])) {
                return e(route('package.docs', ['package' => $target->key, 'path' => $page === 'index' ? null : $page])).$fragment;
            }
            $external = rtrim($target?->externalDocs() ?? 'https://github.com/phpstats', '/');

            return e("{$external}/{$page}.html").$fragment;
        }, $html);
    }

    // Results

    /** @return list<array> benchmark files in manifest order */
    public function benchmarks(): array
    {
        return $this->results('benchmarks');
    }

    /** @return list<array> verification files in manifest order */
    public function verifications(): array
    {
        return $this->results('verifications');
    }

    private function results(string $kind): array
    {
        $files = [];
        foreach ($this->site[$kind] ?? [] as $file) {
            $data = $this->readJson(substr($file, strlen('site/')));
            if ($data !== null) {
                $files[] = ['id' => pathinfo($file, PATHINFO_FILENAME), ...$data];
            }
        }

        return $files;
    }

    // Vignettes

    /**
     * Vignettes for this package: synced scripts first, then the site's own
     * Livewire vignettes that list this package.
     *
     * @return list<array{slug: string, title: string, summary: string, kind: string, owner: string}>
     */
    public function vignettes(): array
    {
        $list = [];
        foreach ($this->site['vignettes'] ?? [] as $vignette) {
            $list[] = [
                'slug' => $vignette['slug'],
                'title' => $vignette['title'],
                'summary' => $vignette['summary'] ?? '',
                'uses' => $vignette['uses'] ?? [$this->key],
                'kind' => 'script',
                'owner' => $this->key,
            ];
        }
        foreach (config('phpstats.vignettes') as $slug => $vignette) {
            if (in_array($this->key, $vignette['packages'], true)) {
                $list[] = [
                    'slug' => $slug,
                    'title' => $vignette['title'],
                    'summary' => $vignette['summary'],
                    'uses' => $vignette['packages'],
                    'kind' => 'livewire',
                    'owner' => $vignette['packages'][0],
                ];
            }
        }

        return $list;
    }

    /** The vignette with this slug owned by this package. */
    public function vignette(string $slug): ?array
    {
        return Arr::first($this->vignettes(), fn (array $v) => $v['slug'] === $slug && $v['owner'] === $this->key);
    }

    public function vignetteScript(string $slug): ?string
    {
        foreach ($this->site['vignettes'] ?? [] as $vignette) {
            if ($vignette['slug'] === $slug) {
                return $this->path(substr($vignette['script'], strlen('site/')));
            }
        }

        return null;
    }

    private function readJson(string $relative): ?array
    {
        $file = $this->path($relative);

        return is_file($file) ? json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR) : null;
    }
}
