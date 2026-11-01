<?php

namespace App\Content;

/**
 * Sets the names of the R and Python libraries the landing page compares
 * PHPstats with (lm, glm, lme4, statsmodels, SciPy, and so on) in the
 * monospace font, as package names are. The languages R and Python stay in
 * the text font, and text inside <code> is left as it is.
 */
class Libraries
{
    private const NAMES = ['statsmodels', 'GPBoost', 'pandas', 'readr', 'dplyr', 'tibble', 'lme4', 'SciPy', 'glm', 'lm', 'mpmath', 'phit-polyfill', 'phit', 'CmdStanPHP', 'CmdStanR', 'CmdStanPy', 'phpolars', 'phibble', 'phplyr', 'phiz'];

    /** HTML with each library name outside <code> wrapped in a font-mono span. */
    public static function mono(string $html): string
    {
        $names = implode('|', array_map(fn (string $n) => preg_quote($n, '/'), self::NAMES));
        $parts = preg_split('/(<code\b.*?<\/code>|<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        return implode('', array_map(
            fn (string $part) => str_starts_with($part, '<')
                ? $part
                : preg_replace('/(?<![\w-])('.$names.')(?![\w-])/', '<span class="font-mono">$1</span>', $part),
            $parts,
        ));
    }
}
