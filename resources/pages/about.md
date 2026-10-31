---
title: About PHPstats
description: PHPstats is a set of open-source PHP packages for data frames, data transformation, statistical models, Bayesian inference, charts, and notebooks.
---

PHPstats is a set of open-source PHP packages for data analysis: data frames,
data transformation, statistical models, Bayesian inference, charts, and
notebooks. Each package can be installed and used on its own. They are
designed to work well together.

## Packages

<x-package-table />

## Design

PHPstats is influenced heavily by the tidyverse and the tidy approach to
statistical computing in R, and also by R's statistical packages, Polars, and
Stan.

- <span class="font-mono font-semibold">phibble</span> implements data frames modeled on tibble, file reading modeled on readr, and the factor functions of forcats.
- <span class="font-mono font-semibold">phplyr</span> implements the data manipulation features of dplyr, tidyr, and janitor.
- <span class="font-mono font-semibold">phiz</span> implements the grammar of graphics of ggplot2.
- <span class="font-mono font-semibold">phit</span> implements regression models from R's stats package and mixed models from lme4.
- <span class="font-mono font-semibold">phpolars</span> brings Polars to PHP, with an API that follows Polars' Python library.
- <span class="font-mono font-semibold">CmdStanPHP</span> is a port of CmdStanR.
- <span class="font-mono font-semibold">juphyter</span> is a PHP kernel for Jupyter, modeled on IRkernel, that also runs PHP in Quarto documents.

## Validation

All mathematical and statistical functions are verified against trusted
statistics and modeling libraries in R and Python. Each package documents its
checks:

<x-verification-links />

## Source and license

The source of every package and of this site is in the
[phpstats organization on GitHub](https://github.com/phpstats). All of it is
released under the MIT license. Each repository's LICENSE file names its
copyright holder.
