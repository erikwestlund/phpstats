@php
    $packages = \App\Content\Package::all();
    $stats = \App\Content\Stats::family();
    // The "What you can do" tabs are Markdown files in resources/pages/home, in file name order.
    $tabs = collect(glob(resource_path('pages/home/*.md')))->sort()->map(fn (string $file) => [
        'id' => \Illuminate\Support\Str::slug(pathinfo($file, PATHINFO_FILENAME)),
        ...\App\Content\Markdown::page($file),
    ])->values();
@endphp
<x-layout :bare="true">
    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-zinc-950 text-white">
        <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="absolute -top-40 left-1/2 -z-10 h-[36rem] w-[64rem] -translate-x-1/2 rounded-full bg-gradient-to-r from-php-600/40 via-violet-600/30 to-fuchsia-600/20 blur-3xl" aria-hidden="true"></div>

        <div class="mx-auto grid max-w-7xl items-center gap-14 px-4 pb-20 pt-16 *:min-w-0 sm:px-6 lg:grid-cols-[1fr_1.1fr] lg:px-8 lg:pb-28 lg:pt-24">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white/5 px-3 py-1 text-xs font-medium text-php-200 ring-1 ring-white/10">
                    <span class="size-1.5 rounded-full bg-fuchsia-400"></span>
                    Inspired by the Tidyverse in R
                </p>
                <h1 class="mt-6 text-4xl font-semibold tracking-tight sm:text-6xl">
                    Modern statistical computing
                    <span class="block bg-gradient-to-r from-php-300 via-violet-300 to-fuchsia-300 bg-clip-text text-transparent">for PHP</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-zinc-300">
                    PHPstats is a family of libraries for data analysis in native PHP.
                    Read data from your application, transform it, fit models checked against R, and draw charts, all in the code you already deploy.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="#packages" class="rounded-full bg-php-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-php-500/30 transition hover:bg-php-400">Explore the packages</a>
                    <a href="{{ route('page', 'about') }}" class="rounded-full px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/10">Why PHPstats</a>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-php-500/20 via-violet-500/10 to-transparent blur-2xl" aria-hidden="true"></div>
                <x-code-tabs label="RentReport.php" class="shadow-2xl shadow-black/40 ring-white/10!">
                    <x-slot:chain>use Phibble\DataFrame;
use Phiz\Plot;
use function Phplyr\{col, phplyr};

$rentals = phplyr(DataFrame::fromQuery(Rental::query()))
    ->filter(col('bedrooms')->le(3))
    ->mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2')))
    ->get();

$fit = phplyr($rentals)->lm('monthly_rent ~ area_m2 + bedrooms + neighborhood');

$chart = Plot::of($rentals)
    ->aes(x: 'area_m2', y: 'monthly_rent', color: 'neighborhood')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm');</x-slot:chain>
                    <x-slot:pipe>use Phibble\DataFrame;
use Phiz\Plot;
use function Phplyr\{col, filter, lm, mutate};

$rentals = DataFrame::fromQuery(Rental::query())
    |> filter(col('bedrooms')->le(3))
    |> mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2')));

$fit = $rentals |> lm('monthly_rent ~ area_m2 + bedrooms + neighborhood');

