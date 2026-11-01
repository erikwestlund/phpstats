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

PHPstats is influenced heavily by the [tidyverse](https://www.tidyverse.org)
and the tidy approach to statistical computing in R, and also by R's
statistical packages, Polars, and [Stan](https://mc-stan.org).

- <x-package-link package="phibble" class="font-semibold" />: data frames modeled on <a href="https://tibble.tidyverse.org" class="font-mono">tibble</a>, file reading modeled on <a href="https://readr.tidyverse.org" class="font-mono">readr</a>, and the factor functions of <a href="https://forcats.tidyverse.org" class="font-mono">forcats</a>.
- <x-package-link package="phplyr" class="font-semibold" />: the data manipulation features of <a href="https://dplyr.tidyverse.org" class="font-mono">dplyr</a>, <a href="https://tidyr.tidyverse.org" class="font-mono">tidyr</a>, and <a href="https://sfirke.github.io/janitor/" class="font-mono">janitor</a>.
- <x-package-link package="juphyter" class="font-semibold" />: a PHP kernel for <a href="https://jupyter.org">Jupyter</a>, modeled on <a href="https://github.com/IRkernel/IRkernel" class="font-mono">IRkernel</a>, that also runs PHP in <a href="https://quarto.org">Quarto</a> documents.
- <x-package-link package="phpolars" class="font-semibold" />: the <a href="https://pola.rs">Polars</a> library, wrapped in a PHP extension. Its API follows <a href="https://docs.pola.rs/api/python/stable/reference/">Polars' Python library</a>.
- <x-package-link package="phit" class="font-semibold" />: its own Rust implementation of regression, mixed models, and distributions, drawing on R's <a href="https://stat.ethz.ch/R-manual/R-devel/library/stats/html/00Index.html" class="font-mono">stats</a>, <a href="https://github.com/lme4/lme4" class="font-mono">lme4</a>, and <a href="https://www.statsmodels.org" class="font-mono">statsmodels</a>. Models are written in R's formula syntax.
- <x-package-link package="phiz" class="font-semibold" />: the grammar of graphics of <a href="https://ggplot2.tidyverse.org" class="font-mono">ggplot2</a>.
- <x-package-link package="cmdstanphp" class="font-semibold" />: a port of <a href="https://mc-stan.org/cmdstanr/" class="font-mono">CmdStanR</a>.

## Validation

The packages include comparisons against established R and Python libraries.
Each package documents its checks:

<x-verification-links />

## Source and license

The source of every package and of this site is in the
[phpstats organization on GitHub](https://github.com/phpstats). All of it is
released under the MIT license. Each repository's LICENSE file names its
copyright holder.
