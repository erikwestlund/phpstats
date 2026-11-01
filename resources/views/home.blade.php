<x-layout :bare="true">
<div class="landing">
    {{-- Hero: the introduction beside a carousel of examples. --}}
    <section class="relative isolate overflow-hidden bg-zinc-950 text-white">
        <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="absolute -top-40 left-1/2 -z-10 h-[36rem] w-[64rem] -translate-x-1/2 rounded-full bg-gradient-to-r from-php-600/40 via-violet-600/30 to-fuchsia-600/20 blur-3xl" aria-hidden="true"></div>

        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 pb-14 pt-12 *:min-w-0 sm:px-6 lg:grid-cols-[1fr_1.1fr] lg:gap-12 lg:px-8 lg:pb-16 lg:pt-16">
            <div>
                <h1 class="text-4xl font-semibold tracking-tight sm:text-6xl">
                    Modern statistical computing
                    <span class="block bg-gradient-to-r from-php-300 via-violet-300 to-fuchsia-300 bg-clip-text text-transparent">for PHP</span>
                </h1>
                <p class="mt-6 max-w-xl text-xl leading-8 text-zinc-200">
                    A collection of native PHP packages and extensions for modern statistical workflows.
                </p>
                <p class="mt-4 max-w-xl text-base leading-7 text-zinc-400">
                    Load, transform, and summarize data from your app using data frames. Work with probability distributions, fit statistical models, and draw beautiful charts without leaving PHP.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="#packages" class="rounded-full bg-php-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-php-500/30 transition hover:bg-php-400">Explore the packages</a>
                    <a href="{{ route('page', 'installation') }}" class="rounded-full px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/10">Get started</a>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-php-500/20 via-violet-500/10 to-transparent blur-2xl" aria-hidden="true"></div>
                <x-landing.hero-examples />
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
