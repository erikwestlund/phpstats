<?php

namespace App\Content;

use Illuminate\Support\Facades\Blade;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Markdown pages written for this site, in resources/pages.
 *
 * A page may start with YAML front matter. Its body is CommonMark with GitHub
 * tables, and it can call PHP through Blade: Blade components (<x-... />) and
 * echoes ({{ ... }}) outside fenced code are rendered with Blade and placed in
 * the converted HTML, with $meta (the front matter) and $stats (counts from
 * App\Content\Stats) in scope. Fenced code is highlighted, and any words after the
 * language become the block's label, as in ```r dplyr.
 */
class Markdown
{
    private static ?MarkdownConverter $converter = null;

    /** @return array{meta: array, html: string} */
    public static function page(string $file, array $data = []): array
    {
        $source = file_get_contents($file);
        $meta = [];
        if (preg_match('/\A---\R(.*?)\R---\R/s', $source, $m)) {
            $meta = Yaml::parse($m[1]) ?? [];
            $source = substr($source, strlen($m[0]));
        }

        return ['meta' => $meta, 'html' => self::render($source, ['stats' => Stats::family(), ...$data, 'meta' => $meta])];
    }

    public static function render(string $markdown, array $data = []): string
    {
        $snippets = [];
        $parts = preg_split('/^((?:```|~~~).*?^(?:```|~~~)[ \t]*$)/ms', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                continue; // fenced code
            }
            $parts[$i] = preg_replace_callback(
                '/<x-([\w.:-]+)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*?\/>|<x-([\w.:-]+)\b.*?<\/x-\2>|\{\{.*?\}\}|\{!!.*?!!\}/s',
                function (array $m) use (&$snippets) {
                    $snippets[] = $m[0];

                    return '<!--blade:'.(count($snippets) - 1).'-->';
                },
                $part,
            );
        }

        $html = (string) self::converter()->convert(implode('', $parts));

        return preg_replace_callback('/<!--blade:(\d+)-->/', fn (array $m) => Blade::render($snippets[(int) $m[1]], $data), $html);
    }

    private static function converter(): MarkdownConverter
    {
        if (self::$converter === null) {
            $environment = new Environment(['html_input' => 'allow', 'allow_unsafe_links' => false]);
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new GithubFlavoredMarkdownExtension);
            $environment->addRenderer(FencedCode::class, new class implements NodeRendererInterface
            {
                public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
                {
                    $words = $node->getInfoWords();

                    return Blade::render('<x-code :code="$code" :lang="$lang" :label="$label" />', [
                        'code' => $node->getLiteral(),
                        'lang' => $words[0] ?? 'txt',
                        'label' => trim(implode(' ', array_slice($words, 1))) ?: null,
                    ]);
                }
            }, 10);
            self::$converter = new MarkdownConverter($environment);
        }

        return self::$converter;
    }
}
