<x-layout title="Polars pipeline">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Polars pipeline</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            phplyr works on any phibble table.
            This vignette runs the pipeline from the SQL vignette on an in-memory DataFrame and on a PolarsTable, which holds the data in Polars through the php_polars extension from phpolars.
            The steps use col() expressions, so the Polars side runs them in Polars instead of in PHP.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The input stacks copies of the 3,000 simulated rentals, up to 300,000 rows.
            Polars runs the steps quickly, and the time to copy data in and out of Polars matters more at small sizes.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <x-code-tabs class="mt-3">
            <x-slot:chain>$pipeline = fn (Table $rentals) => phplyr($rentals)
    ->filter(col('bedrooms')->ge($minBedrooms))
    ->mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2')))
    ->groupBy('neighborhood')
    ->summarise(rentals: n(), mean_rent_per_m2: mean('rent_per_m2'))
    ->arrange(desc('mean_rent_per_m2'))
    ->get();

$inMemory = $pipeline($df);
$inPolars = $pipeline(PolarsTable::fromTable($df));</x-slot:chain>
            <x-slot:pipe>$pipeline = fn (Table $rentals) => $rentals |>
    filter(col('bedrooms')->ge($minBedrooms)) |>
    mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2'))) |>
    group_by('neighborhood') |>
    summarise(rentals: n(), mean_rent_per_m2: mean('rent_per_m2')) |>
    arrange(desc('mean_rent_per_m2'));

$inMemory = $df |> $pipeline;
$inPolars = PolarsTable::fromTable($df) |> $pipeline;</x-slot:pipe>
        </x-code-tabs>
    </article>

    <div class="mt-10">
        <livewire:polars-pipeline />
    </div>
</x-layout>
