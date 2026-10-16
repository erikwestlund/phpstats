<x-layout title="SQL pipeline">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">SQL pipeline</h1>
        <p class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
            This page uses the simulated rentals data. It does not describe real rentals.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            This vignette runs one phplyr pipeline in two ways.
            On a lazy phibble table, created with ->phibble() on an Eloquent query, the verbs build a single SQL query that the database runs.
            On an in-memory frame, created with ->toFrame(), the same verbs run in PHP.
            The page shows the generated SQL and both results.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The pipeline describes columns with col() expressions, which phibble can translate to SQL.
            A PHP closure in a verb would make phibble load the rows into memory first.
            Eloquent casts and accessors do not apply to a lazy table, because its rows come straight from SQL.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>$pipeline = fn ($rentals) => $rentals
    |> filter(col('bedrooms')->ge($minBedrooms))
    |> mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2')))
    |> group_by('neighborhood')
    |> summarise(rentals: n(), mean_rent_per_m2: mean('rent_per_m2'))
    |> arrange(desc('mean_rent_per_m2'));

$sql = SimulatedRental::query()->phibble() |> $pipeline;
$sql |> show_query();

$memory = SimulatedRental::query()->toFrame() |> $pipeline;</code></pre>
    </article>

    <div class="mt-10">
        <livewire:sql-pipeline />
    </div>
</x-layout>
