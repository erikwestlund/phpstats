<?php

namespace App\Content;

use App\Content\Highlight\RLanguage;
use Tempest\Highlight\Highlighter;

/** Server-side syntax highlighting for code shown on the site. */
class Code
{
    private static ?Highlighter $highlighter = null;

    /**
     * Highlighted HTML split into one block per source line, for code shown in
     * a narrow column. A line too long for the column wraps with a hanging
     * indent: its continuation lines up two columns past the line's own
     * indentation, rather than at the left edge. Tags open across a line
     * break, as in a multi-line comment, are closed at the end of the line
     * and reopened on the next.
     */
    public static function hanging(string $html): string
    {
        $out = '';
        $open = [];
        foreach (explode("\n", $html) as $line) {
            $text = html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5);
            $indent = strlen($text) - strlen(ltrim($text, ' '));
            $body = implode('', $open).$line;
            // Track the tags still open at the end of this line.
            preg_match_all('#<(/?)span\b[^>]*>#', $line, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if ($tag[1] === '/') {
                    array_pop($open);
                } else {
                    $open[] = $tag[0];
                }
            }
            $body .= str_repeat('</span>', count($open));
            $hang = $indent + 2;
            $out .= '<span class="code-line" style="padding-left: '.$hang.'ch; text-indent: -'.$hang.'ch">'.($body === '' ? ' ' : $body).'</span>';
        }

        return $out;
    }

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
