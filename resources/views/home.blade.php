<x-layout>
    <section class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">PHPstats</h1>
        <p class="mt-2 text-xl text-zinc-700 dark:text-zinc-300">Modern statistical computing for PHP.</p>
        <p class="mt-6 text-zinc-700 dark:text-zinc-300">
            PHPstats provides a family of libraries for data analysis in native PHP.
            It is inspired by the Tidyverse in R.
            Each package does one job, the packages share one data frame type, and they compose with the PHP 8.5 pipe operator.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The packages read data directly from the application database through PDO, Eloquent, or Doctrine, and they fit statistical models whose results are checked against R.
            Dashboards and reports run in the same Laravel app, with no R or Python server and no hand-written JavaScript.
        </p>
    </section>

    <section class="mt-10">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">A pipeline</h2>
        <x-code-tabs class="mt-3">
            <x-slot:chain>$fit = phplyr(DataFrame::fromQuery(Sale::query()))
    ->filter(fn ($r) => $r->price > 0)
    ->mutate(log_price: fn ($r) => log($r->price))
    ->lm('log_price ~ area + neighborhood');</x-slot:chain>
            <x-slot:pipe>$fit = DataFrame::fromQuery(Sale::query())
    |> filter(fn ($r) => $r->price > 0)
    |> mutate(log_price: fn ($r) => log($r->price))
    |> lm('log_price ~ area + neighborhood');</x-slot:pipe>
        </x-code-tabs>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            phibble loads the query into a frame. phplyr filters and transforms it, and lm() fits the model with php-stats.
        </p>
    </section>

    <section class="mt-12">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Packages</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($projects as $key => $project)
                <a href="{{ route('project', $key) }}"
                   class="block rounded-lg border border-zinc-200 p-5 hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold">{{ $project['name'] }}</span>
                        <x-status :project="$project" />
                    </div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $project['summary'] }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-12">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Vignettes</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Each vignette fits its models on the server when the page loads or an input changes.</p>
        <ul class="mt-4 space-y-3">
            @foreach ([
                'vignettes.rent-dashboard' => ['Rent dashboard', 'A reactive Livewire dashboard: filters, a phplyr summary, two phiz charts, and a logistic model.'],
                'vignettes.simulated-rents' => ['Simulated rents', 'Linear regression with a factor, compared with the coefficients that generated the data.'],
                'vignettes.school-scores' => ['School scores', 'Linear regression with a choice of predictors and an observed-against-fitted chart.'],
                'vignettes.glm-models' => ['Binary and count outcomes', 'Logistic, probit, and Poisson regression from R formulas, with predictions and confidence bands.'],
                'vignettes.school-effects' => ['School effects', 'A linear mixed model with random school intercepts, variance components, and shrinkage.'],
                'vignettes.random-effects' => ['Random-effect intervals', 'A caterpillar plot of school effects with intervals from their conditional variances.'],
                'vignettes.bayesian-schools' => ['Bayesian school effects', 'The school effects model in Stan through cmdstanphp, with posterior intervals next to stats_lmer.'],
                'vignettes.sql-pipeline' => ['SQL pipeline', 'One phplyr pipeline run as a single SQL query and in memory, with the generated SQL.'],
                'vignettes.polars-pipeline' => ['Polars pipeline', 'The same pipeline on an in-memory frame and in Polars, with timings for up to 300,000 rows.'],
                'vignettes.csv-reader' => ['Reading CSV files', 'The native CSV reader on editable text: guessed types, parse failures, problems, and agreement with phibble.'],
            ] as $route => [$title, $description])
                <li>
                    <a href="{{ route($route) }}" class="font-medium underline">{{ $title }}</a>
                    <span class="text-zinc-600 dark:text-zinc-400"> {{ $description }}</span>
                </li>
            @endforeach
        </ul>
    </section>
</x-layout>
