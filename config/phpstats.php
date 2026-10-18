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
        'phit' => ['repo' => 'phpstats/phit', 'ref' => '74999288dab30022d8ce056c4b94e843753eefca'],
        'phpolars' => ['repo' => 'phpstats/phpolars', 'ref' => 'b57bff8076647093f4b2a4a3f2fe3ef02cb5f96a'],
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
