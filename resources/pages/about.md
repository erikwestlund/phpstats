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

- <span class="font-mono font-semibold">phibble:</span> data frames modeled on tibble, file reading modeled on readr, and the factor functions of forcats.
- <span class="font-mono font-semibold">phplyr:</span> the data manipulation features of dplyr, tidyr, and janitor.
- <span class="font-mono font-semibold">phiz:</span> the grammar of graphics of ggplot2.
- <span class="font-mono font-semibold">phit:</span> its own Rust implementation of regression, mixed models, and distributions, drawing on R's stats, lme4, and statsmodels. Models are written in R's formula syntax.
- <span class="font-mono font-semibold">phpolars:</span> the Polars library, wrapped in a PHP extension. Its API follows Polars' Python library.
- <span class="font-mono font-semibold">CmdStanPHP:</span> a port of CmdStanR.
- <span class="font-mono font-semibold">juphyter:</span> a PHP kernel for Jupyter, modeled on IRkernel, that also runs PHP in Quarto documents.

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
