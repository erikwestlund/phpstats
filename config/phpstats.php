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
     * Package content synced from each repo's site/ folder and rendered docs
     * by `php artisan site:sync`. The ref is the pinned commit. Packages
     * without an entry show the overview from config/projects.php only.
     */
    'content' => [
        'phit' => ['repo' => 'phpstats/phit', 'ref' => 'bb9133ea39245977f0b79f77d168c7d4e9bdd107'],
        'phpolars' => ['repo' => 'phpstats/phpolars', 'ref' => 'b57bff8076647093f4b2a4a3f2fe3ef02cb5f96a'],
        'phplyr' => ['repo' => 'phpstats/phplyr', 'ref' => 'da6c617adeb02a6ee0ae8f488dd0543ecae27dd9'],
    ],

    /*
     * How the packages fit together. The landing page
     * draws these layers, and each package host shows its layer and color.
     * Colors are Tailwind color names, and icons are Heroicons.
     */
    'layers' => [
        'data' => ['title' => 'Data Frames', 'role' => 'Tables from databases, queries, and files', 'color' => 'indigo', 'icon' => 'table-cells', 'packages' => [
            'phibble' => 'Immutable, typed data frames from CSV files, PDO, Eloquent, or Doctrine.',
            'phpolars' => 'Polars data frames as a PHP extension. Compatible with phibble.',
        ]],
        'processing' => ['title' => 'Processing & Visualization', 'role' => 'Pipelines and charts', 'color' => 'fuchsia', 'icon' => 'presentation-chart-line', 'packages' => [
            'phplyr' => "A PHP port of the Tidyverse's dplyr and tidyr. Pipelines use PHP 8.5's pipe operator.",
            'phiz' => 'ggplot2-style charts, rendered with Vega-Lite.',
        ]],
        'models' => ['title' => 'Statistical Modeling', 'role' => 'Regression, mixed, and Bayesian models', 'color' => 'purple', 'icon' => 'variable', 'packages' => [
            'phit' => 'Regression, mixed models, distributions, and descriptive statistics.',
            'cmdstanphp' => 'Bayesian models in Stan.',
        ]],
    ],

    /*
     * The landing page's package explorer: a fuller description of each
     * package, the label for its output, and, for examples that pages can't
     * run, a callable that gives the stored result. Each package's code is
     * resources/examples/packages/{key}.php.
     */
    'explorer' => [
        'phibble' => [
            'description' => 'Immutable, typed data frames for PHP. Column types are inferred on load, and missing values are null. Frames load from CSV files, PDO statements, and Eloquent or Doctrine queries, and every operation returns a new frame.',
            'output' => 'Printed data frame',
        ],
        'phpolars' => [
            'description' => 'Polars data frames as a PHP extension, written in Rust, following the py-polars API with lazy queries, joins, and SQL. Its PolarsTable is compatible with phibble, so phplyr pipelines run on the Polars engine.',
            'output' => 'Printed Polars DataFrame',
        ],
        'phplyr' => [
            'description' => "A PHP port of the Tidyverse's dplyr and tidyr, with their function names and arguments. Pipelines use PHP 8.5's pipe operator, or a method chain on 8.3 and 8.4, and run on phibble frames, in SQL, or in Polars.",
            'output' => 'Printed data frame',
        ],
        'phiz' => [
            'description' => 'ggplot2-style charts built from aesthetics, layers, scales, and facets, rendered with Vega-Lite. Plots take phibble frames, rows, or Laravel collections, and a Blade component redraws them when Livewire updates.',
            'output' => 'Chart',
        ],
        'phit' => [
            'description' => 'Regression, mixed models, probability distributions, and descriptive statistics, in a Rust extension. Formulas work as in R through fit_lm(), fit_glm(), fit_lmer(), and fit_glmer(), and results are plain PHP arrays.',
            'output' => 'Model summary',
        ],
        'cmdstanphp' => [
            'description' => 'Bayesian models in Stan, run through CmdStan with an API patterned after CmdStanR. It supports MCMC, optimization, variational inference, and Pathfinder, with posterior summaries and convergence diagnostics.',
            'output' => 'Posterior summary',
            'stored' => [\App\Data\SchoolPosterior::class, 'explorerSummary'],
            'note' => 'Pages never run CmdStan. This summary is computed by CmdStanPHP from the stored draws of this fit (CmdStan 2.40.0).',
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
        'data' => ['title' => 'Data Frames', 'short' => 'Data', 'layers' => ['data']],
        'processing' => ['title' => 'Processing & Visualization', 'short' => 'Processing', 'layers' => ['processing']],
        'modeling' => ['title' => 'Statistical Modeling', 'short' => 'Modeling', 'layers' => ['models']],
    ],

    /*
     * Where one package builds on another, as [from, to, sentence]. Both
     * package hosts list the link: "Uses" on the first and "Used by" on the
     * second.
     */
    'relations' => [
        ['phplyr', 'phibble', 'phplyr functions take phibble frames and return new ones.'],
        ['phplyr', 'phit', 'phplyr\'s lm() and glm() fit their formulas with phit, and its summary helpers use phit when it is loaded.'],
        ['phplyr', 'phpolars', 'phplyr pipelines run in Polars when the frame is a phpolars PolarsTable.'],
        ['phpolars', 'phibble', 'phpolars\' PolarsTable implements phibble\'s table contract.'],
        ['phibble', 'phit', 'phibble\'s toMatrix() and toVector() hand columns to phit functions such as Phit\\fit_ols().'],
        ['phiz', 'phibble', 'phiz plots take phibble frames as data.'],
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
