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

    /**
     * The packages in the layers' order, then any package in no layer.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        $keys = array_values(array_diff(array_keys(config('projects')), array_keys(config('phpstats.companions'))));
        $ordered = array_merge(...array_column(config('phpstats.layers'), 'packages'));

        return array_values(array_unique([...array_intersect($ordered, $keys), ...$keys]));
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

    /**
     * The package's short description, from config/projects.php, shown in
     * the menus, the landing page's package list, and the overview's lead.
     */
    public function tagline(): string
    {
        return $this->project['summary'];
    }

    /**
     * The overview paragraphs. A package's own site.json wins when it ships a
     * list of them; a single string is thinner than the shaped paragraphs in
     * config/projects.php, so those are kept instead.
     *
     * @return list<string>
     */
    public function summary(): array
    {
        $paragraphs = $this->site['summary'] ?? null;
        if (is_array($paragraphs) && array_is_list($paragraphs) && $paragraphs !== []
            && $paragraphs === array_filter($paragraphs, 'is_string')) {
            return $paragraphs;
        }

        return $this->project['overview'];
    }

    /**
     * A printed phibble or phplyr table heads with "# A tibble: n × m". Pages
     * show that size next to the output instead of inside it.
     *
     * @return array{0: string, 1: ?string} the text without the header, and the size
     */
    public static function splitTableHeader(string $text): array
    {
        if (preg_match('/\A# A tibble: ([^\n]+)\n/u', $text, $matches)) {
            return [substr($text, strlen($matches[0])), $matches[1]];
        }

        return [$text, null];
    }

    /**
     * The overview's capabilities from config/projects.php: lists side by
     * side, then one full width, each with its heading's link (the docs
     * nav's Functions and methods section, else the named docs page), its
     * optional lead sentence, and its items as inline Markdown.
     *
     * @return array{columns: list<array{heading: string, url: ?string, lead: ?string, items: list<string>}>, other: ?array{heading: string, url: ?string, lead: ?string, items: list<string>}}|null
     */
    public function capabilities(): ?array
    {
        $capabilities = $this->project['capabilities'] ?? null;
        if (! is_array($capabilities)) {
            return null;
        }
        $api = $this->apiReference()['url'] ?? null;
        $list = fn (array $block) => [
            'heading' => (string) $block['heading'],
            'url' => $api ?? (isset($block['docs']) && $this->docsPage($block['docs']) !== null ? $this->url('docs', ['path' => $block['docs']]) : null),
            'lead' => $block['lead'] ?? null,
            'items' => array_values($block['items'] ?? []),
        ];

        return [
            'columns' => array_map($list, $capabilities['columns'] ?? []),
            'other' => isset($capabilities['other']) ? $list($capabilities['other']) : null,
        ];
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

    /**
     * Optional dependencies, each with the feature it enables when the
     * package says. A plain string in site.json is a name with no feature.
     *
     * @return list<array{name: string, for: ?string}>
     */
    public function optional(): array
    {
        return array_map(
            fn (string|array $item) => is_string($item)
                ? ['name' => $item, 'for' => null]
                : ['name' => (string) ($item['name'] ?? ''), 'for' => $item['for'] ?? null],
            $this->site['optional'] ?? [],
        );
    }

    /** @return list<string> the PHP minor versions the package's composer.json allows */
    public function php(): array
    {
        return $this->site['php'] ?? [];
    }

    /** @return list<string> what a source build needs, for packages that build */
    public function build(): array
    {
        return $this->site['build'] ?? [];
    }

    /** What build() is needed for: building from source, unless the package's config says otherwise. */
    public function buildLabel(): string
    {
        return $this->project['build_label'] ?? 'To build from source';
    }

    /** @return list<string> */
    public function platforms(): array
    {
        return $this->site['platforms'] ?? [];
    }

    /** @return array{command: string, output: ?string, note: ?string}|null a command that checks the install, and a note such as how PHP-FPM differs */
    public function verify(): ?array
    {
        $verify = $this->site['verify'] ?? null;

        return isset($verify['command'])
            ? ['command' => (string) $verify['command'], 'output' => $verify['output'] ?? null, 'note' => $verify['note'] ?? null]
            : null;
    }

    /** The URL of the package's getting-started docs page, when it names one that was synced. */
    public function quickstart(): ?string
    {
        $path = $this->site['quickstart'] ?? null;

        return is_string($path) && $this->docsPage($path) !== null ? $this->url('docs', ['path' => $path]) : null;
    }

    /**
     * The URL of the package's installation guide, which holds each optional
     * feature's detail: site.json's installation path, with an optional
     * fragment, when it names a synced page, else the getting-started page.
     */
    public function installation(): ?string
    {
        $target = $this->site['installation'] ?? null;
        if (is_string($target)) {
            [$path, $fragment] = array_pad(explode('#', $target, 2), 2, null);
            if ($this->docsPage($path) !== null) {
                return $this->url('docs', ['path' => $path === 'index' ? null : $path]).($fragment ? "#{$fragment}" : '');
            }
        }

        return $this->quickstart();
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

        $output = $example['output'] ?? null;

        return ['chain' => ($example['chain'] ?? null) ?: null, 'pipe' => $example['pipe'] ?? null, 'output' => $output, 'chart' => self::embeddedChart($output)];
    }

    /**
     * The Vega-Lite spec of an example whose output is phiz's embed(), a div
     * whose data-phiz attribute holds the spec, so the page draws the chart
     * with its own phiz component instead of printing the markup.
     */
    public static function embeddedChart(mixed $output): ?array
    {
        if (! is_string($output) || ! preg_match('/\A\s*<div data-phiz="([^"]*)"/', $output, $matches)) {
            return null;
        }
        $spec = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true);
        if (! is_array($spec)) {
            return null;
        }

        // A single chart with no width of its own fills the output column.
        if (! isset($spec['width']) && ! isset($spec['facet']) && ! isset($spec['hconcat']) && ! isset($spec['vconcat']) && ! isset($spec['concat'])) {
            $spec['width'] = 'container';
        }

        return $spec;
    }

    public function status(): string
    {
        return $this->project['status_label'];
    }

    /** @return array{key: string, title: string, color: string} */
    public function layer(): array
    {
        foreach (config('phpstats.layers') as $key => $layer) {
            if (in_array($this->key, $layer['packages'], true)) {
                return ['key' => $key, 'title' => $layer['title'], 'color' => $layer['color']];
            }
        }

        return ['key' => 'other', 'title' => 'Other', 'color' => 'purple'];
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

    /**
     * The docs section that is the package's API reference, by its nav
     * name: "Functions and methods", "Functions", or "Methods". Its URL is
     * the section's first page.
     *
     * @return array{title: string, url: string, count: int}|null
     */
    public function apiReference(): ?array
    {
        foreach ($this->docsGroups() as $group => $items) {
            if (in_array(strtolower($group), ['functions and methods', 'functions', 'methods'], true) && $items !== []) {
                $path = $items[0]['path'];

                return ['title' => $group, 'url' => $this->url('docs', ['path' => $path === 'index' ? null : $path]), 'count' => count($items)];
            }
        }

        return null;
    }

    /** The group a docs page is in. */
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

    /** Replace phpstats://{package}/docs/{path} and phpstats://{package}/{path} placeholders with links for the current hosts. */
    public static function resolveLinks(string $html): string
    {
        return preg_replace_callback('#phpstats://([a-z0-9-]+)/docs/([^"\'\#\s]*)(\#[^"\'\s]*)?#', function (array $m) {
            $target = self::find($m[1]);
            $page = $m[2];
            $fragment = $m[3] ?? '';
            // A report that left the docs for the evidence pages.
            if ($target && ($evidence = $target->evidenceRedirect($page))) {
                return e($evidence).$fragment;
            }
            if ($target?->hasDocs() && isset($target->docs['pages'][$page])) {
                return e(route('package.docs', ['package' => $target->key, 'path' => $page === 'index' ? null : $page])).$fragment;
            }
            $external = rtrim($target?->externalDocs() ?? 'https://github.com/phpstats', '/');

            return e("{$external}/{$page}.html").$fragment;
        }, preg_replace_callback('#phpstats://([a-z0-9-]+)/((?!docs/)[^"\'\#\s]*)#', function (array $m) {
            // Any other page of a package host, such as a live example.
            $base = self::find($m[1])?->url() ?? "https://{$m[1]}.phpstats.org";

            return e(rtrim($base, '/').($m[2] === '' ? '' : '/'.$m[2]));
        }, $html));
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

    /** The benchmark record with this id. */
    public function benchmark(string $id): ?array
    {
        return Arr::first($this->benchmarks(), fn (array $record) => $record['id'] === $id);
    }

    /**
     * The address of a benchmark workload: its anchor on the one Benchmarks
     * page. A renamed or merged record's old id gives its replacement's.
     */
    public function benchmarkUrl(string $id): string
    {
        $id = $this->benchmark($id) === null ? ($this->renamedTopics('benchmarks')[$id] ?? $id) : $id;

        return $this->url('benchmarks').'#'.$id;
    }

    /** The verification record with this id. */
    public function verificationTopic(string $id): ?array
    {
        return Arr::first($this->verifications(), fn (array $record) => $record['id'] === $id);
    }

    /**
     * Renamed or merged records of a section, as old id => current id, from
     * config/evidence.php and site.json's evidence_redirects. An entry whose
     * target is not a current record is left out.
     *
     * @param  'benchmarks'|'verification'  $section
     * @return array<string, string>
     */
    public function renamedTopics(string $section): array
    {
        $map = [...(array) ($this->site['evidence_redirects'][$section] ?? []), ...(array) config("evidence.renamed.{$this->key}.{$section}", [])];
        $find = $section === 'benchmarks' ? $this->benchmark(...) : $this->verificationTopic(...);

        return array_filter($map, fn ($target, $old) => is_string($target) && is_string($old) && $find($target) !== null && $find($old) === null, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * The sentence site.json's evidence_gaps gives for a section, naming what
     * is not measured or checked yet.
     *
     * @param  'benchmarks'|'verification'  $section
     */
    public function evidenceGap(string $section): ?string
    {
        $gap = $this->site['evidence_gaps'][$section] ?? null;

        return is_string($gap) && trim($gap) !== '' ? trim($gap) : null;
    }

    /**
     * The URL of a raw record on GitHub at the pinned commit, or null for a
     * local sync, which has no commit to link.
     *
     * @param  'benchmarks'|'verifications'  $kind
     */
    public function recordUrl(string $kind, string $id): ?string
    {
        return $this->sourceUrl("site/{$kind}/{$id}.json");
    }

    /** A repository path at the pinned commit on GitHub, or a URL as given. */
    public function sourceUrl(?string $path): ?string
    {
        // Only a single path or URL is linked; prose such as "a.php and a.R" is shown as text.
        if ($path === null || $path === '' || preg_match('/\s/', $path)) {
            return null;
        }
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }
        $sync = $this->sync();
        if ($sync === null || ($sync['local'] ?? false) || empty($sync['repo']) || empty($sync['commit'])) {
            return null;
        }

        return "https://github.com/{$sync['repo']}/blob/{$sync['commit']}/".ltrim($path, '/');
    }

    /**
     * The evidence page that replaced a docs report: benchmarks/{id} goes to
     * the benchmark topic, verifications/{id} and notebooks/{id}-verification
     * to the verification topic, and verification to the Verification
     * section. It applies only once the package has taken the page out of
     * its docs nav, so a report the package still lists keeps its page.
     */
    public function evidenceRedirect(string $path): ?string
    {
        if (in_array($path, array_column($this->docsNav(), 'path'), true)) {
            return null;
        }

        return $this->evidenceUrlFor($path);
    }

    /**
     * Sections of a docs page that moved to an evidence page, as fragment =>
     * URL, from config/evidence.php. A target whose record is gone is left out.
     *
     * @return array<string, string>
     */
    public function movedFragments(string $path): array
    {
        $urls = [];
        foreach (config("evidence.fragments.{$this->key}.{$path}", []) as $fragment => [$key, $route, $id]) {
            $target = self::find($key);
            $found = $target === null ? null : ($route === 'benchmark' ? $target->benchmark($id) : $target->verificationTopic($id));
            if ($found !== null) {
                $urls[$fragment] = $route === 'benchmark' ? $target->benchmarkUrl($id) : $target->url($route, ['id' => $id]);
            }
        }

        return $urls;
    }

    /** The evidence page that covers a docs path, whether or not the page is still in the docs. */
    private function evidenceUrlFor(string $path): ?string
    {
        $listed = config("evidence.docs.{$this->key}.{$path}");
        if (in_array($listed, ['benchmarks', 'verification'], true)) {
            return $this->url($listed);
        }

        return match (true) {
            $path === 'verification' || $path === 'verifications' || $path === 'verifications/index' => $this->url('verification'),
            $path === 'benchmarks' || $path === 'benchmarks/index' => $this->url('benchmarks'),
            (bool) preg_match('#^benchmarks/([a-z0-9-]+)$#', $path, $m) => $this->benchmark($m[1]) ? $this->benchmarkUrl($m[1]) : null,
            (bool) preg_match('#^(?:verifications/([a-z0-9-]+)|notebooks/([a-z0-9-]+?)(?:-verification)?)$#', $path, $m) => ($id = ($m[1] ?? '') ?: ($m[2] ?? '')) && $this->verificationTopic($id) ? $this->url('verification.topic', ['id' => $id]) : null,
            default => null,
        };
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

    /**
     * The examples this package owns, from site.json's examples list, in its
     * order. Each is a page of the package's docs, under its Examples section,
     * with the one-sentence description the indexes show. An entry whose page
     * did not sync is left out.
     *
     * @return list<array{path: string, title: string, description: string, url: string, owner: string, also: list<string>}>
     */
    public function examples(): array
    {
        $list = [];
        foreach ($this->site['examples'] ?? [] as $example) {
            $path = is_array($example) ? ($example['path'] ?? null) : null;
            if (! is_string($path) || ! isset($this->docs['pages'][$path])) {
                continue;
            }
            $list[] = [
                'path' => $path,
                'title' => (string) ($example['title'] ?? $this->docs['pages'][$path]['title'] ?? $path),
                'description' => (string) ($example['description'] ?? ''),
                'url' => $this->url('docs', ['path' => $path]),
                'owner' => $this->key,
                'also' => array_values(array_filter((array) ($example['also'] ?? []), 'is_string')),
            ];
        }

        return $list;
    }

    /**
     * The examples this package's Examples tab lists: its own, then other
     * packages' examples that name it under also.
     *
     * @return list<array{path: string, title: string, description: string, url: string, owner: string, also: list<string>}>
     */
    public function listedExamples(): array
    {
        $list = $this->examples();
        foreach (self::all() as $key => $other) {
            if ($key !== $this->key) {
                array_push($list, ...array_filter($other->examples(), fn (array $example) => in_array($this->key, $example['also'], true)));
            }
        }

        return $list;
    }

    /**
     * Where an example that left the site's live pages now lives: its slug in
     * config/phpstats.php's example_redirects, as the URL of the owning
     * package's docs page, or of that package's Examples tab until the page
     * has synced.
     */
    public static function exampleRedirect(string $slug): ?string
    {
        [$key, $path] = config("phpstats.example_redirects.{$slug}") ?? [null, null];
        $package = $key === null ? null : self::find($key);
        if ($package === null) {
            return null;
        }

        return isset($package->docs['pages'][$path]) ? $package->url('docs', ['path' => $path]) : $package->url('examples');
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
