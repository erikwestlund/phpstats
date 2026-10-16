<x-layout>
    <section class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">PHP Stats</h1>
        <p class="mt-4 text-lg text-zinc-700 dark:text-zinc-300">
            PHP Stats is a small family of PHP 8.5 packages for statistical work, modeled on the tidyverse.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The packages fit real statistical models in PHP. They read data directly from the application database through PDO, Eloquent, or Doctrine.
            Dashboards and reports run in the same Laravel app, with no R or Python server and no hand-written JavaScript.
        </p>
    </section>

    <section class="mt-10">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">A pipeline</h2>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>$fit = Frame::fromQuery(Sale::query())
    |> filter(fn ($r) => $r->price > 0)
    |> mutate(log_price: fn ($r) => log($r->price))
    |> lm('log_price ~ area + neighborhood');</code></pre>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            phibble loads the query into a frame. phplyr filters and transforms it, and lm() fits the model with php-stats.
        </p>
    </section>

    <section class="mt-12">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Vignettes</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Each vignette fits its models on the server when the page loads or an input changes.</p>
        <ul class="mt-4 space-y-3">
            @foreach ([
                'vignettes.simulated-rents' => ['Simulated rents', 'Linear regression with a factor, compared with the coefficients that generated the data.'],
                'vignettes.school-scores' => ['School scores', 'Linear regression with a choice of predictors and an observed-against-fitted chart.'],
                'vignettes.school-effects' => ['School effects', 'A linear mixed model with random school intercepts, variance components, and shrinkage.'],
                'vignettes.sql-pipeline' => ['SQL pipeline', 'One phplyr pipeline run as a single SQL query and in memory, with the generated SQL.'],
            ] as $route => [$title, $description])
                <li>
                    <a href="{{ route($route) }}" class="font-medium underline">{{ $title }}</a>
                    <span class="text-zinc-600 dark:text-zinc-400"> {{ $description }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="mt-12">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Projects</h2>
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
</x-layout>
