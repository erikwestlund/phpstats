# Docs review, second pass (2026-10-31)

A reader reviewed the rendered site after the first cleanup. This pass is
about two things: a reader starting from a fresh project can run every
introductory example, and the summaries agree with the detailed pages.

## Rules for every package

- Every introductory example (docs index, getting started, quickstart) either
  includes its complete setup or links to it under a clear label. Complete
  setup means:
  - every `use` import the code needs, including functions such as `len()` and
    `scan_csv()` that later examples use;
  - where to get each dataset, as a download link or a command, or code that
    creates the data inline;
  - a complete starter script that runs from a fresh Composer project.
  
  Output that renders correctly can hide setup the reader never sees, so run
  each starter script in an empty directory to check it.
- A summary must not claim more than the detailed page it summarizes. Carry
  each qualification into the overview, the index, and site.json.
- Link text names the page by its title, never by a file name such as
  `missing-data.qmd`.
- Round numbers in displayed summaries consistently, to the precision the
  reader needs.

## site.json changes

- `installation`: new key. Give the docs path of the page that holds the full
  installation guide, with an optional fragment (`getting-started#installation`).
  Get started now shows only the install command, the requirements, and the
  names of the optional features. Each optional feature's detail (which
  functions it enables, version minimums) belongs on that page.
- `optional[].for`: keep it short. Get started no longer shows it, but the
  package's own pages may.
- `platforms`: the supported operating systems, which Erik set as Linux and
  macOS for every package. The site labels them "Supported platforms". Move
  "NTS (non-thread-safe) PHP" into `requires`.
- `verify.output`: the site now says "prints the installed version, for
  example 0.3.1" when the output is a version number. Keep a real version
  there.
