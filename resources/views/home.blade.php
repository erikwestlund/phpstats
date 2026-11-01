<x-layout :bare="true">
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
                    Data processing, analysis, visualization, and modeling in native PHP.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="#packages" class="rounded-full bg-php-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-php-500/30 transition hover:bg-php-400">Explore the packages</a>
                    <a href="{{ route('page', 'about') }}" class="rounded-full px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/10">About PHPstats</a>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-php-500/20 via-violet-500/10 to-transparent blur-2xl" aria-hidden="true"></div>
                <x-code-tabs label="OrderReport.php" class="shadow-2xl shadow-black/40 ring-white/10!">
                    <x-slot:pipe>use App\Models\Order;
use function Phplyr\{col, filter, floor_date};
use function Phplyr\{group_by, mutate, sum, summarize};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$monthly = $orders |>
    filter(col('returned')->eq(0)) |>
    mutate(month: floor_date(col('placed_on'), 'month')) |>
    group_by('month', 'channel') |>
    summarize(revenue: sum('total'));

$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'revenue', color: 'channel')
    ->line();</x-slot:pipe>
                    <x-slot:chain>use App\Models\Order;
use function Phplyr\{col, floor_date, phplyr, sum};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$monthly = phplyr($orders)
    ->filter(col('returned')->eq(0))
    ->mutate(month: floor_date(col('placed_on'), 'month'))
    ->groupBy('month', 'channel')
    ->summarize(revenue: sum('total'))
    ->get();

$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'revenue', color: 'channel')
    ->line();</x-slot:chain>
                </x-code-tabs>
            </div>
        </div>
    </section>

    {{-- The packages: what each one does, its code, and its output --}}
    <section id="packages" class="scroll-mt-16 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Packages</h2>
            <p class="mx-auto mb-10 mt-4 max-w-2xl text-center text-lg leading-8 text-zinc-600 sm:mb-12 dark:text-zinc-400">PHPstats has seven packages for statistical computing workflows, from data cleaning to statistical modeling.</p>
            <x-package-explorer />
        </div>
    </section>

</x-layout>
