<?php

namespace App\Content;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\File;
use RuntimeException;

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

    /**
     * @param string $docsRoot absolute path to the docs root in the checkout
     * @param string $assetsDir absolute path where copied files go (public/content/{package}/docs)
     * @param string $assetsUrl URL path for that directory (/content/{package}/docs)
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

        return $found;
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
