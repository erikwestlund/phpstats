<?php

namespace App\Content;

use Dom\HTMLDocument;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Turns a package's rendered Quarto pages into fragments for the site layout.
 *
 * Each page keeps the content of <main id="quarto-document-content"> with its
 * math spans, pandoc highlighting, tables, and callouts. Quarto's title block,
 * scripts, and copy buttons are dropped. Links between pages become
 * phpstats://{package}/docs/{path} placeholders that the site resolves for the
 * current host, and figures and other files are copied under public/content.
 */
class DocsImporter
{
    /** GitHub Pages repository names that are packages of this site. */
    public const PAGES_REPOS = [
        'phit' => 'phit',
        'phit-polyfill' => 'phit-polyfill',
        'phibble' => 'phibble',
        'phplyr' => 'phplyr',
        'phiz' => 'phiz',
        'phpolars' => 'phpolars',
        'cmdstanphp' => 'cmdstanphp',
        'CmdStanPHP' => 'cmdstanphp',
    ];

    /** @var array<string, true> page paths without extension, relative to the docs root */
    private array $pages = [];

    /** @var list<array{target: string, reason: string}> sidebar entries the site cannot serve */
    private array $skipped = [];

    /** Whether discover() has run, which a package with nothing rendered cannot show by its pages. */
    private bool $discovered = false;

    /**
     * @param  string  $docsRoot  absolute path to the docs root in the checkout
     * @param  string  $assetsDir  absolute path where copied files go (public/content/{package}/docs)
     * @param  string  $assetsUrl  URL path for that directory (/content/{package}/docs)
     */
    public function __construct(
        private readonly string $package,
        private readonly string $docsRoot,
        private readonly string $assetsDir,
        private readonly string $assetsUrl,
        private readonly string $pagesRepo,
    ) {}

    /**
     * Every rendered page: an .html file with a .qmd source next to it,
     * outside Quarto's _-prefixed and site_libs folders.
     *
     * @return list<string>
     */
    public function discover(): array
    {
        $found = [];
        foreach (File::allFiles($this->docsRoot) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            if (! str_ends_with($relative, '.html') || preg_match('#(^|/)(_|site_libs/)#', $relative)) {
                continue;
            }
            $path = substr($relative, 0, -5);
            if (is_file("{$this->docsRoot}/{$path}.qmd")) {
                $found[] = $path;
            }
        }
        sort($found);
        $this->pages = array_fill_keys($found, true);
        $this->discovered = true;

        return $found;
    }

    /**
     * The docs nav as the package's own Quarto sidebar orders it.
     *
     * A package maintains `docs/_quarto.yml` because its docs do not render
     * without it, so the sidebar there is the one nav that cannot quietly go
     * stale. Entries come back in sidebar order, each with the title Quarto
     * would show for it (its `text`, or none, which leaves the caller to fill
     * in a better one) and the enclosing `section` as task 9 carries it.
     * Null when the docs hold no sidebar at all, which leaves the caller
     * site.json's `docs.nav`.
     *
     * An entry the site cannot serve is left out and recorded for
     * skippedNavEntries(): a link off the docs, and a rendered page with no
     * `.qmd` beside it, such as phiz's `docs/gallery.html`.
     *
     * @return ?list<array{path: string, title: ?string, section: ?string}>
     */
    public function quartoNav(): ?array
    {
        $file = null;
        foreach (['_quarto.yml', '_quarto.yaml'] as $name) {
            if (is_file("{$this->docsRoot}/{$name}")) {
                $file = "{$this->docsRoot}/{$name}";
                break;
            }
        }
        if ($file === null) {
            return null;
        }
        if (! $this->discovered) {
            throw new RuntimeException("{$this->package}: discover() has to run before the nav is derived from {$file}.");
        }

        $yaml = Yaml::parseFile($file);
        $contents = is_array($yaml) ? $this->sidebarContents($yaml['website'] ?? []) : null;
        if ($contents === null) {
            return null;
        }

        $this->skipped = [];
        $items = [];
        $this->walkSidebar($contents, null, $items);

        return array_values($items);
    }

    /**
     * The sidebar entries quartoNav() left out, so the caller can tell a
     * rendered page it simply does not serve from a page whose HTML is gone.
     *
     * @return list<array{target: string, reason: string}>
     */
    public function skippedNavEntries(): array
    {
        return $this->skipped;
    }

