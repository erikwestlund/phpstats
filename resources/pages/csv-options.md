---
title: CSV options
lead: The same reader options carry readr's names in phibble and phit, and Polars' names in phpolars. This page maps them onto each other.
description: A table mapping the readr-named CSV options of phibble and phit to the Polars-named options of phpolars, including the options that exist on only one side.
---

phibble, phit, and phpolars each read a CSV file, and the three name the reader's options three ways. phibble's `DataFrame::fromCsv()` is a port of readr's `read_delim()` and takes readr's option names. `Phit\read_csv()` is the native reader that phibble calls when the extension is loaded, and it takes the same names in readr's own spelling. `Phpolars\read_csv()` wraps Polars' reader and takes py-polars' names.

A reader who learns `fromCsv(nMax: 5)` in phibble needs `read_csv(nRows: 5)` in phpolars. The table below covers every option of both readers.

## Why the names differ

Convention 3 of the family's API conventions keeps free functions snake_case and members camelCase. It then exempts a package that ports a library faithfully, so that package keeps its source's names. phpolars holds one of those exemptions because it ports Polars. Renaming its options toward readr would put a Polars port's parameter names at odds with Polars' own documentation, which is what the exemption prevents. The conventions are in the family repository, at `docs/api-conventions.md`.

## phibble and phit spell the readr options differently

phibble's reader takes PHP parameters, so its names are camelCase: `colNames`, `nMax`, and `trimWs`. `Phit\read_csv()` takes an array of options, and its keys are readr's snake_case names: `col_names`, `n_max`, and `trim_ws`. `Phibble\Io\NativeCsv` translates between the two before it calls the extension. Each spelling names the same readr option, so the table gives each its own column.

`Phit\read_csv()` accepts fifteen of these options. phibble handles column selection, name repair, factor columns, and the readr type letters that the extension has no type code for, applying them in PHP to the result the extension returns.

## The options

| What it controls | <span class="font-mono">phibble</span> | <span class="font-mono">phit</span> | <span class="font-mono">phpolars</span> |
|---|---|---|---|
| The field separator | `delimiter` | `delimiter` | `separator` |
| Whether the first row holds the column names | `colNames` as a boolean | `col_names` | `hasHeader` |
| Column names supplied rather than read | `colNames` as a list | absent | `newColumns` |
| The strings that read as missing | `na` | `na` | `nullValues` |
| The type of every column | `types` | `types` | `schema` |
| The type of some columns, the rest guessed | `types` as a map | `types` | `schemaOverrides` |
| The columns to keep, in order | `select` | absent | `columns` |
| Rows skipped before the header | `skip` | `skip` | `skipRows` |
| Rows skipped after the header | absent | absent | `skipRowsAfterHeader` |
| The most rows to read | `nMax` | `n_max` | `nRows` |
| Rows read to guess the types | `guessMax` | `guess_max` | `inferSchemaLength` |
| The quote character | `quote` | `quote` | `quoteChar` |
| A doubled quote stands for one quote | `escapeDouble` | `escape_double` | absent |
| A backslash escapes the next character | `escapeBackslash` | `escape_backslash` | absent |
| The prefix that marks a comment line | `comment` | `comment` | `commentPrefix` |
| Whitespace trimmed from each field | `trimWs` | `trim_ws` | absent |
| Empty rows dropped | `skipEmptyRows` | `skip_empty_rows` | absent |
| The decimal mark | `decimalMark` | `decimal_mark` | `decimalComma` |
| The digit grouping mark | `groupingMark` | `grouping_mark` | absent |
| How duplicate and empty column names are repaired | `nameRepair` | absent | absent |
| The line ending | absent | absent | `eolChar` |
| Dates and datetimes parsed while reading | absent | absent | `tryParseDates` |
| The input encoding | absent | absent | `encoding` |
| Parse errors tolerated rather than raised | absent | absent | `ignoreErrors` |
| A missing text field read as an empty string | absent | absent | `missingUtf8IsEmptyString` |
| Rows with too many fields truncated | absent | absent | `truncateRaggedLines` |
| A row number column, by name and first value | absent | absent | `rowIndexName`, `rowIndexOffset` |
| An empty file raises rather than returning nothing | absent | absent | `raiseIfEmpty` |
| The source path expanded as a glob | absent | absent | `glob` |

An option marked absent has no counterpart in that reader.

## Where a match is partial

- `types` covers both of the Polars options next to it. It takes readr's compact letter string, a list of specifications by position, or a map by column name with `.default` for the rest. phpolars splits the two cases: `schema` names a Polars data type for every column, and `schemaOverrides` names one for some columns and leaves the rest to type inference.
- `colNames` takes `true`, `false`, or a list of names. `Phit\read_csv()` reads `col_names` as a boolean only, so phibble supplies a list itself. phpolars splits the two cases into `hasHeader` and `newColumns`.
- `decimalMark` and `decimal_mark` take the character. `decimalComma` is a boolean that selects a comma, and Polars has no option for the grouping mark.
- `columns` is a parameter of `Phpolars\read_csv()` rather than one of Polars' reader options, and it selects from the data frame the reader returns. phibble's `select` keeps the named columns in the order given.
- phibble guesses dates and datetimes as part of guessing a column's type, so it has no option to turn that on. It collects parse failures and reports them as problems rather than offering a flag to ignore them.
- `encoding` has no counterpart because neither readr-named reader inspects the encoding. Both pass bytes through unchanged and validate nothing, so the bytes of a file in any encoding reach the column values as written. Both strip a UTF-8 byte-order mark from the start of the file and leave every other byte alone.
