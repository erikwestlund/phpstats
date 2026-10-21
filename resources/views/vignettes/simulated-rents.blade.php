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
            The page fits a linear regression with phit and compares each estimate with the value used to generate the data.
            Coefficient names follow R, so the Old Town effect is neighborhoodOld Town.
            Smaller samples give wider confidence intervals.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            phibble reads the table through Eloquent and phplyr summarises it. phplyr's lm() passes the formula and the data to phit, which builds the model matrix as R does.
            Setting the factor levels makes Riverside the reference neighborhood.
        </p>
        <x-code-tabs class="mt-3">
            <x-slot:chain>$rentals = phplyr(DataFrame::fromQuery(SimulatedRental::query()->orderBy('id'))
        ->asFactor('neighborhood', ['Riverside', 'Old Town', 'Hillcrest', 'Harbor', 'University', 'Northgate']))
    ->head($n)
    ->get();

$byNeighborhood = phplyr($rentals)
    ->groupBy('neighborhood')
    ->summarise(rentals: n(), mean_area: mean('area_m2'), mean_rent: mean('monthly_rent'))
    ->arrange('neighborhood')
    ->get();

$fit = phplyr($rentals)->lm('monthly_rent ~ area_m2 + bedrooms + neighborhood');</x-slot:chain>
            <x-slot:pipe>$rentals = DataFrame::fromQuery(SimulatedRental::query()->orderBy('id'))
    ->asFactor('neighborhood', ['Riverside', 'Old Town', 'Hillcrest', 'Harbor', 'University', 'Northgate']) |>
    head($n);

$byNeighborhood = $rentals |>
    group_by('neighborhood') |>
    summarise(rentals: n(), mean_area: mean('area_m2'), mean_rent: mean('monthly_rent')) |>
    arrange('neighborhood');

$fit = $rentals |> lm('monthly_rent ~ area_m2 + bedrooms + neighborhood');</x-slot:pipe>
        </x-code-tabs>
    </article>

    <div class="mt-10">
        <livewire:simulated-rents />
    </div>
</x-layout>
