---
title: Get started
lead: Install the packages you need, then read a table out of your application database and work with it in PHP.
description: How to install the PHPstats packages and run a first pipeline, with the PHP version to use and the install lines each package publishes.
---

## PHP version

Use PHP 8.5. The examples on this site write pipelines with the pipe operator
`|>`, which PHP added in 8.5, and that is the form the documentation leads
with.

The packages themselves support PHP 8.3 and later. On 8.3 and 8.4 the same
pipeline is written as a method chain, and every example on this site shows
both forms. Each package lists its own requirements below.

## Install

Nothing in the PHPstats family is published on Packagist yet, so each install
reads the package from its Git repository. The lines below are the ones each
package publishes for itself.

A line beginning with `composer config` registers a package's repository in
the project you are installing into, and the `composer require` line after it
resolves the package from there. Its `dev-main` constraint follows that
repository's main branch, which is where the current work is until the
packages are tagged. Both assume a project that already has a `composer.json`,
so run `composer init` first when you are starting from an empty directory.
While Composer reads a VCS repository it calls the GitHub API, and it may ask
you for a token once anonymous requests reach their rate limit.

A block that clones a repository and runs `cargo build` belongs to an
extension written in Rust, which has no package to install. The
`php -d extension=...` line loads the library you have just built for a
single run. To load it for every run, put the same `extension=` line, with
the path to that library, in the `php.ini` that `php --ini` reports.

<x-install-steps />

## A first pipeline

The example below reads an `orders` table through Eloquent into a phibble
DataFrame, then summarizes revenue by channel with phplyr. Pick the tab for
the PHP version you are on. The result underneath is computed when this page
is served.

<x-example name="orders/transform" requires="phplyr" />

The pipeline reads the table once. `filter()`, `group_by()`, and
`summarize()` each take a DataFrame and return a new one, so the table the
pipeline started from is left unchanged.

## Where to go next

Each package has its own pages, with documentation, benchmarks, and the
results of checking it against a reference implementation. The
[vignettes](/vignettes) are longer worked examples that run on this server,
and [About PHPstats](/about) describes how the packages fit together.
