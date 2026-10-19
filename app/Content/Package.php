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

    /**
     * Every package with its own pages: all of config/projects.php except
     * the companions in config/phpstats.php.
     *
     * @return array<string, self>
     */
    public static function all(): array
    {
        $keys = self::keys();

        return array_map(fn (string $key) => self::find($key), array_combine($keys, $keys));
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_values(array_diff(array_keys(config('projects')), array_keys(config('phpstats.companions'))));
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

    public function status(): string
    {
        return $this->project['status_label'];
    }

    /** @return array{key: string, title: string, role: string, color: string, description: string} */
    public function layer(): array
    {
        foreach (config('phpstats.layers') as $key => $layer) {
            if (isset($layer['packages'][$this->key])) {
                return ['key' => $key, 'title' => $layer['title'], 'role' => $layer['role'], 'color' => $layer['color'], 'description' => $layer['packages'][$this->key]];
            }
        }

        return ['key' => 'other', 'title' => 'Other', 'role' => '', 'color' => 'purple', 'description' => $this->project['summary']];
    }

    /**
     * Packages that are part of this one on the site, such as phit-polyfill
     * in phit.
     *
     * @return list<self>
     */
    public function companions(): array
    {
        return array_values(array_map(self::find(...), array_keys(config('phpstats.companions'), $this->key, true)));
    }

    /**
     * Packages this one uses or is used by, from config/phpstats.php.
     *
     * @return list<array{package: self, kind: 'uses'|'used_by', text: string}>
     */
    public function relations(): array
    {
        $relations = [];
        foreach (config('phpstats.relations') as [$from, $to, $text]) {
            if ($from === $this->key && ($package = self::find($to))) {
                $relations[] = ['package' => $package, 'kind' => 'uses', 'text' => $text];
            } elseif ($to === $this->key && ($package = self::find($from))) {
                $relations[] = ['package' => $package, 'kind' => 'used_by', 'text' => $text];
            }
        }
        usort($relations, fn (array $a, array $b) => ($a['kind'] === 'uses' ? 0 : 1) <=> ($b['kind'] === 'uses' ? 0 : 1));

        return $relations;
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

    /**
     * The docs nav in groups. An entry's own section key wins; otherwise
     * pages in a folder, such as reference/series, are grouped by the folder
     * and the rest form the Guide.
     *
     * @return array<string, list<array{title: string, path: string}>>
     */
    public function docsGroups(): array
    {
        $groups = [];
        foreach ($this->docsNav() as $item) {
            $group = $item['section'] ?? (str_contains($item['path'], '/') ? ucfirst(str_replace('-', ' ', strstr($item['path'], '/', true))) : 'Guide');
            $groups[$group][] = $item;
        }

        return $groups;
    }

    /** The group a docs page is in, for the page eyebrow. */
    public function docsGroupOf(string $path): ?string
    {
        foreach ($this->docsGroups() as $group => $items) {
            if (in_array($path, array_column($items, 'path'), true)) {
                return $group;
            }
        }

        return str_contains($path, '/') ? ucfirst(str_replace('-', ' ', strstr($path, '/', true))) : null;
    }

    /**
     * Every synced docs page, including those outside the nav, as the paths
     * the docs route takes.
     *
     * @return list<string>
     */
    public function docsPaths(): array
    {
        return array_keys($this->docs['pages'] ?? []);
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
