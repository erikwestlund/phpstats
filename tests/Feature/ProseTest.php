<?php

namespace Tests\Feature;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * House style rules that are cheap to check across the site's own text: the
 * strings in config/phpstats.php and config/projects.php, which reach the
 * landing page and the package pages, the Markdown pages in
 * resources/pages, and the prose in the vignette views.
 *
 * Since task 27 the packages' synced docs under resources/content are
 * scanned too, because the site publishes that prose under its own name.
 * Those pages are generated, so a hit cannot be fixed here: the package's
 * .qmd has to change and the pin then has to move. That is the cost of
 * covering them, and it is also the point -- a package cannot quietly
 * reintroduce the bare noun on this site.
 *
 * Only the rendered prose is read. Script, style, pre and code elements go
 * first, with their contents, so nothing in an example, in printed output or
 * in an identifier is judged as prose. site.json, the benchmark and
 * verification JSON, and the vignette scripts are left alone.
 */
class ProseTest extends TestCase
{
    /**
     * The sentences in the synced docs where a bare "frame" is the right
     * word, quoted in full and kept with the file they belong to, so an
     * allowance covers one sentence rather than every hit on that page.
     *
     * R's model frame is R's own name for what model.frame() builds, and
     * phit's formulas page says where its factor levels come from by naming
     * it. "data frame" there would name something else.
     *
     * A stale entry fails the test rather than lingering, so the list can
     * only shrink by being noticed.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'phit/docs/formulas.html' => ["as in R\u{2019}s model frame."],
    ];

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

        foreach ([...$this->pages(), ...$this->vignetteViews()] as $file => $source) {
            foreach ($this->bareFrames($source) as $context) {
                $offenders[] = "{$file}: {$context}";
            }
        }

        foreach ($this->syncedDocs() as $file => $text) {
            foreach (self::ALLOWED[$file] ?? [] as $sentence) {
                $this->assertStringContainsString($sentence, $text,
                    "{$file} no longer writes \"{$sentence}\", so drop that entry from ProseTest::ALLOWED.");
                $text = str_replace($sentence, '', $text);
            }
            foreach ($this->bareFrames($text) as $context) {
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
            // A Blade comment is a note to the next reader, not site prose.
            "{{-- The printed frame sits below. --}}\nIt runs on the server.",
            // A view's code slots hold code, whatever it names. The views
            // close a slot with its own name, so that form has to be the
            // case that matters; the bare close is tolerated as well.
            "<x-code-tabs>\n<x-slot:chain>\$rows |> frame(\$rows);\n</x-slot:chain>\n</x-code-tabs>",
            "<x-slot:pipe>frame(\$rows)\n</x-slot:pipe>",
            "<x-slot:chain>read_csv(\$path)->frame()\n</x-slot>",
        ] as $prose) {
            $this->assertSame([], $this->bareFrames($prose), $prose);
        }
    }

    /**
     * Each place this text uses "frame" or "frames" as a noun of its own,
     * with a little context. Fenced blocks, inline code, Blade comments, the
     * code in a view's pipe and chain slots, and HTML tags are dropped
     * first, because code and the notes about it name their own things, and
     * a name joined by a word character, "-", "_", "$", "\", or "/" is an
     * identifier rather than the noun.
     *
     * A slot closes with either </x-slot:pipe> or the bare </x-slot>, and
     * every view in this repository writes the first form, so the pattern
     * has to accept both. Matching only the bare close reads a slot's code
     * as prose, and a vignette that calls a function named frame() then
     * fails this test for writing code.
     *
     * @return list<string>
     */
    private function bareFrames(string $text): array
    {
        $text = preg_replace('/^(```|~~~).*?^(```|~~~)[ \t]*$/ms', '', $text);
        $text = preg_replace('/`[^`]*`/', '', $text);
        $text = preg_replace('/\{\{--.*?--\}\}/s', '', $text);
        $text = preg_replace('#<x-slot:(pipe|chain)>.*?</x-slot(?::\1)?>#s', '', $text);
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

    /**
     * The prose in the vignette views, which carry their own copy rather
     * than reading it from a package.
     *
     * @return array<string, string>
     */
    private function vignetteViews(): array
    {
        $views = [];
        foreach (glob(resource_path('views/vignettes/*.blade.php')) as $file) {
            $views['resources/views/vignettes/'.basename($file)] = file_get_contents($file);
        }

        $this->assertNotEmpty($views, 'no vignette view was scanned');

        return $views;
    }

    /**
     * The rendered text of every synced docs page, keyed as
     * "{package}/docs/{path}.html".
     *
     * Script, style, pre and code elements are removed with their contents
     * before anything is read, so an example, a printed table or an
     * identifier is never judged as prose. Character entities are decoded
     * afterwards, because a phrase written with an escaped space would
     * otherwise read as the bare noun, and a decoded no-break space is
     * folded into an ordinary one for the same reason.
     *
     * @return array<string, string>
     */
    private function syncedDocs(): array
    {
        $pages = [];
        foreach (glob(resource_path('content/*/docs'), GLOB_ONLYDIR) as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'html') {
                    continue;
                }

                $html = preg_replace('#<(script|style|pre|code)\b[^>]*>.*?</\1>#is', ' ', file_get_contents($file->getPathname()));
                $text = str_replace("\u{A0}", ' ', html_entity_decode($html, ENT_QUOTES | ENT_HTML5));

                $pages[ltrim(str_replace(resource_path('content'), '', $file->getPathname()), '/')] = $text;
            }
        }

        $this->assertNotEmpty($pages, 'no synced docs page was scanned');
        ksort($pages);

        return $pages;
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
