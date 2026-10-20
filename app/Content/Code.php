<?php

namespace App\Content;

use App\Content\Highlight\RLanguage;
use Tempest\Highlight\Highlighter;

/** Server-side syntax highlighting for code shown on the site. */
class Code
{
    private static ?Highlighter $highlighter = null;

    /**
     * Highlighted HTML for $code, with hl-* classes styled in app.css.
     * The language "quarto" highlights a .qmd document in parts; see quarto().
     */
    public static function highlight(string $code, string $language = 'php'): string
    {
        if ($language === 'quarto') {
            return self::quarto($code);
        }

        $highlighter = self::$highlighter ??= (new Highlighter)->addLanguage(new RLanguage);

        return $highlighter->parse(rtrim($code), $language === '' ? 'txt' : $language);
    }

    /**
     * A Quarto document: its YAML front matter and the PHP in each {php}
     * cell, each highlighted as itself, with the delimiters and the prose
     * between them left plain. Highlighting a .qmd as markdown instead
     * leaves a cell's PHP uncolored and marks the closing fences as comments,
     * because the document's own language says nothing about what a cell
     * holds.
     */
    private static function quarto(string $code): string
    {
        $code = rtrim($code);
        $html = '';
        $offset = 0;

        if (preg_match('/\A---\n(.*?\n)---\n/s', $code, $matches)) {
            // The yaml pass trims, so the newline before the closing --- is put back.
            $html = "---\n".self::highlight($matches[1], 'yaml')."\n---\n";
            $offset = strlen($matches[0]);
        }

        // A cell opens with ```{php}, with any cell options on its own lines
        // inside, and closes with ``` at the start of a line.
        while (preg_match('/^```\{php\}\n(.*?)^```$/ms', $code, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $matches[0][1];
            $html .= e(substr($code, $offset, $start - $offset));
            $html .= "```{php}\n".self::highlight($matches[1][0], 'php')."\n```";
            $offset = $start + strlen($matches[0][0]);
        }

        return $html.e(substr($code, $offset));
    }
}
