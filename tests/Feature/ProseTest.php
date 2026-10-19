<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * House style rules that are cheap to check across the site's own text: the
 * strings in config/phpstats.php and config/projects.php, which reach the
 * landing page and the package pages, and the Markdown pages in
 * resources/pages.
 */
class ProseTest extends TestCase
{
    public function test_no_prose_writes_the_bare_noun_frame(): void
    {
        $offenders = [];

        foreach (['phpstats', 'projects'] as $file) {
            foreach ($this->strings(config($file), "config/{$file}.php") as $where => $string) {
                foreach ($this->bareFrames($string) as $context) {
                    $offenders[] = "{$where}: {$context}";
                }
            }
        }

        foreach ($this->pages() as $file => $source) {
            foreach ($this->bareFrames($source) as $context) {
                $offenders[] = "{$file}: {$context}";
            }
        }

        $this->assertSame([], $offenders, 'Write "data frame", "data frames", or "table", never the bare noun.');
    }

    public function test_the_scan_finds_the_bare_noun_and_leaves_the_allowed_forms_alone(): void
    {
        foreach ([
            'A frame is returned.',
            'It takes phibble frames.',
            'Frames load from CSV files.',
            'Every operation returns a new frame',
            // Split over a line break, as wrapped copy is.
            "The same pipeline runs on a phibble\nframe, or in Polars.",
        ] as $prose) {
            $this->assertNotSame([], $this->bareFrames($prose), $prose);
        }

        foreach ([
            'A data frame is returned.',
            'It takes phibble data frames.',
            'Data frames load from CSV files.',
            'Each dataFrame is immutable.',
            'A data-frame column is typed.',
            // Wrapped copy, where "data" ends the line.
            "It takes phibble data\nframes and formulas.",
            // Code keeps its own names, inside backticks and outside them.
            'Operations on `$frame` return a new one.',
            'The `frame-table` component prints it.',
            'Write \Phibble\DataFrame fully qualified.',
            'The frame-table component prints it.',
            'Laravel is a framework.',
            "Before.\n```php\n\$frame = new DataFrame;\n```\nAfter.",
        ] as $prose) {
            $this->assertSame([], $this->bareFrames($prose), $prose);
        }
    }

    /**
     * Each place this text uses "frame" or "frames" as a noun of its own,
     * with a little context. Fenced blocks, inline code, and HTML tags are
     * dropped first, because code names its own things, and a name joined by
     * a word character, "-", "_", "$", "\", or "/" is an identifier rather
     * than the noun.
     *
     * @return list<string>
     */
    private function bareFrames(string $text): array
    {
        $text = preg_replace('/^(```|~~~).*?^(```|~~~)[ \t]*$/ms', '', $text);
        $text = preg_replace('/`[^`]*`/', '', $text);
        $text = preg_replace('/<[^>]*>/', '', $text);

        // Collapsed, so copy wrapped between "data" and "frames" still reads
        // as one phrase.
        $text = preg_replace('/\s+/', ' ', $text);

        preg_match_all(
            '/(?<![\w$\\\\\\/-])(?<!data )frames?(?![\w$\\\\\\/-])/i',
            $text,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        return array_map(
            fn (array $match) => trim(mb_strcut($text, max(0, $match[1] - 30), 70)),
            $matches[0],
        );
    }

    /**
     * Every string in a config array, keyed by where it came from.
     *
     * @return array<string, string>
     */
    private function strings(mixed $value, string $path): array
    {
        if (is_string($value)) {
            return [$path => $value];
        }

        if (! is_array($value)) {
            return [];
        }

        $strings = [];
        foreach ($value as $key => $item) {
            $strings = [...$strings, ...$this->strings($item, "{$path} {$key}")];
        }

        return $strings;
    }

    /** @return array<string, string> */
    private function pages(): array
    {
        $pages = [];
        foreach (glob(resource_path('pages/{*.md,*/*.md}'), GLOB_BRACE) as $file) {
            $pages['resources/pages/'.ltrim(str_replace(resource_path('pages'), '', $file), '/')] = file_get_contents($file);
        }

        $this->assertNotEmpty($pages, 'no Markdown page was scanned');

        return $pages;
    }
}
