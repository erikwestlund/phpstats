@php $packages = \App\Content\Package::all(); @endphp
<x-layout :wide="true">
    <section class="max-w-3xl">
        <h1 class="text-4xl font-semibold tracking-tight">PHPstats</h1>
        <p class="mt-2 text-xl text-zinc-700 dark:text-zinc-300">Modern statistical computing for PHP.</p>
        <p class="mt-6 text-zinc-700 dark:text-zinc-300">
            PHPstats is a family of libraries for data analysis in native PHP.
            It is inspired by the Tidyverse in R.
            Each package does one job, the packages share one data frame type, and they compose with method chains or the PHP 8.5 pipe operator.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            Many teams already keep their data in a PHP application.
            To analyze it, they export it to R or Python, or they run a separate service next to the app.
            PHPstats fits the models where the data already is.
            It reads tables from the application database through Eloquent, Doctrine, or PDO, and it fits regression and mixed models whose results are checked against R.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            With Laravel and Livewire, the same code drives interactive dashboards and reports, which makes PHPstats an alternative to Shiny for PHP teams.
            Inputs are component properties, the server refits the model when one changes, and phiz draws the charts.
            The page needs no separate R or Python server and no hand-written JavaScript.
        </p>
    </section>

    <section class="mt-14">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">How the packages fit together</h2>
        @php
            $layers = [
                ['Data', 'Tables from CSV files, the database, or Polars', ['phibble' => 'The shared data frame: typed, immutable, and loaded from CSV, PDO, Eloquent, or Doctrine', 'phpolars' => 'Polars data frames in a Rust extension, and a fast backend for phibble']],
                ['Verbs', 'Filter, mutate, group, summarise, join, and reshape', ['phplyr' => 'dplyr and tidyr verbs that run in memory, as SQL, or in Polars']],
                ['Models', 'Estimation, verified against R', ['phit' => 'Regression, mixed models, distributions, and descriptive statistics in a Rust extension', 'phit-polyfill' => 'The phit functions in plain PHP, for hosts that cannot install extensions', 'cmdstanphp' => 'Bayesian models in Stan through CmdStan']],
                ['Graphics', 'A grammar of graphics', ['phiz' => 'ggplot2-style charts that render as Vega-Lite, with a Blade component for Livewire']],
            ];
        @endphp
        <div class="mt-4 space-y-3">
            @foreach ($layers as $i => [$layer, $role, $members])
                <div class="grid gap-3 rounded-lg border border-zinc-200 p-4 sm:grid-cols-[10rem_1fr] dark:border-zinc-800">
                    <div>
                        <p class="font-semibold">{{ $layer }}</p>
                        <p class="text-sm text-zinc-500">{{ $role }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($members as $key => $description)
                            <a href="{{ $packages[$key]->url() }}" class="block rounded-md bg-zinc-50 px-3 py-2 hover:bg-zinc-100 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                                <span class="font-medium">{{ $packages[$key]->name() }}</span>
                                <span class="block text-sm text-zinc-600 dark:text-zinc-400">{{ $description }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                <p class="text-center text-zinc-400" aria-hidden="true">↓</p>
            @endforeach
            <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-400">
                Your application: Laravel controllers, Livewire dashboards, reports, and jobs
            </div>
        </div>
    </section>

    <section class="mt-14 max-w-3xl">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">From the database to a chart</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            phibble reads the query, phplyr filters and transforms it, phit fits the model, and phiz draws the chart.
        </p>
        <x-code-tabs class="mt-3">
            <x-slot:chain>use Phibble\DataFrame;
use Phiz\Plot;
use function Phplyr\{col, phplyr};

$rentals = phplyr(DataFrame::fromQuery(Rental::query()))
    ->filter(col('bedrooms')->le(3))
    ->mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2')))
    ->get();

$fit = phplyr($rentals)->lm('monthly_rent ~ area_m2 + bedrooms + neighborhood');
$fit['named'];  // coefficients by term, as in R's coef()

$chart = Plot::of($rentals)
    ->aes(x: 'area_m2', y: 'monthly_rent', color: 'neighborhood')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm');

// In a Blade view: &lt;x-phiz :plot="$chart" /&gt;</x-slot:chain>
            <x-slot:pipe>use Phibble\DataFrame;
use Phiz\Plot;
use function Phplyr\{col, filter, lm, mutate};

$rentals = DataFrame::fromQuery(Rental::query())
    |> filter(col('bedrooms')->le(3))
    |> mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2')));

$fit = $rentals |> lm('monthly_rent ~ area_m2 + bedrooms + neighborhood');
$fit['named'];  // coefficients by term, as in R's coef()

$chart = Plot::of($rentals)
    ->aes(x: 'area_m2', y: 'monthly_rent', color: 'neighborhood')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm');

// In a Blade view: &lt;x-phiz :plot="$chart" /&gt;</x-slot:pipe>
        </x-code-tabs>
    </section>

    <section class="mt-14">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Packages</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($packages as $package)
                <a href="{{ $package->url() }}"
                   class="block rounded-lg border border-zinc-200 p-5 hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold">{{ $package->name() }}</span>
                        <x-status :project="$package->project" />
                    </div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $package->tagline() }}</p>
                    <p class="mt-3 text-xs text-zinc-500">{{ preg_replace('#^https?://#', '', $package->url()) }}</p>
                </a>
            @endforeach
        </div>
    </section>
</x-layout>
