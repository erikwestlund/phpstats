<x-layout :bare="true">
<div class="landing">
    {{-- Hero: a compact introduction, then a demonstration with its code and chart side by side. --}}
    <section class="relative isolate overflow-hidden bg-zinc-950 text-white">
        <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="absolute -top-40 left-1/2 -z-10 h-[36rem] w-[64rem] -translate-x-1/2 rounded-full bg-gradient-to-r from-php-600/40 via-violet-600/30 to-fuchsia-600/20 blur-3xl" aria-hidden="true"></div>

        <div class="mx-auto max-w-7xl px-4 pb-14 pt-12 sm:px-6 lg:px-8 lg:pb-16 lg:pt-14">
            <div class="max-w-3xl">
                <h1 class="text-4xl font-semibold tracking-tight sm:text-6xl">
                    Modern statistical computing
                    <span class="bg-gradient-to-r from-php-300 via-violet-300 to-fuchsia-300 bg-clip-text text-transparent">for PHP</span>
                </h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-zinc-300">
                    Load data into tables, transform and summarize them, fit statistical models, and draw charts, all in PHP.
                </p>
                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <a href="#packages" class="rounded-full bg-php-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-php-500/30 transition hover:bg-php-400">Explore the packages</a>
                    <a href="{{ route('page', 'installation') }}" class="rounded-full px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/10">Get started</a>
                </div>
            </div>

            @php
                // The demonstration runs like the other landing examples: its code is
                // resources/examples/hero/temperatures.php (and .chain.php), and the
                // card beside it shows the chart that code draws, from illustrative data.
                $hero = \App\Content\Example::code('hero/temperatures');
                try {
                    $heroChart = \App\Content\Example::output('hero/temperatures', 'hero-chart', fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => 'Illustrative data'])->render(), files: [database_path('data/daily-temperatures.csv')]);
                } catch (\Throwable $e) {
                    report($e);
                    $heroChart = null;
                }
            @endphp
            <div class="relative mt-10 grid gap-6 *:min-w-0 lg:grid-cols-2 lg:items-start">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-php-500/20 via-violet-500/10 to-transparent blur-2xl" aria-hidden="true"></div>
                <x-code-tabs :pipe="$hero['pipe']" :chain="$hero['chain']" :hanging="true" label="temperatures.php" class="shadow-2xl shadow-black/40 ring-white/10!" />
                @if ($heroChart)
                    <div class="overflow-hidden rounded-xl bg-white text-zinc-900 shadow-2xl shadow-black/40 ring-1 ring-white/10 dark:bg-zinc-900 dark:text-zinc-100">
                        {!! $heroChart !!}
                    </div>
                @endif
            </div>
        </div>
    </section>



    {{-- How the packages fit together, shown as one connected example --}}
    <x-landing.together />

    {{-- The packages: what each one does, its code, and its output --}}
    <section id="packages" class="scroll-mt-16 border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="mb-10 text-center text-3xl font-semibold tracking-tight sm:mb-12 sm:text-4xl">Packages</h2>
            <x-package-explorer />
        </div>
    </section>

    {{-- Evidence: verification against R and Python, then saved benchmarks --}}
    <x-landing.verified />
    <x-landing.performance />

    <section aria-label="Get started" class="border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-center gap-3 px-4 sm:flex-row sm:px-6 lg:px-8">
            <a href="{{ route('page', 'installation') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-php-600 px-5 py-2.5 text-[0.9375rem] font-semibold text-white shadow-sm transition hover:bg-php-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-php-500">
                Install the packages
            </a>
            <a href="{{ route('examples') }}" class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-[0.9375rem] font-semibold text-zinc-900 ring-1 ring-zinc-300 transition hover:bg-zinc-50 dark:text-white dark:ring-white/20 dark:hover:bg-white/5">
                Browse examples
            </a>
        </div>
    </section>

</div>
</x-layout>
