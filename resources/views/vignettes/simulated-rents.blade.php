<x-layout title="Simulated rents">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Simulated rents</h1>
        <p class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
            The data on this page is simulated. It does not describe real rentals.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The app's database holds {{ number_format(\App\Data\SimulatedRentals::ROWS) }} simulated rentals in six neighborhoods.
            Each monthly rent was generated from known coefficients for floor area, bedrooms, and neighborhood, plus normal noise with a standard deviation of {{ number_format(\App\Data\SimulatedRentals::SIGMA) }}.
            Riverside is the reference neighborhood.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The page fits a linear regression with php-stats and compares each estimate with the value used to generate the data.
            Smaller samples give wider confidence intervals.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            phibble reads the table through Eloquent, and phplyr verbs prepare the data. The page builds neighborhood indicators with mutate() and passes the design matrix to stats_ols().
        </p>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>$rentals = Frame::fromQuery(SimulatedRental::query()->orderBy('id'))
    |> head($n);

$byNeighborhood = $rentals
    |> group_by('neighborhood')
    |> summarise(rentals: n(), mean_area: mean('area_m2'), mean_rent: mean('monthly_rent'))
    |> arrange('neighborhood');

$design = $rentals |> mutate(
    is_old_town: fn ($r) => $r->neighborhood === 'Old Town',
    // ... one indicator per neighborhood except Riverside
);

$fit = stats_ols(
    x: $design->toMatrix(['area_m2', 'bedrooms', 'is_old_town', ...]),
    y: $design->toVector('monthly_rent'),
);</code></pre>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            When php-stats provides stats_lm(), phplyr's lm() will replace the indicator columns and the stats_ols() call:
        </p>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>$fit = $rentals |> lm('monthly_rent ~ area_m2 + bedrooms + neighborhood');</code></pre>
    </article>

    <div class="mt-10">
        <livewire:simulated-rents />
    </div>
</x-layout>
