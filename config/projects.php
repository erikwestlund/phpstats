<?php

// The phpstats family. Keys are the URL paths (phpstats.org/{key}).
// Status kinds: working, exists, in-progress, experimental.
// docs is null until the project publishes a reference site.
// links (optional) adds more documentation links, as label => URL.

return [
    'php-stats' => [
        'name' => 'php-stats',
        'summary' => 'A native PHP extension, written in Rust, for regression, mixed models, distributions, and descriptive statistics.',
        'status' => 'working',
        'status_label' => 'Working, v0.2',
        'repo' => 'https://github.com/erikwestlund/php-stats',
        'docs' => 'https://erikwestlund.github.io/php-stats/',
        'overview' => [
            'php-stats fits statistical models inside PHP and returns plain PHP arrays. It provides OLS, logistic, probit, and Poisson regression, linear mixed models, and mixed logistic regression.',
            'Version 0.2 adds probability distributions, descriptive statistics, posterior-draw kernels, and a seeded random number generator. Results are verified against R.',
            'The extension targets PHP 8.5 on Linux and macOS and installs with PIE.',
        ],
    ],
    'php-stats-polyfill' => [
        'name' => 'php-stats-polyfill',
        'summary' => 'A pure-PHP fallback for the php-stats functions, for hosts that cannot install the extension.',
        'status' => 'in-progress',
        'status_label' => 'In progress',
        'repo' => 'https://github.com/erikwestlund/php-stats-polyfill',
        'docs' => null,
        'overview' => [
            'php-stats-polyfill implements the stats_* functions in plain PHP. It covers descriptive statistics, OLS, logistic, probit, and Poisson regression, and probability distributions. Multilevel models need the extension.',
            'The polyfill defines its functions only when the extension is not loaded, so the same code runs with or without the extension.',
            'Descriptive statistics are implemented and tested for parity with the extension. Regression and distributions are in progress.',
        ],
    ],
    'phibble' => [
        'name' => 'phibble',
        'summary' => 'A typed, immutable data frame that loads from CSV files, PDO, and Eloquent or Doctrine queries.',
        'status' => 'in-progress',
        'status_label' => 'MVP in progress',
        'repo' => 'https://github.com/erikwestlund/phibble',
        'docs' => 'https://erikwestlund.github.io/phibble/',
        'overview' => [
            'phibble is the shared table type for phplyr, phpolars, php-stats, and cmdstanphp. Every operation returns a new frame.',
            'Columns are typed as float, int, bool, string, factor, or date, and types are inferred on load. Missing values are PHP null.',
            'Frames load from CSV files, PDO statements, and Laravel, Eloquent, or Doctrine query builders, so analysis can start from the application database. phibble does not depend on Laravel or Doctrine.',
            'toMatrix() and toVector() hand columns to php-stats functions such as stats_ols().',
        ],
    ],
    'phplyr' => [
        'name' => 'phplyr',
        'summary' => 'dplyr-style verbs as pipe functions, with lm() and glm() formulas that call php-stats.',
        'status' => 'in-progress',
        'status_label' => 'MVP in progress',
        'repo' => 'https://github.com/erikwestlund/phplyr',
        'docs' => 'https://erikwestlund.github.io/phplyr/',
        'links' => [
            'Getting started' => 'https://erikwestlund.github.io/phplyr/getting-started.html',
            'Coming from dplyr' => 'https://erikwestlund.github.io/phplyr/coming-from-dplyr.html',
            'Gallery' => 'https://erikwestlund.github.io/phplyr/gallery.html',
        ],
        'overview' => [
            'phplyr covers most of dplyr and tidyr. Each verb returns a closure, so verbs compose with the PHP 8.5 pipe operator.',
            'The dplyr side includes the single-table verbs, mutating and filtering joins, set operations, window functions such as lag(), lead(), and row_number(), and across() with tidyselect-style selectors.',
            'The tidyr side includes pivot_longer(), pivot_wider(), separate and unite functions, nesting, and functions for completing and filling missing values.',
            'Summary helpers such as n(), mean(), sd(), and quantile() follow R conventions for missing values and use php-stats when the extension is loaded. Model formulas such as monthly_rent ~ area_m2 + neighborhood will fit models through php-stats.',
        ],
    ],
    'phiz' => [
        'name' => 'phiz',
        'summary' => 'A ggplot-style grammar of graphics for PHP that produces Vega-Lite charts.',
        'status' => 'in-progress',
        'status_label' => 'In progress',
        'repo' => 'https://github.com/erikwestlund/phiz',
        'docs' => 'https://erikwestlund.github.io/phiz/',
        'overview' => [
            'phiz describes charts with a grammar modeled on ggplot2 and produces Vega-Lite specifications, which the browser renders.',
        ],
    ],
    'phpolars' => [
        'name' => 'phpolars',
        'summary' => 'An optional Polars engine for large data frames.',
        'status' => 'experimental',
        'status_label' => 'Experimental',
        'repo' => 'https://github.com/erikwestlund/phpolars',
        'docs' => null,
        'overview' => [
            'phpolars runs phibble frames on the Polars columnar engine. phibble works without it.',
        ],
    ],
    'cmdstanphp' => [
        'name' => 'cmdstanphp',
        'summary' => 'Bayesian models in Stan, run from PHP through CmdStan, with an API patterned after CmdStanR.',
        'status' => 'working',
        'status_label' => 'Working',
        'repo' => 'https://github.com/erikwestlund/cmdstanphp',
        'docs' => null,
        'overview' => [
            'cmdstanphp runs real Stan programs through CmdStan. It needs no R installation and no PHP framework.',
            'It supports compilation, parallel MCMC, optimization, ADVI, Pathfinder, and Laplace approximation, along with posterior summaries and fit persistence.',
            'MCMC fits compute PSIS-LOO and WAIC. Moment matching for PSIS-LOO uses the php-stats draw kernels when the extension is loaded.',
            'Trace, density, interval, rank, and Pareto k figures render as ASCII text, Vega-Lite charts, HTML, or SVG.',
            'cmdstanphp covers most of CmdStanR but not all of it. Its compatibility contract lists the differences.',
        ],
    ],
];
