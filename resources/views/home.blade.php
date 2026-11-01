<x-layout :bare="true">
<div class="landing">
    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-zinc-950 text-white">
        <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="absolute -top-40 left-1/2 -z-10 h-[36rem] w-[64rem] -translate-x-1/2 rounded-full bg-gradient-to-r from-php-600/40 via-violet-600/30 to-fuchsia-600/20 blur-3xl" aria-hidden="true"></div>

        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 pb-14 pt-12 *:min-w-0 sm:px-6 lg:grid-cols-[1fr_1.1fr] lg:gap-12 lg:px-8 lg:pb-16 lg:pt-16">
            <div>
                <h1 class="text-4xl font-semibold tracking-tight sm:text-6xl">
                    Modern statistical computing
                    <span class="block bg-gradient-to-r from-php-300 via-violet-300 to-fuchsia-300 bg-clip-text text-transparent">for PHP</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-zinc-300">
                    Load your application's database queries and CSV files into tables, transform and summarize them, fit statistical models, and draw charts, all in PHP.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="#packages" class="rounded-full bg-php-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-php-500/30 transition hover:bg-php-400">Explore the packages</a>
                    <a href="{{ route('page', 'installation') }}" class="rounded-full px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/10">Get started</a>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-php-500/20 via-violet-500/10 to-transparent blur-2xl" aria-hidden="true"></div>
                @php
                    // The order report runs like the other landing examples: its
                    // code is resources/examples/hero/orders.php (and .chain.php),
                    // and the card below it shows the chart it draws.
                    $hero = \App\Content\Example::code('hero/orders');
                    try {
                        $heroChart = \App\Content\Example::output('hero/orders', 'hero-chart', fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => 'Monthly revenue by channel'])->render());
                    } catch (\Throwable $e) {
                        report($e);
                        $heroChart = null;
                    }
                @endphp
                <x-code-tabs :pipe="$hero['pipe']" :chain="$hero['chain']" label="OrderReport.php" class="shadow-2xl shadow-black/40 ring-white/10!" />
                @if ($heroChart)
                    <div class="mt-4 overflow-hidden rounded-xl bg-white text-zinc-900 shadow-2xl shadow-black/40 ring-1 ring-white/10 dark:bg-zinc-900 dark:text-zinc-100">
                        {!! $heroChart !!}
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- The packages: what each one does, its code, and its output --}}
    <section id="packages" class="scroll-mt-16 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Packages</h2>
            <p class="mx-auto mb-10 mt-4 max-w-2xl text-center text-lg leading-snug text-zinc-600 sm:mb-12 dark:text-zinc-400">PHPstats has seven packages for statistical computing workflows, from data cleaning to statistical modeling. Inspired by the capabilities and cohesiveness of R's tidyverse, the packages are designed to work together.</p>
            <x-package-explorer />
        </div>
    </section>

    {{-- Evidence: one connected example, a saved benchmark, and verification results --}}
    <x-landing.together />
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
