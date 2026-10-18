<?php

namespace App\Content;

use App\Content\Highlight\RLanguage;
use Tempest\Highlight\Highlighter;

/** Server-side syntax highlighting for code shown on the site. */
class Code
{
    private static ?Highlighter $highlighter = null;

    /** Highlighted HTML for $code, with hl-* classes styled in app.css. */
    public static function highlight(string $code, string $language = 'php'): string
    {
        $highlighter = self::$highlighter ??= (new Highlighter)->addLanguage(new RLanguage);

        return $highlighter->parse(rtrim($code), $language === '' ? 'txt' : $language);
    }
}
