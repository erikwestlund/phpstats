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
        'phibble' => ['repo' => 'phpstats/phibble', 'ref' => '7ebf06835bab65ca8facde2ab0165ba1b995cc12'],
        'phpolars' => ['repo' => 'phpstats/phpolars', 'ref' => 'dda391fb5e233c6ab39d5517fb9a2589618a9b27'],
        'phplyr' => ['repo' => 'phpstats/phplyr', 'ref' => '0c2d613ac44a62dd750e810ae7b5cd4cc7acafc8'],
        'phit' => ['repo' => 'phpstats/phit', 'ref' => '5690153bc6fa8bb0058aad21b267d0a850eec8a3'],
        'phiz' => ['repo' => 'phpstats/phiz', 'ref' => '3956fe5a9ec2fff248b97b28d69c451074e33107'],
        'cmdstanphp' => ['repo' => 'phpstats/CmdStanPHP', 'ref' => '4b89c39283f78133431f1d4981f9e97229c08d68'],
        'juphyter' => ['repo' => 'phpstats/juphyter', 'ref' => '82e8fbee5f0b4680491d169596697f1ed0b74af7'],
    ],

    /*
     * How the packages fit together. The landing page
     * draws these layers, and each package host shows its layer and color.
     * Colors are Tailwind color names, and icons are Heroicons. packages
     * gives the order in the menus, the footer, and the landing page; each
     * package's short description is its summary in config/projects.php.
     */
    'layers' => [
        'data' => ['title' => 'Data & Workflow', 'role' => 'Tables, pipelines, and notebooks', 'color' => 'indigo', 'icon' => 'table-cells', 'packages' => ['phibble', 'phplyr', 'juphyter', 'phpolars']],
        'stats' => ['title' => 'Statistics, Modeling & Visualization', 'role' => 'Models, inference, and charts', 'color' => 'purple', 'icon' => 'variable', 'packages' => ['phit', 'phiz', 'cmdstanphp']],
    ],

    /*
     * The landing page's package explorer: an introduction of two sentences
     * (what the code does, then what its output shows, as inline Markdown),
     * the title of its example, the label for its output, the docs path of
     * the fuller example its "View full example" link opens, with a
     * #fragment when it is one section of a page (the docs index when
     * that page has not synced), and, for examples pages can't run, a stored
     * result: the callable that
     * gives it under result, and under files the paths it is computed from,
     * whose modification times go in the output's cache key, so re-capturing
     * one is enough. Each package's code is resources/examples/packages/{key}.php,
     * unless the entry gives its own source file and language, as juphyter's
     * Quarto document does. A package with no entry here gets no panel.
     */
    'explorer' => [
        'phibble' => [
            'intro' => 'Loads the orders table from an Eloquent query into a data frame, with `channel` read as a factor and `coupon` and `returned` as logical columns. The glimpse lists every column with its type and first values.',
            'example' => 'Example: Load an Eloquent query with typed columns',
            'output' => 'Data frame glimpse',
            'link' => 'io#databases',
        ],
        'phpolars' => [
            'intro' => 'Reads three columns of the orders table into Polars, drops returned orders, and counts the orders and sums the revenue of each channel. The result is a Polars table sorted by revenue, highest first.',
            'example' => 'Example: Aggregate database rows with expressions',
            'output' => 'Polars table',
            'link' => 'examples/expressions',
        ],
        'phplyr' => [
            'intro' => 'Keeps the orders that were not returned, groups them by channel, and computes each channel\'s order count, total revenue, and average order value. The result is a data frame with one row per channel.',
            'example' => 'Example: Filter, group, and summarize orders',
            'output' => 'Data frame preview',
            'link' => 'examples/group-and-summarize',
        ],
        'juphyter' => [
            'intro' => 'A Quarto document runs a PHP cell that summarizes the orders by channel with phplyr. The kernel displays the cell\'s final value, a data frame, as the HTML table shown.',
            'example' => 'Example: A PHP cell in a Quarto document',
            'output' => 'Rendered notebook cell',
            'link' => 'examples/notebook',
            'source' => ['file' => 'packages/juphyter.qmd', 'language' => 'quarto'],
            'stored' => [
                'result' => [\App\Data\JuphyterCell::class, 'explorerOutput'],
                'files' => [\App\Data\JuphyterCell::path()],
            ],
        ],
        'phiz' => [
            'intro' => 'Plots streams against playlist adds for each track, colored by label, and adds a least-squares line with its confidence band for each label. `embed()` renders the chart as HTML.',
            'example' => 'Example: Points with a fitted line per group',
            'output' => 'Chart',
            'link' => 'examples/layers',
        ],
        'phit' => [
            'intro' => 'Fits a linear model of streams on playlist adds and record label with `fit_lm()`, which returns the fit as an array. The table lists each coefficient\'s estimate, standard error, and p-value.',
            'example' => 'Example: Fit a linear model',
            'output' => 'Coefficients',
            'link' => 'examples/linear-model',
        ],
        'cmdstanphp' => [
            'intro' => 'Builds Stan data from a formula with a random intercept per school and samples four chains from the posterior. The table summarizes the coefficients (`beta`), the residual SD (`sigma`), and the between-school SD (`tau`), with R-hat and bulk effective sample size.',
            'example' => 'Example: Sample a hierarchical model and summarize it',
            'output' => 'Posterior summary',
            'link' => 'examples/sample',
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
     * Docs sidebar groups that start collapsed, so the learning path stays
     * short. A group opens when it holds the current page.
     */
    'collapsed_docs_groups' => ['Benchmarks and verification', 'Mathematics', 'History', 'Contributing'],

    /*
     * Vignettes written as Livewire components in this app. Synced packages
     * add their own from site/vignettes. The first package owns the page;
     * the others list it too.
     */
    'vignettes' => [
        'school-shrinkage' => ['packages' => ['phit'], 'title' => 'School effects and shrinkage', 'summary' => 'A linear mixed model with random school intercepts, variance components, shrinkage, and what-if predictions.'],
        'bayesian-schools' => ['packages' => ['cmdstanphp', 'phit'], 'title' => 'Bayesian school effects', 'summary' => 'The school effects model in Stan through CmdStanPHP, with posterior intervals next to Phit\\fit_lmer().'],
        'rent-dashboard' => ['packages' => ['phiz', 'phplyr', 'phit'], 'title' => 'Rent dashboard', 'summary' => 'A Livewire dashboard: filters, a phplyr summary, two phiz charts, a logistic model, and what-if predictions.'],
    ],

    /*
     * Examples that left the site's live pages or a package's site/vignettes
     * in the 2026-10-31 curation, and the package docs page that replaced
     * each: [package, docs path]. /vignettes/{slug} redirects there.
     */
    'example_redirects' => [
        'simulated-rents' => ['phit', 'examples/linear-model'],
        'rent-model' => ['phit', 'examples/linear-model'],
        'school-scores' => ['phit', 'examples/mixed-model'],
        'school-effects' => ['phit', 'examples/mixed-model'],
        'random-effects' => ['phit', 'examples/mixed-model'],
        'glm-models' => ['phit', 'examples/glm'],
        'distributions' => ['phit', 'examples/distributions'],
        'csv-reader' => ['phibble', 'examples/read-a-csv'],
        'sql-pipeline' => ['phibble', 'examples/database-table'],
        'reshaping' => ['phplyr', 'examples/pivot'],
        'port-r-script' => ['phplyr', 'port-r-script'],
        'backends' => ['phplyr', 'performance'],
        'polars-pipeline' => ['phpolars', 'phibble'],
        'phplyr-on-polars' => ['phpolars', 'phibble'],
        'lazy-queries' => ['phpolars', 'examples/lazy-csv'],
        'time-series' => ['phpolars', 'examples/time-series'],
    ],

];