    /**
     * A website's sidebar contents, or its navbar's when it has no sidebar.
     * Quarto allows several sidebars, so the first one with contents wins.
     *
     * @param  array<mixed>  $website
     * @return ?array<mixed>
     */
    private function sidebarContents(array $website): ?array
    {
        $sidebar = $website['sidebar'] ?? null;
        $sidebars = is_array($sidebar) && array_is_list($sidebar) ? $sidebar : [$sidebar];
        foreach ($sidebars as $candidate) {
            if (is_array($candidate) && is_array($candidate['contents'] ?? null)) {
                return $candidate['contents'];
            }
        }

        $navbar = $website['navbar'] ?? null;
        foreach (['contents', 'left', 'right'] as $side) {
            if (is_array($navbar) && is_array($navbar[$side] ?? null)) {
                return $navbar[$side];
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $nodes
     * @param  array<string, array{path: string, title: ?string, section: ?string}>  $items
     */
    private function walkSidebar(array $nodes, ?string $section, array &$items): void
    {
        foreach ($nodes as $node) {
            if (is_string($node)) {
                $this->addNavItem($node, null, $section, $items);

                continue;
            }
            if (! is_array($node)) {
                continue;
            }
            if (is_array($node['contents'] ?? null)) {
                // The group's own name, or the one it sits in when Quarto's
                // entry is nothing but a container.
                $name = $node['section'] ?? $node['text'] ?? null;
                $named = is_string($name) && trim($name) !== '' && ! str_contains($name, '.qmd');
                $this->walkSidebar($node['contents'], $named ? trim($name) : $section, $items);

                continue;
            }
            $target = $node['href'] ?? $node['file'] ?? null;
            if (is_string($target)) {
                $title = $node['text'] ?? null;
                $this->addNavItem($target, is_string($title) ? trim($title) : null, $section, $items);
            }
        }
    }

    /**
     * @param  array<string, array{path: string, title: ?string, section: ?string}>  $items
     */
    private function addNavItem(string $target, ?string $title, ?string $section, array &$items): void
    {
        if ($target === '' || preg_match('#^([a-z][a-z0-9+.-]*:|/|\#)#i', $target)) {
            $this->skipped[] = ['target' => $target, 'reason' => 'off-site'];

            return;
        }

        [$path] = $this->splitFragment($target);
        $path = $this->normalize($path);
        if ($path === null) {
            $this->skipped[] = ['target' => $target, 'reason' => 'off-site'];

            return;
        }
        if ($path === '' || str_ends_with($path, '/')) {
            $path .= 'index.qmd';
        }

        $page = preg_replace('/\.(qmd|md|ipynb|html)$/', '', $path);
        if (! isset($this->pages[$page])) {
            // A .qmd the sidebar names should have rendered; anything else is
            // a file the site does not serve as a page.
            $this->skipped[] = [
                'target' => $target,
                'reason' => str_ends_with($path, '.qmd') ? 'unrendered' : 'not-a-page',
            ];

            return;
        }

        // A sidebar may name a page twice; the first entry decides its place.
        $items[$page] ??= ['path' => $page, 'title' => $title, 'section' => $section];
    }

    /**
     * @return array{title: string, html: string, headings: list<array{id: string, text: string, level: int}>, math: bool}
     */
    public function import(string $path): array
    {
        $file = "{$this->docsRoot}/{$path}.html";
        if (! is_file($file)) {
            throw new RuntimeException("{$this->package}: the rendered page {$path}.html is missing.");
        }

        $document = HTMLDocument::createFromString(file_get_contents($file), LIBXML_NOERROR);
        $main = $document->getElementById('quarto-document-content') ?? $document->querySelector('main');
        if ($main === null) {
            throw new RuntimeException("{$this->package}: {$path}.html has no main content.");
        }

        $title = trim($main->querySelector('h1.title')?->textContent
            ?? $document->querySelector('title')?->textContent ?? $path);
        $title = trim(preg_replace('/\s+/', ' ', $title));

        foreach ($main->querySelectorAll('#title-block-header, .code-copy-button, script:not([type*="json"]), .quarto-alternate-formats, .quarto-alternate-notebooks') as $node) {
            $node->remove();
        }

        $directory = str_contains($path, '/') ? dirname($path) : '';
        foreach ($main->querySelectorAll('a[href]') as $link) {
            $link->setAttribute('href', $this->resolveLink($link->getAttribute('href'), $directory));
        }
        foreach ($main->querySelectorAll('img[src], source[src], video[src], audio[src], embed[src], iframe[src]') as $node) {
            $node->setAttribute('src', $this->resolveAsset($node->getAttribute('src'), $directory));
        }
        foreach ($main->querySelectorAll('video[poster]') as $node) {
            $node->setAttribute('poster', $this->resolveAsset($node->getAttribute('poster'), $directory));
        }
        foreach ($main->querySelectorAll('object[data]') as $node) {
            $node->setAttribute('data', $this->resolveAsset($node->getAttribute('data'), $directory));
        }

        $headings = [];
        foreach ($main->querySelectorAll('section.level2, section.level3') as $section) {
            $heading = $section->querySelector('h2, h3');
            if ($heading !== null && $section->getAttribute('id')) {
                $headings[] = [
                    'id' => $section->getAttribute('id'),
                    'text' => trim(preg_replace('/\s+/', ' ', $heading->textContent)),
                    'level' => $section->classList->contains('level2') ? 2 : 3,
                ];
            }
        }

        $html = '';
        foreach ($main->childNodes as $child) {
            $html .= $document->saveHtml($child);
        }

        return [
            'title' => $title,
            'html' => trim($html),
            'headings' => $headings,
            'math' => $main->querySelector('.math') !== null,
        ];
    }

    private function resolveLink(string $href, string $directory): string
    {
        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:')) {
            return $href;
        }

        if (preg_match('#^https?://(?:erikwestlund|phpstats)\.github\.io/([^/]+)/?(.*)$#', $href, $m)) {
            $package = self::PAGES_REPOS[$m[1]] ?? null;
            if ($package !== null) {
                [$target, $fragment] = $this->splitFragment($m[2]);
                $target = $target === '' ? 'index.html' : (str_ends_with($target, '/') ? "{$target}index.html" : $target);
                if (str_ends_with($target, '.html')) {
                    return $this->placeholder($package, substr($target, 0, -5), $fragment);
                }
            }

            return $href;
        }

        // Links to the production package hosts resolve per host too, so a
        // preview or staging page links within itself.
        if (preg_match('#^https?://([a-z0-9-]+)\.phpstats\.org(/[^?\#]*)?(\#.*)?$#', $href, $m) && isset(config('projects')[$m[1]])) {
            $path = trim($m[2] ?? '', '/');
            $fragment = $m[3] ?? '';
            if ($path === '') {
                return "phpstats://{$m[1]}/{$fragment}";
            }
            if ($path === 'docs' || str_starts_with($path, 'docs/')) {
                $page = preg_replace('#\.html$#', '', substr($path, 5) ?: 'index');

                return $this->placeholder($m[1], $page, $fragment);
            }

            return $href;
        }

        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $href) || str_starts_with($href, '/')) {
            return $href;
        }

        [$target, $fragment] = $this->splitFragment($href);
        if ($target === '') {
            return $href;
        }
        $resolved = $this->normalize($directory === '' ? $target : "{$directory}/{$target}");
        if ($resolved === null) {
            return $href;
        }
        if (str_ends_with($resolved, '/') || $resolved === '') {
            $resolved .= 'index.html';
        }

        if (str_ends_with($resolved, '.html')) {
            $page = substr($resolved, 0, -5);
            if (isset($this->pages[$page])) {
                return $this->placeholder($this->package, $page, $fragment);
            }

            return "https://phpstats.github.io/{$this->pagesRepo}/{$resolved}{$fragment}";
        }

        return $this->copyAsset($resolved).$fragment;
    }

