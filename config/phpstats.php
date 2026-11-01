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
        'phibble' => ['repo' => 'phpstats/phibble', 'ref' => '61131e6f6d5336b88ef8a9eeed858afd045b971a'],
        'phpolars' => ['repo' => 'phpstats/phpolars', 'ref' => '1f2d1469e1b848ff0c00c09e2dc23299c3202448'],
        'phplyr' => ['repo' => 'phpstats/phplyr', 'ref' => '5427a9a943ec679910406fe02754027c13fa6fbb'],
        'phit' => ['repo' => 'phpstats/phit', 'ref' => 'ded53a2b4707f6653bba528d4cd8cb71315cc6e7'],
        'phiz' => ['repo' => 'phpstats/phiz', 'ref' => '87cb7e013ba15dd1fa345cfb543d3d844bc5c101'],
        'cmdstanphp' => ['repo' => 'phpstats/CmdStanPHP', 'ref' => '66709d675ae297cfaa7bc327ca98bd1a07ef8236'],
        'juphyter' => ['repo' => 'phpstats/juphyter', 'ref' => '8521e942ab731888f66f405e9e942180aee8c76d'],
    ],

    /*
     * How the packages fit together. The landing page
     * draws these layers, and each package host shows its layer and color.
     * Colors are Tailwind color names, and icons are Heroicons. packages
     * gives the order in the menus, the footer, and the landing page; each
     * package's short description is its summary in config/projects.php.
     */
    'layers' => [
        'data' => ['title' => 'Data & Workflow', 'role' => 'Tables, pipelines, and notebooks', 'color' => 'indigo', 'icon' => 'table-cells', 'packages' => ['phibble', 'phplyr', 'phpolars', 'juphyter']],
        'stats' => ['title' => 'Statistics, Modeling & Visualization', 'role' => 'Models, inference, and charts', 'color' => 'purple', 'icon' => 'variable', 'packages' => ['phit', 'phiz', 'cmdstanphp']],
    ],

    /*
     * Pages in a package's docs that collect more examples, such as a
     * gallery, listed on /examples after that package's curated examples. A
     * page that has not synced is left out.
     */
    'example_collections' => [
        'phibble' => [
            ['path' => 'laravel', 'title' => 'Analyze Eloquent models', 'description' => 'Turn Eloquent queries and collections into data frames, then summarize and chart them.'],
        ],
        'phplyr' => [
            ['path' => 'port-r-script', 'title' => 'Port an R script', 'description' => 'Translate an R analysis that cleans, reshapes, and models data.'],
            ['path' => 'gallery', 'title' => 'Analysis gallery', 'description' => 'Short analyses that combine data pipelines with model fits.'],
        ],
        'phit' => [
            ['path' => 'missing-data', 'title' => 'Account for missing values', 'description' => 'See how fits handle incomplete rows and report the rows they used.'],
        ],
        'phiz' => [
            ['path' => 'dashboard', 'title' => 'Build a Livewire dashboard', 'description' => 'Link several charts to the filters of one Livewire component.'],
            ['path' => 'gallery', 'title' => 'Chart gallery', 'description' => 'Browse charts by type, from distributions and heatmaps to linked brushing.'],
        ],
        'cmdstanphp' => [
            ['path' => 'examples', 'title' => 'Bundled Stan models', 'description' => 'Fit the example models that ship with CmdStanPHP.'],
        ],
    ],

    /*
     * Benchmark machines' CPUs as the records name them, each with the name
     * of its cores, so the landing page says "2 AMD Zen 5 cores".
     */
    'cpus' => [
        'AMD Ryzen 9 9955HX 16-Core Processor' => 'AMD Zen 5',
    ],

    /*
     * The landing page's benchmark workloads, in selector order: the package
     * whose synced benchmark record holds the saved results, the record's
     * id, the selector label, display names for the record's
     * implementations where its own are terse, the task when the record does
     * not describe its workload, and an optional note. A workload whose record has not synced is left out.
     */
    'benchmarks' => [
        ['package' => 'phit', 'id' => 'distributions-workload', 'label' => 'Distributions', 'names' => ['R' => 'R', 'SciPy' => 'SciPy (Python)'], 'note' => 'For easier installation, use phit-polyfill. For faster computation, use the phit extension.'],
        ['package' => 'phpolars', 'id' => 'csv-pipeline', 'label' => 'CSV processing', 'task' => 'Keep the completed orders, then total them by region and month.', 'note' => null],
        ['package' => 'phit', 'id' => 'ols-workload', 'label' => 'OLS', 'names' => ['R' => 'lm (R)', 'statsmodels' => 'statsmodels (Python)'], 'note' => 'For easier installation, use phit-polyfill. For faster computation, use the phit extension.'],
        ['package' => 'phit', 'id' => 'logistic-workload', 'label' => 'Logistic', 'names' => ['R' => 'glm (R)', 'statsmodels' => 'statsmodels (Python)'], 'note' => 'For easier installation, use phit-polyfill. For faster computation, use the phit extension.'],
        ['package' => 'phit', 'id' => 'probit-workload', 'label' => 'Probit', 'names' => ['R' => 'glm (R)', 'statsmodels' => 'statsmodels (Python)'], 'note' => 'For easier installation, use phit-polyfill. For faster computation, use the phit extension.'],
        ['package' => 'phit', 'id' => 'mixed-workload', 'label' => 'Mixed Gaussian', 'names' => ['R' => 'lme4 (R)', 'statsmodels' => 'statsmodels (Python)'], 'note' => 'For easier installation, use phit-polyfill. For faster computation, use the phit extension.'],
        ['package' => 'phit', 'id' => 'mixed-logistic-workload', 'label' => 'Mixed logistic', 'names' => ['R' => 'lme4 (R)', 'GPBoost' => 'GPBoost (Python)'], 'note' => 'For easier installation, use phit-polyfill. For faster computation, use the phit extension.'],
        ['package' => 'cmdstanphp', 'id' => 'interfaces-workload', 'label' => 'Mixed Gaussian (Bayesian)', 'names' => ['CmdStanR' => 'CmdStanR (R)', 'CmdStanPy' => 'CmdStanPy (Python)'], 'note' => null],
    ],

    /*
     * The landing page's package explorer: a plain sentence on the tasks the
     * package handles (shown in the list), the title of its example, the label for its output, the docs path of
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
            'tasks' => 'Load CSV files and database queries into typed tables you can inspect, filter, sort, and join. Similar to R\'s tibble and readr.',
            'example' => 'Example: Load an Eloquent query with typed columns',
            'output' => 'Data frame glimpse',
            'link' => 'io#databases',
        ],
        'phpolars' => [
            'tasks' => 'Process large tables quickly in the Polars engine, from CSV, Parquet, or SQL, through a PHP extension. Similar to Python\'s Polars.',
            'example' => 'Example: Aggregate database rows with expressions',
            'output' => 'Polars table',
            'link' => 'examples/expressions',
        ],
        'phplyr' => [
            'tasks' => 'Filter, group, summarize, join, and reshape tables as a pipeline of steps. Similar to R\'s dplyr, tidyr, and janitor.',
            'example' => 'Example: Filter, group, and summarize orders',
            'output' => 'Data frame preview',
            'link' => 'examples/group-and-summarize',
        ],
        'juphyter' => [
            'tasks' => 'Run PHP in Jupyter notebooks and Quarto documents, with tables and charts shown inline. Similar to R\'s IRkernel.',
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
            'tasks' => 'Build charts layer by layer from your tables, and save or embed them as Vega-Lite. Similar to R\'s ggplot2.',
            'example' => 'Example: Points with a fitted line per group',
            'output' => 'Chart',
            'link' => 'examples/layers',
        ],
        'phit' => [
            'tasks' => 'Fit linear, generalized linear, and mixed models, and compute probabilities and summary statistics. Similar to R\'s stats and lme4.',
            'example' => 'Example: Fit a linear model',
            'output' => 'Coefficients',
            'link' => 'examples/linear-model',
        ],
        'cmdstanphp' => [
            'tasks' => 'Fit Bayesian models written in Stan and summarize their posterior draws. A port of R\'s CmdStanR.',
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
