<?php

namespace App\Data;

use Illuminate\Support\HtmlString;

/**
 * The rendered cell of resources/examples/packages/juphyter.qmd, as the
 * juphyter kernel drew it. A page request must not start a kernel or run
 * Quarto, so the cell's output is captured once and committed, the way the
 * Stan draws for the Bayesian vignette are.
 *
 * Captured on 2026-10-20 from juphyter at 2af1591 with Quarto 1.10.18 and
 * PHP 8.5.10. The kernel went into a throwaway virtual environment, so
 * nothing outside it was installed:
 *
 *   python3 -m venv venv && venv/bin/pip install jupyter ipykernel
 *   php85 bin/juphyter install --name php --python venv/bin/python \
 *     --php "$(command -v php85)" --prefix venv
 *   QUARTO_PYTHON=venv/bin/python JUPYTER_PATH=venv/share/jupyter \
 *     quarto render resources/examples/packages/juphyter.qmd --to html
 *
 * The rendered page's <div class="cell-output"> is this file, verbatim.
 * Render it again the same way whenever the example document changes, and
 * never edit the fixture by hand: the panel's promise is that the output is
 * what the kernel produced from the code shown above it.
 */
final class JuphyterCell
{
    private const FIXTURE = 'data/juphyter/revenue-by-channel.html';

    /**
     * The cell for the package explorer, with the table's size taken out of
     * the markup so the output label can carry it.
     *
     * @return array{html: HtmlString, size: ?string}
     */
    public static function explorerOutput(): array
    {
        $html = trim(file_get_contents(database_path(self::FIXTURE)));
        $size = null;

        // phibble heads a table with its size, which the kernel draws as a
        // note above the table. The site shows that size in the output label
        // and never calls the output a tibble.
        if (preg_match('#<div class="juphyter-note">A tibble: ([^<]+)</div>\s*#u', $html, $matches)) {
            $size = trim($matches[1]);
            $html = str_replace($matches[0], '', $html);
        }

        return ['html' => new HtmlString(trim($html)), 'size' => $size];
    }
}
