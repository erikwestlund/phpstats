---
title: About PHPstats
description: PHPstats is a set of open-source PHP packages for data frames, data transformation, statistical models, Bayesian inference, charts, and notebooks.
---

PHPstats is a set of open-source PHP packages for data analysis: data frames,
data transformation, statistical models, Bayesian inference, charts, and
notebooks. Each package can be installed and used on its own. The packages
that work with tables accept phibble's data frame, so they can also be
combined in one analysis.

## Packages

<x-package-table />

## Design

The packages borrow conventions from R's data-analysis and statistics
packages, from Polars, and from Stan, so that people who know those tools can
read and write PHPstats code. phibble's data frame and its readers follow
tibble and readr. phplyr keeps the function names and arguments of dplyr and
tidyr. phiz builds charts with ggplot2's grammar of graphics. phit specifies
models with R's formula syntax. phpolars follows the API of Polars' Python
library and runs queries on the Polars engine. CmdStanPHP's API is patterned
after CmdStanR, and it fits models with CmdStan.

These projects are influences. The packages are not drop-in replacements for
them, and each package's documentation notes where its behavior differs.

## Source and license

The source of every package and of this site is in the
[phpstats organization on GitHub](https://github.com/phpstats). All of it is
released under the MIT license. Each repository's LICENSE file names its
copyright holder.
