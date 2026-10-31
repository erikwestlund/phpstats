<?php

// The phpstats family. Keys are the URL paths (phpstats.org/{key}).
// Status kinds: working, exists, in-progress, experimental.
// docs is null until the project publishes a reference site.
// links (optional) adds more documentation links, as label => URL.
// from_r names the R packages it corresponds to, or null.

return [
    'phit' => [
        'name' => 'phit',
        'from_r' => 'stats and lme4',
        'summary' => 'Regression, mixed models, distributions, and descriptive statistics, in a Rust extension.',
        'status' => 'working',
        'status_label' => 'Working',
        'repo' => 'https://github.com/phpstats/phit',
        'docs' => 'https://phpstats.github.io/phit/',
        'overview' => [
            'phit fits statistical models inside PHP and returns plain PHP arrays. It provides OLS, logistic, probit, and Poisson regression, linear mixed models, and mixed logistic regression.',
            'It also provides probability distributions, descriptive statistics, posterior-draw kernels, and a seeded random number generator. Results are verified against R.',
            'Every function is in the Phit namespace, such as Phit\fit_lm(), Phit\predict(), and Phit\dist_normal_cdf(). The functions were named stats_* when the extension was called php-stats.',
            'The extension targets PHP 8.3 to 8.5 on Linux and macOS and installs with PIE as phit.',
        ],
    ],
    'phit-polyfill' => [
        'name' => 'phit-polyfill',
        'from_r' => null,
        'summary' => 'The phit functions in plain PHP, for hosts that cannot install the extension.',
        'status' => 'working',
        'status_label' => 'Working',
        'repo' => 'https://github.com/phpstats/phit-polyfill',
        'docs' => null,
        'overview' => [
            'phit-polyfill implements the phit extension\'s functions in plain PHP, in the Phit namespace with the same names, parameters, and results, except the CSV reader, which needs the extension. It is for hosts that cannot install extensions.',
            'It defines its functions only when the extension is not loaded, so the same code runs with or without the extension.',
            'In the recorded benchmarks, regression fits take 9 to 23 times as long as with the extension, and mixed models 13 to 78 times as long. The polyfill needs OPcache\'s JIT disabled.',
        ],
    ],
    'phibble' => [
        'name' => 'phibble',
        'from_r' => 'tibble and readr',
        'summary' => 'Immutable, typed data frames from CSV files, PDO, Eloquent, or Doctrine.',
        'status' => 'working',
        'status_label' => 'Released, v0.3.0',
        'repo' => 'https://github.com/phpstats/phibble',
        'docs' => 'https://phpstats.github.io/phibble/',
        'links' => [
            'Laravel integration' => 'https://phpstats.github.io/phibble/laravel.html',
            'Release notes' => 'https://github.com/phpstats/phibble/releases/tag/v0.3.0',
        ],
        'overview' => [
            'phibble is the shared table type for phplyr, phpolars, phit, and CmdStanPHP. Every operation returns a new data frame.',
            'Columns are typed as float, int, bool, string, factor, or date, and types are inferred on load. Missing values are PHP null.',
            'Data frames load from CSV files, PDO statements, and Laravel, Eloquent, or Doctrine query builders, so analysis can start from the application database. phibble does not depend on Laravel or Doctrine.',
            'toMatrix() and toVector() hand columns to phit functions such as Phit\fit_ols().',
        ],
    ],
    'phplyr' => [
        'name' => 'phplyr',
        'from_r' => 'dplyr and tidyr',
        'summary' => "A PHP port of the Tidyverse's dplyr and tidyr.",
        'status' => 'working',
        'status_label' => 'Released, v0.3.0',
        'repo' => 'https://github.com/phpstats/phplyr',
        'docs' => 'https://phpstats.github.io/phplyr/',
        'links' => [
            'Getting started' => 'https://phpstats.github.io/phplyr/getting-started.html',
            'Coming from dplyr' => 'https://phpstats.github.io/phplyr/coming-from-dplyr.html',
            'Gallery' => 'https://phpstats.github.io/phplyr/gallery.html',
            'Release notes' => 'https://github.com/phpstats/phplyr/releases/tag/v0.3.0',
        ],
        'overview' => [
            'phplyr covers most of dplyr and tidyr. Each function returns a closure, so steps compose with the PHP 8.5 pipe operator.',
            'The dplyr side includes the single-table functions, mutating and filtering joins, set operations, window functions such as lag(), lead(), and row_number(), and across() with tidyselect-style selectors.',
            'The tidyr side includes pivot_longer(), pivot_wider(), separate and unite functions, nesting, and functions for completing and filling missing values.',
            'Summary helpers such as n(), mean(), sd(), and quantile() follow R conventions for missing values and use phit when the extension is loaded. Model formulas such as monthly_rent ~ area_m2 + neighborhood will fit models through phit.',
        ],
    ],
    'phiz' => [
        'name' => 'phiz',
        'from_r' => 'ggplot2',
        'summary' => 'ggplot2-style charts for PHP, rendered with Vega-Lite.',
        'status' => 'working',
        'status_label' => 'Released, v0.3.0',
        'repo' => 'https://github.com/phpstats/phiz',
        'docs' => 'https://phpstats.github.io/phiz/',
        'links' => [
            'Release notes' => 'https://github.com/phpstats/phiz/releases/tag/v0.3.0',
        ],
        'overview' => [
            'phiz describes charts with a grammar modeled on ggplot2 and produces Vega-Lite specifications, which the browser renders.',
            'Plots take rows, columns, phibble data frames, or Laravel collections, and support layers, scales, facets, themes, and interactive selections.',
            'The <x-phiz> Blade component renders a plot and re-renders it when a Livewire component updates. Every chart on this site is drawn with it.',
        ],
    ],
    'phpolars' => [
        'name' => 'phpolars',
        'from_r' => 'polars',
        'summary' => 'Polars data frames as a PHP extension. Compatible with phibble.',
        'status' => 'working',
        'status_label' => 'Working, PIE-packaged',
        'repo' => 'https://github.com/phpstats/phpolars',
        'docs' => 'https://phpstats.github.io/phpolars/',
        'links' => [
            'Performance' => 'https://phpstats.github.io/phpolars/performance.html',
        ],
        'overview' => [
            'phpolars is a native PHP extension, written in Rust, that brings the Polars columnar engine to PHP. PIE installs it as the php_polars extension for PHP 8.3, 8.4, and 8.5 on Linux and macOS.',
            'phibble\'s PolarsTable holds a table in Polars through this extension, so phplyr pipelines can run in Polars. phibble and phplyr work without phpolars.',
            'In phplyr\'s backend benchmark (Apple M5 Ultra, PHP 8.5.10, October 2026), pipelines on a PolarsTable ran 1.3 to 15 times faster than on an in-memory phibble data frame. The pipeline that filters with a PHP closure gained the least.',
        ],
    ],
    'juphyter' => [
        'name' => 'juphyter',
        'from_r' => 'IRkernel and knitr',
        'summary' => 'A Jupyter and Quarto kernel for PHP.',
        'status' => 'working',
        'status_label' => 'Working',
        'repo' => 'https://github.com/phpstats/juphyter',
        // juphyter's docs are synced to this site and have no GitHub Pages copy.
        'docs' => null,
        'overview' => [
            'juphyter is a Jupyter kernel for PHP. It runs PHP cells in a notebook, so analysis with the PHPstats packages can be written and read as a notebook rather than a script.',
            'Quarto names a kernel in a document\'s front matter, so the same kernel executes PHP in a .qmd file and renders it to HTML or PDF.',
            'It corresponds to IRkernel, which runs R in Jupyter, and to knitr, which executes R in Quarto and R Markdown.',
            'Composer installs it globally with composer global require phpstats/juphyter, or into a project as a dev dependency. Then juphyter install, or vendor/bin/juphyter install in a project, writes the kernelspec that JupyterLab and Quarto read.',
            'The kernel needs PHP 8.3, 8.4, or 8.5 and a Python installation with Jupyter and ipykernel. Its wrapper runs inside ipykernel.',
        ],
    ],
    'cmdstanphp' => [
        'name' => 'CmdStanPHP',
        'from_r' => 'cmdstanr',
        'summary' => 'Bayesian models in Stan, with an API patterned after CmdStanR.',
        'status' => 'working',
        'status_label' => 'Working',
        'repo' => 'https://github.com/phpstats/cmdstanphp',
        'docs' => 'https://phpstats.github.io/CmdStanPHP/',
        'overview' => [
            'CmdStanPHP runs real Stan programs through CmdStan. It needs no R installation and no PHP framework.',
            'It supports compilation, parallel MCMC, optimization, ADVI, Pathfinder, and Laplace approximation, along with posterior summaries and fit persistence.',
            'MCMC fits compute PSIS-LOO and WAIC. Moment matching for PSIS-LOO uses the phit draw kernels when the extension is loaded.',
            'Trace, density, interval, rank, and Pareto k figures render as ASCII text, Vega-Lite charts, HTML, or SVG.',
            'CmdStanPHP covers most of CmdStanR but not all of it. Its compatibility contract lists the differences.',
        ],
    ],
];
