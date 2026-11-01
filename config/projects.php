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
        'summary' => 'Statistical models, probability distributions, and descriptive statistics.',
        'status' => 'working',
        'status_label' => 'Working',
        'repo' => 'https://github.com/phpstats/phit',
        'docs' => 'https://phpstats.github.io/phit/',
        'overview' => [
            'Fit statistical models, compute probability distributions, and summarize data in PHP. Model functions accept formulas or explicit designs and return PHP arrays.',
            'The models are OLS, logistic, probit, and Poisson regression, linear mixed models, and mixed logistic regression. The library also has probability distributions, descriptive statistics, posterior-draw kernels, and a seeded random number generator.',
            'Every function is in the Phit namespace, such as Phit\fit_ols(), Phit\predict(), and Phit\dist_normal_cdf().',
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
        'summary' => 'Immutable, typed data frames for PHP.',
        'status' => 'working',
        'status_label' => 'Released, v0.3.0',
        'repo' => 'https://github.com/phpstats/phibble',
        'docs' => 'https://phpstats.github.io/phibble/',
        'links' => [
            'Laravel integration' => 'https://phpstats.github.io/phibble/laravel.html',
            'Release notes' => 'https://github.com/phpstats/phibble/releases/tag/v0.3.0',
        ],
        'overview' => [
            'Load, transform, and summarize typed data frames in PHP. Tables are immutable, and null represents missing data.',
            'Columns are typed as float, int, bool, string, factor, or date, and types are inferred on load. phibble is the table type that phplyr, phpolars, phit, and CmdStanPHP read or return.',
            'Data frames load from CSV files, PDO statements, and Laravel, Eloquent, or Doctrine query builders, so analysis can start from the application database. phibble does not depend on Laravel or Doctrine.',
            'toMatrix() and toVector() hand columns to phit functions such as Phit\fit_ols().',
        ],
    ],
    'phplyr' => [
        'name' => 'phplyr',
        'from_r' => 'dplyr and tidyr',
        'summary' => 'dplyr, tidyr, and janitor data wrangling in PHP.',
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
            'Filter, transform, join, and reshape tables with dplyr- and tidyr-style functions. Pipelines use PHP 8.5\'s pipe operator, or method chains on PHP 8.3 and 8.4.',
            'The dplyr functions cover single-table verbs, joins, set operations, window functions, and across() with tidyselect-style selectors. The tidyr functions cover pivots, separating and uniting columns, nesting, and completing and filling missing values.',
            'Summary helpers such as n(), mean(), sd(), and quantile() follow R\'s rules for missing values and use phit when the extension is loaded.',
        ],
    ],
    'phiz' => [
        'name' => 'phiz',
        'from_r' => 'ggplot2',
        'summary' => 'ggplot2-style charts rendered with Vega-Lite.',
        'status' => 'working',
        'status_label' => 'Released, v0.3.0',
        'repo' => 'https://github.com/phpstats/phiz',
        'docs' => 'https://phpstats.github.io/phiz/',
        'links' => [
            'Release notes' => 'https://github.com/phpstats/phiz/releases/tag/v0.3.0',
        ],
        'overview' => [
            'Build charts in PHP with data, mappings, and layers. Render interactive charts with Vega-Lite or export static images.',
            'Plots take rows, columns, phibble data frames, or Laravel collections, and support layers, scales, facets, themes, and interactive selections.',
            'The <x-phiz> Blade component renders a plot and re-renders it when a Livewire component updates. Every chart on this site is drawn with it.',
        ],
    ],
    'phpolars' => [
        'name' => 'phpolars',
        'from_r' => 'polars',
        'summary' => 'Polars data frames for PHP through a native extension.',
        'status' => 'working',
        'status_label' => 'Working, PIE-packaged',
        'repo' => 'https://github.com/phpstats/phpolars',
        'docs' => 'https://phpstats.github.io/phpolars/',
        'links' => [
            'Performance' => 'https://phpstats.github.io/phpolars/performance.html',
        ],
        'overview' => [
            'Query and transform data with Polars from PHP. Use eager data frames or build lazy queries with expressions.',
            'phpolars is a PHP extension written in Rust. PIE installs it as the php_polars extension.',
            'phibble\'s PolarsTable holds a table in Polars through this extension, so phplyr pipelines can run in Polars. phibble and phplyr work without phpolars.',
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
            'Run PHP in Jupyter notebooks and Quarto documents, with tables and charts displayed alongside code.',
            'Quarto names a kernel in a document\'s front matter, so the same kernel executes PHP in a .qmd file and renders it to HTML or PDF.',
            'It corresponds to IRkernel, which runs R in Jupyter, and to knitr, which executes R in Quarto and R Markdown.',
        ],
    ],
    'cmdstanphp' => [
        'name' => 'CmdStanPHP',
        // CmdStanPHP's build tools compile Stan models, not the package.
        'build_label' => 'To compile models',
        'from_r' => 'cmdstanr',
        'summary' => 'A PHP port of CmdStanR for Bayesian modeling with Stan.',
        'status' => 'working',
        'status_label' => 'Working',
        'repo' => 'https://github.com/phpstats/cmdstanphp',
        'docs' => 'https://phpstats.github.io/CmdStanPHP/',
        'overview' => [
            'Compile Stan models, run Bayesian inference, and inspect posterior draws from PHP. CmdStanPHP runs Stan programs through CmdStan and needs no R installation.',
            'It supports compilation, parallel MCMC, optimization, ADVI, Pathfinder, and Laplace approximation, along with posterior summaries and fit persistence.',
            'MCMC fits compute PSIS-LOO and WAIC. Moment matching for PSIS-LOO uses the phit draw kernels when the extension is loaded.',
            'Trace, density, interval, rank, and Pareto k figures render as ASCII text, Vega-Lite charts, HTML, or SVG.',
            'CmdStanPHP covers most of CmdStanR but not all of it. Its compatibility contract lists the differences.',
        ],
    ],
];
