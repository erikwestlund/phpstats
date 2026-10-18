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
     * Package content synced from each repo's site/ folder and rendered docs
     * by `php artisan site:sync`. The ref is the pinned commit. Packages
     * without an entry show the overview from config/projects.php only.
     */
    'content' => [
        'phit' => ['repo' => 'phpstats/phit', 'ref' => 'bb9133ea39245977f0b79f77d168c7d4e9bdd107'],
        'phpolars' => ['repo' => 'phpstats/phpolars', 'ref' => 'b57bff8076647093f4b2a4a3f2fe3ef02cb5f96a'],
    ],

    /*
     * How the packages fit together, from data to graphics. The landing page
     * draws these layers, and each package host shows its layer and color.
     * Colors are Tailwind color names.
     */
    'layers' => [
        'data' => ['title' => 'Data', 'role' => 'Tables from CSV files, the database, or Polars', 'color' => 'indigo', 'packages' => [
            'phibble' => 'The shared data frame: typed, immutable, and loaded from CSV, PDO, Eloquent, or Doctrine',
            'phpolars' => 'Polars data frames in a Rust extension, and a fast backend for phibble',
        ]],
        'verbs' => ['title' => 'Verbs', 'role' => 'Filter, mutate, group, summarise, join, and reshape', 'color' => 'violet', 'packages' => [
            'phplyr' => 'dplyr and tidyr verbs that run in memory, as SQL, or in Polars',
        ]],
        'models' => ['title' => 'Models', 'role' => 'Estimation, verified against R', 'color' => 'purple', 'packages' => [
            'phit' => 'Regression, mixed models, distributions, and descriptive statistics in a Rust extension',
            'phit-polyfill' => 'The phit functions in plain PHP, for hosts that cannot install extensions',
            'cmdstanphp' => 'Bayesian models in Stan through CmdStan',
        ]],
        'graphics' => ['title' => 'Graphics', 'role' => 'A grammar of graphics', 'color' => 'fuchsia', 'packages' => [
            'phiz' => 'ggplot2-style charts that render as Vega-Lite, with a Blade component for Livewire',
        ]],
    ],

    /*
     * Where one package builds on another, as [from, to, sentence]. Both
     * package hosts list the link: "Uses" on the first and "Used by" on the
     * second.
     */
    'relations' => [
        ['phplyr', 'phibble', 'phplyr verbs take phibble frames and return new ones.'],
        ['phplyr', 'phit', 'phplyr\'s lm() and glm() fit their formulas with phit, and its summary helpers use phit when it is loaded.'],
        ['phplyr', 'phpolars', 'phplyr verbs run in Polars when the frame is a phpolars PolarsTable.'],
        ['phpolars', 'phibble', 'phpolars\' PolarsTable implements phibble\'s table contract.'],
        ['phibble', 'phit', 'phibble\'s toMatrix() and toVector() hand columns to phit functions such as Phit\\fit_ols().'],
        ['phiz', 'phibble', 'phiz plots take phibble frames as data.'],
        ['phit-polyfill', 'phit', 'phit-polyfill defines the phit functions in plain PHP when the extension is not loaded.'],
        ['cmdstanphp', 'phit', 'cmdstanphp uses phit\'s draw kernels for PSIS-LOO moment matching when the extension is loaded.'],
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
        'bayesian-schools' => ['packages' => ['cmdstanphp', 'phit'], 'title' => 'Bayesian school effects', 'summary' => 'The school effects model in Stan through cmdstanphp, with posterior intervals next to Phit\fit_lmer().'],
    ],

];
