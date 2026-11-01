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
        'phibble' => ['repo' => 'phpstats/phibble', 'ref' => '348e1d4ac54f02099bb24a6a6dba760f3a230cc4'],
        'phpolars' => ['repo' => 'phpstats/phpolars', 'ref' => '067cae1634004ecb5502e24e4689c2f00066f53a'],
        'phplyr' => ['repo' => 'phpstats/phplyr', 'ref' => '0c2d613ac44a62dd750e810ae7b5cd4cc7acafc8'],
        'phit' => ['repo' => 'phpstats/phit', 'ref' => '82b31e5ab393dfefbf8804a9a089fb6563a84462'],
        'phiz' => ['repo' => 'phpstats/phiz', 'ref' => '3956fe5a9ec2fff248b97b28d69c451074e33107'],
        'cmdstanphp' => ['repo' => 'phpstats/CmdStanPHP', 'ref' => 'da0fb7828cecad85013796bb899a4b1d2c2dcc38'],
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
     * The landing page's benchmark workloads, in selector order: the package
     * whose synced benchmark record holds the saved results, the record's
     * id, the selector label, display names for the record's
     * implementations where its own are terse, the task when the record does
     * not describe its workload, and an optional note. A workload whose record has not synced is left out.
     */
    'benchmarks' => [
        ['package' => 'phit', 'id' => 'distributions-workload', 'label' => 'Distributions', 'names' => ['R' => 'R', 'SciPy' => 'SciPy (Python)'], 'note' => null],
        ['package' => 'phpolars', 'id' => 'csv-pipeline', 'label' => 'CSV processing', 'task' => 'Keep the completed orders, then total them by region and month.', 'note' => null],
        ['package' => 'phit', 'id' => 'ols-workload', 'label' => 'OLS', 'names' => ['R' => 'lm (R)', 'statsmodels' => 'statsmodels (Python)'], 'note' => null],
        ['package' => 'phit', 'id' => 'logistic-workload', 'label' => 'Logistic', 'names' => ['R' => 'glm (R)', 'statsmodels' => 'statsmodels (Python)'], 'note' => null],
        ['package' => 'phit', 'id' => 'probit-workload', 'label' => 'Probit', 'names' => ['R' => 'glm (R)', 'statsmodels' => 'statsmodels (Python)'], 'note' => null],
        ['package' => 'phit', 'id' => 'mixed-workload', 'label' => 'Mixed Gaussian', 'names' => ['R' => 'lme4 (R)', 'statsmodels' => 'statsmodels (Python)'], 'note' => null],
        ['package' => 'phit', 'id' => 'mixed-logistic-workload', 'label' => 'Mixed logistic', 'names' => ['R' => 'lme4 (R)', 'GPBoost' => 'GPBoost (Python)'], 'note' => null],
        ['package' => 'cmdstanphp', 'id' => 'interfaces-workload', 'label' => 'Mixed Gaussian (Bayesian)', 'names' => ['CmdStanR' => 'CmdStanR (R)', 'CmdStanPy' => 'CmdStanPy (Python)'], 'note' => null],
    ],

    /*
     * The landing page's package explorer: the title of its example, the label for its output, the docs path of
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
            'example' => 'Example: Load an Eloquent query with typed columns',
            'output' => 'Data frame glimpse',
            'link' => 'io#databases',
        ],
        'phpolars' => [
            'example' => 'Example: Aggregate database rows with expressions',
            'output' => 'Polars table',
            'link' => 'examples/expressions',
        ],
        'phplyr' => [
            'example' => 'Example: Filter, group, and summarize orders',
            'output' => 'Data frame preview',
            'link' => 'examples/group-and-summarize',
        ],
        'juphyter' => [
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
            'example' => 'Example: Points with a fitted line per group',
            'output' => 'Chart',
            'link' => 'examples/layers',
        ],
        'phit' => [
            'example' => 'Example: Fit a linear model',
            'output' => 'Coefficients',
            'link' => 'examples/linear-model',
        ],
        'cmdstanphp' => [
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