    private function resolveAsset(string $src, string $directory): string
    {
        if ($src === '' || preg_match('#^([a-z][a-z0-9+.-]*:|/|data:)#i', $src)) {
            return $src;
        }
        [$target, $fragment] = $this->splitFragment($src);
        $resolved = $this->normalize($directory === '' ? $target : "{$directory}/{$target}");

        return $resolved === null ? $src : $this->copyAsset($resolved).$fragment;
    }

    private function copyAsset(string $relative): string
    {
        $source = "{$this->docsRoot}/{$relative}";
        if (! is_file($source)) {
            return "https://phpstats.github.io/{$this->pagesRepo}/{$relative}";
        }
        $destination = "{$this->assetsDir}/{$relative}";
        File::ensureDirectoryExists(dirname($destination));
        File::copy($source, $destination);

        return "{$this->assetsUrl}/".implode('/', array_map('rawurlencode', explode('/', $relative)));
    }

    private function placeholder(string $package, string $page, string $fragment): string
    {
        return "phpstats://{$package}/docs/{$page}{$fragment}";
    }

    /** @return array{0: string, 1: string} */
    private function splitFragment(string $href): array
    {
        $position = strcspn($href, '?#');

        return [rawurldecode(substr($href, 0, $position)), substr($href, $position)];
    }

    /** Resolve . and .. segments; null when the path leaves the docs root. */
    private function normalize(string $path): ?string
    {
        $parts = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($parts === []) {
                    return null;
                }
                array_pop($parts);

                continue;
            }
            $parts[] = $segment;
        }

        return implode('/', $parts).(str_ends_with($path, '/') ? '/' : '');
    }
}
