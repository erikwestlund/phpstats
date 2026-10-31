---
title: About PHPstats
lead: PHPstats is a family of PHP packages for data frames, data pipelines, statistical models, and charts.
description: PHPstats is a family of open-source PHP packages for data frames, pipelines, statistical models, and charts. Every package is on Packagist.
---

## What PHPstats is

PHPstats is a set of packages for statistical work in PHP. A table can come
from a CSV file or from an application database through PDO, Eloquent, or
Doctrine. The packages transform it, fit models to it, and chart it, so an
analysis can run inside a PHP application instead of in a separate R or Python
process.

## The packages

The packages fall into two groups: data and workflow, and statistics,
modeling and visualization. They share phibble's data frame, so a table read
by phibble can be transformed by phplyr, modeled by phit, and charted by phiz.
Each package can also be used on its own.

<x-family-table />

## Installing

Every package is published on Packagist. The PHP libraries install with
Composer. phit and phpolars are PHP extensions written in Rust and install
with [PIE](https://github.com/php/pie). [Installation](/installation) lists the
install command and requirements for each package.

## Design

The packages follow R's conventions where R has one. phplyr keeps the
function names and arguments of dplyr and tidyr, and phiz follows the grammar
of graphics in ggplot2. phit fits models from R formulas such as
`log(rent) ~ log(area) + district`, and CmdStanPHP builds Stan data from the
same formulas.

Pipelines on this site use the pipe operator `|>`. It requires PHP 8.5. On PHP
8.3 and 8.4, the same functions are called as a method chain.

phibble, phplyr, phiz, and juphyter are plain PHP. phit fits its models in
Rust, phpolars runs tables on the Polars engine, and CmdStanPHP runs Stan
programs through CmdStan.

## Dashboards with Livewire

In a Laravel application, a Livewire component can run a PHPstats pipeline.
The component's properties are the inputs. When one changes, the server reruns
the pipeline, refits the model, and redraws the charts. The page needs no R or
Python server and no hand-written JavaScript. Every example on this site is
built this way.

## License

The packages and this site are released under the MIT license. Their source is
on GitHub at [github.com/phpstats](https://github.com/phpstats).
