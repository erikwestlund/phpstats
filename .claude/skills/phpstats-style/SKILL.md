---
name: phpstats-style
description: House style for the phpstats.org site and the PHPstats packages' site content. Use when writing or changing site pages, Blade views, landing page examples, package taglines, or any text that names a PHPstats package.
---

# PHPstats site style

These rules apply to the site's views, its Markdown pages, and the content
that packages sync into it.

## Package names

- Write each name exactly: phibble, phpolars, phplyr, phiz, phit, and
  CmdStanPHP. Every name is lowercase except CmdStanPHP, which follows
  CmdStanR and CmdStanPy. Keys, URLs, and Composer names stay lowercase.
- Set package names in the monospace font wherever they stand as labels:
  headings, menus, lists, the package bar, the footer, links between
  packages, and package chips. In Blade, add `font-mono` to the element that
  holds the name. Running prose mentions stay in the text font.
- phit-polyfill is part of phit. Mention it only on phit's pages, and never
  list it as a separate package.

## Categories

The packages are grouped into three categories, in this order, each with
its own menu:

- Data Frames: phibble and phpolars.
- Processing & Visualization: phplyr and phiz.
- Statistical Modeling: phit and CmdStanPHP.

## Copy

- Keep package descriptions short and plain, such as "Immutable, typed data
  frames from CSV files, PDO, Eloquent, or Doctrine." Avoid label-colon
  phrasing such as "The shared data frame: …".
- Leave out status badges, version pills, colored dots, and stats bars.
- Keep "checked against R" and verification claims off the landing page and
  the About page. Verification belongs on each package's Verification
  section.
- Don't use "Verbs" as a heading, and don't describe the project as
  "Inspired by the Tidyverse".
- The about page is "About PHPstats".

## Code examples

- Default to the pipe operator `|>` (PHP 8.5). The method chain is the
  second tab. For phplyr, say that pipelines use PHP 8.5's pipe operator.
- Use realistic application data, such as the `orders` table, instead of
  textbook datasets.
- Don't put comments in example code.
- Write `\Phibble\DataFrame` and `\Phiz\Plot` fully qualified in the code.
  Import models and phplyr functions with `use`.
- Show every landing page example as its code and then its result, computed
  when the page renders. Add examples under `resources/examples` and show
  them with `<x-example>`.

## Color and dark mode

- Use the PHP purples, the purple-leaning zinc grays, and the layer colors
  from `config/phpstats.php`.
- Every view must work in light and dark mode. Dark mode is the `.dark`
  class on `<html>`, set by the theme toggle or the system setting, so use
  `dark:` variants and never `@media (prefers-color-scheme)`.