$chart = Plot::of($rentals)
    ->aes(x: 'area_m2', y: 'monthly_rent', color: 'neighborhood')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm');</x-slot:pipe>
                </x-code-tabs>
                <p class="mt-4 text-center text-sm text-zinc-400">
                    phibble reads the query, phplyr transforms it, phit fits the model, and phiz draws the chart.
                </p>
            </div>
        </div>

        {{-- Counts from the synced content --}}
        <div class="border-t border-white/5 bg-white/[0.02]">
            <dl class="mx-auto grid max-w-7xl grid-cols-2 gap-y-6 px-4 py-8 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ([
                    [$stats['packages'], 'packages that work together'],
                    [number_format($stats['verified']), 'results checked against R and other references'],
                    [$stats['vignettes'], 'live vignettes running on this server'],
                    [$stats['benchmarks'], 'published benchmark reports'],
                ] as [$value, $label])
                    <div class="px-2">
                        <dt class="sr-only">{{ $label }}</dt>
                        <dd class="text-3xl font-semibold tracking-tight text-white">{{ $value }}</dd>
                        <dd class="mt-1 text-sm text-zinc-400">{{ $label }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- What you can do --}}
    <section class="bg-zinc-50 py-20 sm:py-24 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold text-php-600 dark:text-php-300">What you can do</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">From the database to a fitted model</h2>
                <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">Each panel below is computed by the packages when this page loads.</p>
            </div>

            <div x-data="{ tab: '{{ $tabs->first()['id'] ?? '' }}', ids: @js($tabs->pluck('id')) }" x-init="if (ids.includes(location.hash.slice(1))) tab = location.hash.slice(1)" class="mt-14 overflow-hidden rounded-2xl bg-white shadow-xl shadow-php-900/5 ring-1 ring-zinc-200 lg:grid lg:min-h-[36rem] lg:grid-cols-[18rem_1fr] dark:bg-zinc-900 dark:ring-white/10">
                <div role="tablist" aria-label="What you can do" class="flex overflow-x-auto border-b border-zinc-200 lg:block lg:border-b-0 lg:border-r dark:border-white/10">
                    @foreach ($tabs as $tab)
                        <button type="button" role="tab" x-on:click="tab = '{{ $tab['id'] }}'; history.replaceState(null, '', '#{{ $tab['id'] }}')"
                                x-bind:aria-selected="(tab === '{{ $tab['id'] }}').toString()" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                class="group flex w-full shrink-0 items-center gap-3 border-zinc-100 px-5 py-4 text-left text-sm font-medium text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-900 aria-selected:bg-php-50/70 aria-selected:text-zinc-950 max-lg:w-auto max-lg:border-b-2 max-lg:border-transparent max-lg:aria-selected:border-php-500 lg:border-b lg:border-l-2 lg:border-l-transparent lg:aria-selected:border-l-php-500 dark:border-white/5 dark:text-zinc-400 dark:hover:bg-white/[0.03] dark:hover:text-white dark:aria-selected:bg-php-500/10 dark:aria-selected:text-white">
                            <x-dynamic-component :component="'heroicon-o-'.($tab['meta']['icon'] ?? 'squares-2x2')" class="size-5 shrink-0 text-zinc-400 group-aria-selected:text-php-500 dark:group-aria-selected:text-php-300" />
                            <span class="whitespace-nowrap lg:whitespace-normal">{{ $tab['meta']['title'] }}</span>
                        </button>
                    @endforeach
                </div>
                @foreach ($tabs as $tab)
                    <div role="tabpanel" x-show="tab === '{{ $tab['id'] }}'" @unless ($loop->first) x-cloak @endunless class="min-w-0 p-6 sm:p-10">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="text-xl font-semibold tracking-tight">{{ $tab['meta']['title'] }}</h3>
                            @foreach ($tab['meta']['packages'] ?? [] as $key)
                                <x-package-chip :package="$key" />
                            @endforeach
                        </div>
                        <div class="prose prose-zinc prose-php mt-5 max-w-none dark:prose-invert prose-p:leading-7">
                            {!! $tab['html'] !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- The family --}}
    <section id="packages" class="scroll-mt-16 py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold text-php-600 dark:text-php-300">The family</p>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">One job per package</h2>
                <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">
                    The packages share one data frame type and stack from data to graphics.
                    Use one, or use them together.
                </p>
            </div>

            <div class="mt-14 space-y-4">
                @foreach (config('phpstats.layers') as $layer)
                    <div class="layer-{{ $layer['color'] }} grid gap-4 rounded-2xl bg-(--l50)/60 p-4 ring-1 ring-(--l100) sm:p-5 lg:grid-cols-[13rem_1fr] dark:bg-(--l400)/5 dark:ring-(--l400)/15">
                        <div class="px-1 lg:py-2">
                            <p class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-(--l700) dark:text-(--l300)">
                                <span class="size-2 rounded-full bg-(--l500)"></span>{{ $layer['title'] }}
                            </p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $layer['role'] }}</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($layer['packages'] as $key => $description)
                                @php $package = $packages[$key]; @endphp
                                <a href="{{ $package->url() }}" class="group flex flex-col rounded-xl bg-white p-4 shadow-sm ring-1 ring-zinc-200/80 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-(--l300) dark:bg-zinc-900 dark:ring-white/10 dark:hover:ring-(--l400)/40">
                                    <span class="flex items-center justify-between gap-3">
                                        <span class="font-semibold text-zinc-950 dark:text-white">{{ $package->name() }}</span>
                                        <x-status :project="$package->project" />
                                    </span>
                                    <span class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $description }}</span>
                                    <span class="mt-auto flex items-center gap-1 pt-3 text-xs font-medium text-(--l600) dark:text-(--l300)">
                                        {{ preg_replace('#^https?://#', '', $package->url()) }}
                                        <x-heroicon-m-arrow-right class="size-3.5 transition group-hover:translate-x-0.5" />
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="rounded-2xl border-2 border-dashed border-zinc-200 p-5 text-center text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                    <span class="font-semibold text-zinc-900 dark:text-white">Your application.</span>
                    Laravel controllers, Livewire dashboards, reports, and queued jobs.
                </div>
            </div>
        </div>
    </section>
</x-layout>
