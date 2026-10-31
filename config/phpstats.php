<?php

return [

    /*
     * The landing page host and the pattern for package hosts. {package} is a
     * key in config/projects.php. Staging uses phpstats.pequod.sh and
     * phpstats-{package}.pequod.sh; production uses phpstats.org and
     * {package}.phpstats.org.
     */
    'root_host' => env('PHPSTATS_ROOT_HOST', 'phpstats.test'),

    'package_host' => env('PHPSTATS_PACKAGE_HOST', '{package}.phpstats.test'),

    /*
     * Path mode serves the whole site on one host, with each package under
     * /{package}, and ignores the hosts above. Use it for previews behind a
     * single tunnel URL, such as Polyscope's or Expose's.
     */
    'paths' => (bool) env('PHPSTATS_PATHS', false),

    /*
     * Whether robots.txt invites crawlers. Only production should: staging
     * and previews serve the same pages, each with a canonical URL of its
     * own, and would compete with production in search results. The hosts
     * above still have to match, so a crawler is welcome on neither an
     * unknown host nor a host that is not indexable.
     */
    'indexable' => (bool) env('PHPSTATS_INDEXABLE', env('APP_ENV') === 'production'),

    /*
     * Package content synced from each repo's site/ folder and rendered docs
     * by `php artisan site:sync`. The ref is the pinned commit. Packages
     * without an entry show the overview from config/projects.php only.
     */
    'content' => [
        'phibble' => ['repo' => 'phpstats/phibble', 'ref' => 'f0c1e8be8ff93acb8e09ac3049917a4038e9ddf6'],
        'phpolars' => ['repo' => 'phpstats/phpolars', 'ref' => '4a1e15f373a43cd9f0c1697e0bdf8945d0b2c50c'],
        'phplyr' => ['repo' => 'phpstats/phplyr', 'ref' => 'f7edd6e3974c7d67e4586171afac947411793d97'],
        'phit' => ['repo' => 'phpstats/phit', 'ref' => 'aaf22b4785de4504871a906ac106b9ac85739d26'],
        'phiz' => ['repo' => 'phpstats/phiz', 'ref' => '47bbf714962108d82c55135121babf5c58bd625f'],
        'cmdstanphp' => ['repo' => 'phpstats/CmdStanPHP', 'ref' => '4c8e86622d11e18f06b5ef90f27f83dcd75d0b69'],
    ],

    /*
     * How the packages fit together. The landing page
     * draws these layers, and each package host shows its layer and color.
     * Colors are Tailwind color names, and icons are Heroicons.
     */
    'layers' => [
        'data' => ['title' => 'Data & Workflow', 'role' => 'Tables, pipelines, and notebooks', 'color' => 'indigo', 'icon' => 'table-cells', 'packages' => [
            'phibble' => 'Immutable, typed data frames from CSV files, PDO, Eloquent, or Doctrine.',
            'phpolars' => 'Polars data frames as a PHP extension. Compatible with phibble.',
            'phplyr' => "A PHP port of the Tidyverse's dplyr and tidyr. Pipelines use PHP 8.5's pipe operator.",
            'juphyter' => 'PHP in Jupyter notebooks and Quarto documents.',
        ]],
        'stats' => ['title' => 'Statistics, Modeling & Visualization', 'role' => 'Models, inference, and charts', 'color' => 'purple', 'icon' => 'variable', 'packages' => [
            'phit' => 'Regression, mixed models, distributions, and descriptive statistics.',
            'phiz' => 'ggplot2-style charts, rendered with Vega-Lite.',
            'cmdstanphp' => 'Bayesian models in Stan.',
        ]],
    ],

    /*
     * The landing page's package explorer: a one-line summary for the list,
     * the title of its example, points about each package for its "About"
     * panel (inline Markdown, for links to upstream projects and `code`;
     * package names become links to their pages), the label for its output,
     * and, for examples pages can't run, a stored result: the callable that
     * gives it under result, and under files the paths it is computed from,
     * whose modification times go in the output's cache key, so re-capturing
     * one is enough. Each package's code is resources/examples/packages/{key}.php,
     * unless the entry gives its own source file and language, as juphyter's
     * Quarto document does. A package with no entry here gets no panel.
     */
    'explorer' => [
        'phibble' => [
            'summary' => 'Immutable, typed data frames for PHP.',
            'example' => 'Example: Load typed columns from the database',
            'does' => [
                "Inspired by the design and API of the tidyverse's [tibble](https://tibble.tidyverse.org/), phibble provides typed, immutable data frames for PHP. Operations return a new data frame and leave the original unchanged.",
                'Columns are typed as `float`, `int`, `bool`, `string`, `factor`, `date`, or `datetime`. Types are inferred on load or set explicitly, and missing values are `null`.',
                'Data frames load from CSV files, PDO statements, and Eloquent or Doctrine queries, so analysis can start from the application database.',
                'It is the common table type of the PHPstats packages, accepted by phplyr, phpolars, phiz, phit, and CmdStanPHP.',
            ],
            'output' => 'Data frame glimpse',
        ],
        'phpolars' => [
            'summary' => 'Polars data frames for PHP, in a Rust extension.',
            'example' => 'Example: Aggregate database rows in Polars',
            'does' => [
                'Brings [Polars](https://pola.rs/) to PHP as a native extension written in Rust. It follows the Python Polars API and its expression-based approach to data manipulation.',
                'Provides `DataFrame`, `LazyFrame`, `Series`, and expressions, with lazy execution, joins, aggregations, and window functions.',
                'Reads query results from any PDO connection with `read_database()`, runs SQL queries, and reads and writes Parquet and Arrow IPC files.',
                "phibble's `PolarsTable` runs on this extension, so phplyr pipelines can use the Polars engine.",
            ],
            'output' => 'Polars table',
        ],
        'phplyr' => [
            'summary' => 'dplyr and tidyr pipelines for PHP.',
            'example' => 'Example: Revenue share by channel and quarter',
            'does' => [
                "Ports the API and design of the tidyverse's [dplyr](https://dplyr.tidyverse.org/) and [tidyr](https://tidyr.tidyverse.org/) to PHP, keeping their function names, arguments, and conventions.",
                'Provides verbs for filtering, selecting, mutating, grouping, summarizing, arranging, joining, and reshaping, with grouped window functions, `across()`, `pivot_wider()`, `pivot_longer()`, and nesting.',
                "Pipelines use PHP 8.5's `|>` operator, with a method chain on PHP 8.3 and 8.4.",
                'The same pipeline runs on a phibble data frame, in the database as SQL, or in Polars.',
            ],
            'output' => 'Data frame preview',
        ],
        'juphyter' => [
            'summary' => 'A Jupyter and Quarto kernel for PHP.',
            'example' => 'Example: Work out an analysis before it reaches the application',
            'does' => [
                'Registers a PHP kernel with [Jupyter](https://jupyter.org/), so JupyterLab runs PHP cells and [Quarto](https://quarto.org/) executes a `{php}` block in a `.qmd`. A Quarto document selects the kernel with `jupyter: php` in its front matter.',
                'A notebook shows each result under the code that produced it, so data loading, pipelines, and model fits can be tried before they move into an application.',
                "A notebook sees the project it sits in. The engine looks upward from the notebook for `vendor/autoload.php`, so a notebook inside a Composer project has that project's own classes and packages in scope with no configuration.",
                'Each value gets the richest form the front end can draw: a phibble data frame becomes an HTML table and a phiz plot becomes a chart, with state kept across cells, so a variable bound in one cell is in scope in the next.',
            ],
            'output' => 'Rendered notebook cell',
            'source' => ['file' => 'packages/juphyter.qmd', 'language' => 'quarto'],
            'stored' => [
                'result' => [\App\Data\JuphyterCell::class, 'explorerOutput'],
                'files' => [\App\Data\JuphyterCell::path()],
            ],
        ],
        'phiz' => [
            'summary' => 'ggplot2-style charts rendered with Vega-Lite.',
            'example' => 'Example: Compare fitted trends across genres',
            'does' => [
                'Inspired by [ggplot2](https://ggplot2.tidyverse.org/) and the [grammar of graphics](https://link.springer.com/book/9780387245447), phiz brings declarative, layered statistical graphics to PHP.',
                'Charts are composed from aesthetic mappings, geometric layers, statistical layers such as `smooth()`, scales, labels, and facets with a ggplot2-style API.',
                'Produces [Vega-Lite](https://vega.github.io/vega-lite/) specifications, which the browser renders, including interactive selections.',
                'Plots take phibble data frames, arrays of rows, or Laravel collections. Its `<x-phiz>` Blade component redraws a chart when a Livewire component updates.',
            ],
            'output' => 'Chart',
        ],
        'phit' => [
            'summary' => 'Regression, mixed models, and descriptive statistics.',
            'example' => 'Example: Fit a model and quantify uncertainty',
            'does' => [
                'phit fits statistical models directly in PHP. It provides linear models with `fit_lm()`, logistic, probit, and Poisson regression with `fit_glm()`, and mixed-effects models with `fit_lmer()` and `fit_glmer()`.',
                'It is a native extension written in Rust. It takes phibble data frames and R-style formulas and returns plain PHP arrays.',
                '[phit-polyfill](https://github.com/phpstats/phit-polyfill) implements the same functions in plain PHP, so the same code runs where the extension cannot be installed. It is slower, and the CSV reader needs the extension.',
                'Includes probability distributions and descriptive statistics, such as `dist_t_quantile()` and `stat_quantile()`, alongside model fitting.',
            ],
            'output' => 'Model summary and slope intervals',
        ],
        'cmdstanphp' => [
            'summary' => 'Bayesian modeling in Stan from PHP.',
            'example' => 'Example: Fit a hierarchical Bayesian model',
            'does' => [
                'Inspired by [CmdStanR](https://mc-stan.org/cmdstanr/), CmdStanPHP is a PHP interface to [Stan](https://mc-stan.org/) for Bayesian modeling. It compiles and runs Stan programs with an installed CmdStan.',
                'Supports MCMC sampling, optimization, variational inference, Pathfinder, and Laplace approximation.',
                'Builds Stan data from R-style model formulas and phibble data frames.',
                'Summarizes posteriors with convergence diagnostics and compares models with PSIS-LOO and WAIC.',
            ],
            'output' => 'Posterior summary',
            'stored' => [
                'result' => [\App\Data\SchoolPosterior::class, 'explorerSummary'],
                'files' => [\App\Data\SchoolPosterior::path()],
            ],
        ],
    ],

    /*
     * Packages that are part of another package on this site, as
     * [companion => parent]. A companion has no pages or menu entry of its
     * own: its host redirects to the parent, whose overview mentions it.
     */
    'companions' => [
        'phit-polyfill' => 'phit',
    ],

    /*
     * The site bar's menus. Each lists the packages of its layers, so every
     * package is in one menu. short is the label on narrow screens. A menu
     * with one layer shows no layer heading.
     */
    'menus' => [
        'data' => ['title' => 'Data & Workflow', 'short' => 'Data', 'layers' => ['data']],
        'stats' => ['title' => 'Statistics, Modeling & Visualization', 'short' => 'Statistics', 'layers' => ['stats']],
    ],

    /*
     * Where one package builds on another, as [from, to, sentence]. Both
     * package hosts list the link: "Uses" on the first and "Used by" on the
     * second.
     */
    'relations' => [
        ['phplyr', 'phibble', 'phplyr functions take phibble data frames and return new ones.'],
        ['phplyr', 'phit', 'phplyr\'s lm() and glm() fit their formulas with phit, and its summary helpers use phit when it is loaded.'],
        ['phplyr', 'phpolars', 'phplyr pipelines run in Polars when the table is a phibble PolarsTable, which uses the phpolars extension.'],
        ['phibble', 'phpolars', 'phibble\'s PolarsTable holds a table in Polars through the phpolars extension and implements phibble\'s table contract.'],
        ['phibble', 'phit', 'phibble\'s toMatrix() and toVector() hand columns to phit functions such as Phit\\fit_ols().'],
        ['phiz', 'phibble', 'phiz plots take phibble data frames as data.'],
        ['cmdstanphp', 'phit', 'CmdStanPHP uses phit\'s draw kernels for PSIS-LOO moment matching when the extension is loaded.'],
    ],

    'content_path' => resource_path('content'),

    /*
     * Vignettes written as Livewire components in this app. Synced packages
     * add their own from site/vignettes. The first package owns the page;
     * the others list it too.
     */
    'vignettes' => [
        'simulated-rents' => ['packages' => ['phit', 'phplyr'], 'title' => 'Simulated rents', 'summary' => 'Linear regression with a factor, compared with the coefficients that generated the data.'],
        'school-scores' => ['packages' => ['phit'], 'title' => 'School scores', 'summary' => 'Linear regression with a choice of predictors and an observed-against-fitted chart.'],
        'glm-models' => ['packages' => ['phit'], 'title' => 'Binary and count outcomes', 'summary' => 'Logistic, probit, and Poisson regression from R formulas, with predictions and confidence bands.'],
        'school-shrinkage' => ['packages' => ['phit'], 'title' => 'School effects and shrinkage', 'summary' => 'A linear mixed model with random school intercepts, variance components, shrinkage, and what-if predictions.'],
        'random-effects' => ['packages' => ['phit', 'phiz'], 'title' => 'Random-effect intervals', 'summary' => 'A caterpillar plot of school effects with intervals from their conditional variances.'],
        'csv-reader' => ['packages' => ['phit', 'phibble'], 'title' => 'Reading CSV files', 'summary' => 'The native CSV reader on editable text: guessed types, parse failures, problems, and agreement with phibble.'],
        'sql-pipeline' => ['packages' => ['phibble', 'phplyr'], 'title' => 'SQL pipeline', 'summary' => 'One phplyr pipeline run as a single SQL query and in memory, with the generated SQL.'],
        'rent-dashboard' => ['packages' => ['phplyr', 'phiz', 'phit'], 'title' => 'Rent dashboard', 'summary' => 'A reactive Livewire dashboard: filters, a phplyr summary, two phiz charts, a logistic model, and what-if predictions.'],
        'polars-pipeline' => ['packages' => ['phpolars', 'phibble', 'phplyr'], 'title' => 'Polars pipeline', 'summary' => 'The same pipeline on an in-memory data frame and in Polars, with timings for up to 300,000 rows.'],
        'bayesian-schools' => ['packages' => ['cmdstanphp', 'phit'], 'title' => 'Bayesian school effects', 'summary' => 'The school effects model in Stan through CmdStanPHP, with posterior intervals next to Phit\fit_lmer().'],
    ],

];
