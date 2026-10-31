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

PHPstats is modeled primarily on the tidyverse, with additional influences
from R's statistical packages, Polars, and Stan.

- <strong class="font-mono">phibble:</strong> tibble, readr, and forcats.
- <strong class="font-mono">phplyr:</strong> dplyr, tidyr, and janitor.
- <strong class="font-mono">phiz:</strong> ggplot2.
- <strong class="font-mono">phit:</strong> R's stats and lme4.
- <strong class="font-mono">phpolars:</strong> Polars and its Python API.
- <strong class="font-mono">CmdStanPHP:</strong> a port of CmdStanR.
- <strong class="font-mono">juphyter:</strong> IRkernel and Quarto's notebook workflows.

## Source and license

The source of every package and of this site is in the
[phpstats organization on GitHub](https://github.com/phpstats). All of it is
released under the MIT license. Each repository's LICENSE file names its
copyright holder.
