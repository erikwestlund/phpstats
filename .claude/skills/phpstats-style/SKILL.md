---
name: phpstats-style
description: House style for the phpstats.org site and the PHPstats packages' site content. Use when writing or changing site pages, Blade views, landing page examples, package taglines, or any text that names a PHPstats package.
---

# PHPstats site style

These rules apply to the site's views, its Markdown pages, and the content
that packages sync into it.

## Package names

- Write each name exactly: phibble, phpolars, phplyr, juphyter, phiz, phit,
  and CmdStanPHP. Every name is lowercase except CmdStanPHP, which follows
  CmdStanR and CmdStanPy. Keys, URLs, and Composer names stay lowercase.
- Set package names in the monospace font wherever they stand as labels:
  headings, menus, lists, the package bar, the footer, links between
  packages, and package chips. In Blade, add `font-mono` to the element that
  holds the name. Running prose mentions stay in the text font.
- phit-polyfill is part of phit. Mention it only on phit's pages, and never
  list it as a separate package.

## Categories

The packages are grouped into two categories, in this order, each with
its own menu:

- Data & Workflow: phibble, phpolars, phplyr, and juphyter.
- Statistics, Modeling & Visualization: phit, phiz, and CmdStanPHP.

## Copy

- Keep package taglines (menus, package pages) short and plain, such as
  "Immutable, typed data frames from CSV files, PDO, Eloquent, or Doctrine."
  The landing page's package explorer lists each package with a one-line
  summary and puts the detail in three panels: "About {package}", the
  example (titled "Example: {what it does}", from `example` in `explorer`),
  and "Example output". The About panel has usually four bullets from
  `explorer` in `config/phpstats.php`, built only from documented
  capabilities. Write them for readers who know R, the tidyverse, Polars,
  ggplot2, or Stan: name the upstream project and link its first mention,
  cover the core and further capabilities, and say whether the package is
  plain PHP, a Rust extension, or a wrapper around CmdStan. Avoid
  label-colon phrasing such as "The shared data frame: …".
- Explanatory text such as the About bullets is inline Markdown, rendered
  with `App\Content\Markdown::inline()`. Put function, class, and interface
  names, operators, literals, and type names in backticks: `fit_glm()`,
  `PolarsTable`, `|>`, `null`, and `factor`. PHPstats package names become
  bold monospace links to their pages on their own; write them as plain
  words. Link upstream projects (tibble, Polars, ggplot2, Stan) as ordinary
  Markdown links.
- A Markdown page in `resources/pages` runs Blade only in self-contained
  components and echoes, never in directives, and never as a link
  destination: CommonMark percent-encodes the destination, so
  `[text]({{ route('page', 'about') }})` renders a broken href. Write the
  literal path, `/about`, which is the same on the landing host in host mode
  and in path mode. A loop belongs in a component, as `<x-install-steps />`
  holds the one over `Package::all()`.
- Never write the bare noun "frame" or "frames" in site prose. Write "data
  frame", "data frames", or "table", and `DataFrame` where the class is
  meant. The rule is about prose only: `$frame` variables, the
  `df-table` component, and anything else in code keep their names.
- Leave out status badges, version pills, colored dots, and stats bars.
- Keep "checked against R" and verification claims off the landing page and
  the About page. Verification belongs on each package's Verification
  section.
- Don't use "Verbs" as a heading, and don't describe the project as
  "Inspired by the Tidyverse".
- The about page is "About PHPstats".

## Code examples

- Default to the pipe operator `|>` (PHP 8.5). The method chain is the
  second tab.
- Put `|>` at the end of a line, never at the start of the next one:
  `$orders |>` then `    filter(...) |>` on the following lines.
- For phplyr, say that pipelines use PHP 8.5's pipe operator. The syntax
  tabs read only "PHP 8.5+" and "PHP 8.3–8.4", with no "Pipe" or "Methods".
- Use realistic application data, such as the `orders` table, instead of
  textbook datasets.
- Don't put comments in example code.
- Write `\Phibble\DataFrame` and `\Phiz\Plot` fully qualified in the code.
  Import models and phplyr functions with `use`. Imports are hidden unless
  they sit below the example's `// ---` line; show them where the example is
  about loading data, as phibble's and phpolars' are.
- Use American spellings in prose and, where an API offers both, in code:
  `summarize()` over `summarise()`. Never rename an API for spelling.
- Make each explorer example show what the package adds to an analytical
  workflow (typed columns, Polars execution, grouped calculations and
  reshaping, layered and faceted charts, model inference), not a query that
  Eloquent already does well.
- Each package has an example in `resources/examples/packages/{key}.php`
  (with `{key}.chain.php` where a method chain exists). The package explorer
  shows the selected package's code and its output as the package prints it:
  a phibble data frame, Polars' table, a chart, or an R-style model summary. Never
  call phibble output a "tibble"; the site drops phibble's "# A tibble" header
  line and shows the size in the output label. phibble is
  selected by default. Never invent output: run the example, or compute it
  from a stored fixture with the package's own code. Don't add notes about
  how the output was computed.
  Keep these examples different from the workflow examples below them.
- Show every landing page example as its code and then its result, computed
  when the page renders. Add examples under `resources/examples` and show
  them with `<x-example>`.

## Color and dark mode

- Use the PHP purples, the purple-leaning zinc grays, and the layer colors
  from `config/phpstats.php`.
- Every view must work in light and dark mode. Dark mode is the `.dark`
  class on `<html>`, set by the theme toggle or the system setting, so use
  `dark:` variants and never `@media (prefers-color-scheme)`.
