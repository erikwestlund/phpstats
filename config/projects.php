<?php

// The phpstats family. Keys are the URL paths (phpstats.org/{key}).
// Status kinds: working, exists, in-progress, experimental.
// docs is null until the project publishes a reference site.
// links (optional) adds more documentation links, as label => URL.

return [
    'phit' => [
        'name' => 'phit',
        'summary' => 'A native PHP extension, written in Rust, for regression, mixed models, distributions, and descriptive statistics.',
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
        'summary' => 'A pure-PHP fallback for the phit functions, for hosts that cannot install the extension.',
        'status' => 'in-progress',
        'status_label' => 'In progress',
        'repo' => 'https://github.com/phpstats/phit-polyfill',
        'docs' => null,
        'overview' => [
            'phit-polyfill implements the extension functions in plain PHP. It still uses the earlier stats_* names and will move to the Phit namespace. It covers descriptive statistics, OLS, logistic, probit, and Poisson regression, and probability distributions. Multilevel models need the extension.',
            'The polyfill defines its functions only when the extension is not loaded, so the same code runs with or without the extension.',
            'Descriptive statistics are implemented and tested for parity with the extension. Regression and distributions are in progress.',
        ],
    ],
    'phibble' => [
        'name' => 'phibble',
        'summary' => 'A typed, immutable data frame that loads from CSV files, PDO, and Eloquent or Doctrine queries.',
        'status' => 'working',
        'status_label' => 'Released, v0.2.0',
        'repo' => 'https://github.com/phpstats/phibble',
        'docs' => 'https://phpstats.github.io/phibble/',
        'links' => [
            'Laravel integration' => 'https://phpstats.github.io/phibble/laravel.html',
            'Release notes' => 'https://github.com/phpstats/phibble/releases/tag/v0.2.0',
        ],
        'overview' => [
            'phibble is the shared table type for phplyr, phpolars, phit, and cmdstanphp. Every operation returns a new frame.',
            'Columns are typed as float, int, bool, string, factor, or date, and types are inferred on load. Missing values are PHP null.',
            'Frames load from CSV files, PDO statements, and Laravel, Eloquent, or Doctrine query builders, so analysis can start from the application database. phibble does not depend on Laravel or Doctrine.',
            'toMatrix() and toVector() hand columns to phit functions such as Phit\fit_ols().',
        ],
    ],
    'phplyr' => [
        'name' => 'phplyr',
        'summary' => 'dplyr-style verbs as pipe functions, with lm() and glm() formulas that call phit.',
        'status' => 'working',
        'status_label' => 'Released, v0.2.0',
        'repo' => 'https://github.com/phpstats/phplyr',
        'docs' => 'https://phpstats.github.io/phplyr/',
        'links' => [
            'Getting started' => 'https://phpstats.github.io/phplyr/getting-started.html',
            'Coming from dplyr' => 'https://phpstats.github.io/phplyr/coming-from-dplyr.html',
            'Gallery' => 'https://phpstats.github.io/phplyr/gallery.html',
            'Release notes' => 'https://github.com/phpstats/phplyr/releases/tag/v0.2.0',
        ],
        'overview' => [
            'phplyr covers most of dplyr and tidyr. Each verb returns a closure, so verbs compose with the PHP 8.5 pipe operator.',
            'The dplyr side includes the single-table verbs, mutating and filtering joins, set operations, window functions such as lag(), lead(), and row_number(), and across() with tidyselect-style selectors.',
            'The tidyr side includes pivot_longer(), pivot_wider(), separate and unite functions, nesting, and functions for completing and filling missing values.',
            'Summary helpers such as n(), mean(), sd(), and quantile() follow R conventions for missing values and use phit when the extension is loaded. Model formulas such as monthly_rent ~ area_m2 + neighborhood will fit models through phit.',
        ],
    ],
    'phiz' => [
        'name' => 'phiz',
        'summary' => 'A ggplot-style grammar of graphics for PHP that produces Vega-Lite charts.',
        'status' => 'working',
        'status_label' => 'Released, v0.3.0',
        'repo' => 'https://github.com/phpstats/phiz',
        'docs' => 'https://phpstats.github.io/phiz/',
        'links' => [
            'Release notes' => 'https://github.com/phpstats/phiz/releases/tag/v0.3.0',
        ],
        'overview' => [
            'phiz describes charts with a grammar modeled on ggplot2 and produces Vega-Lite specifications, which the browser renders.',
            'Plots take rows, columns, phibble frames, or Laravel collections, and support layers, scales, facets, themes, and interactive selections.',
            'The <x-phiz> Blade component renders a plot and re-renders it when a Livewire component updates. Every chart on this site is drawn with it.',
        ],
    ],
    'phpolars' => [
        'name' => 'phpolars',
        'summary' => 'Polars data frames for PHP as a native Rust extension, with a phibble table backend for phplyr.',
        'status' => 'working',
        'status_label' => 'Working, PIE-packaged',
        'repo' => 'https://github.com/phpstats/phpolars',
        'docs' => 'https://phpstats.github.io/phpolars/',
        'links' => [
            'Performance' => 'https://phpstats.github.io/phpolars/performance.html',
        ],
        'overview' => [
            'phpolars is a native PHP extension, written in Rust, that brings the Polars columnar engine to PHP. It installs with PIE as php_polars and targets PHP 8.5 on Linux and macOS.',
            'Its PolarsTable implements phibble\'s table contract, so phplyr pipelines run in Polars. phibble and phplyr work without it.',
            'The verbs run 10 to 100 times faster in Polars than on an in-memory phibble frame. Converting results back to PHP values takes most of a PolarsTable pipeline\'s time, so pipelines that reduce the data before collecting gain the most.',
        ],
    ],
    'cmdstanphp' => [
        'name' => 'cmdstanphp',
        'summary' => 'Bayesian models in Stan, run from PHP through CmdStan, with an API patterned after CmdStanR.',
        'status' => 'working',
        'status_label' => 'Working',
        'repo' => 'https://github.com/phpstats/cmdstanphp',
        'docs' => null,
        'overview' => [
            'cmdstanphp runs real Stan programs through CmdStan. It needs no R installation and no PHP framework.',
            'It supports compilation, parallel MCMC, optimization, ADVI, Pathfinder, and Laplace approximation, along with posterior summaries and fit persistence.',
            'MCMC fits compute PSIS-LOO and WAIC. Moment matching for PSIS-LOO uses the phit draw kernels when the extension is loaded.',
            'Trace, density, interval, rank, and Pareto k figures render as ASCII text, Vega-Lite charts, HTML, or SVG.',
            'cmdstanphp covers most of CmdStanR but not all of it. Its compatibility contract lists the differences.',
        ],
    ],
];
