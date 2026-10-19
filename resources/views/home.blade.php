@php
    $packages = \App\Content\Package::all();
    // The example tabs are Markdown files in resources/pages/home, in file name order.
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
use function Phplyr\{col, filter, floor_date, group_by};
use function Phplyr\{lm, mutate, sum, summarise};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$monthly = $orders
    |> filter(col('returned')->eq(0))
    |> mutate(month: floor_date(col('placed_on'), 'month'))
    |> group_by('month', 'channel')
    |> summarise(revenue: sum('total'));

$fit = $orders |> lm('total ~ items + coupon + channel');

$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'revenue', color: 'channel')
    ->line();</x-slot:pipe>
                    <x-slot:chain>use App\Models\Order;
use function Phplyr\{col, floor_date, phplyr, sum};

$orders = \Phibble\DataFrame::fromQuery(Order::query());

$monthly = phplyr($orders)
    ->filter(col('returned')->eq(0))
    ->mutate(month: floor_date(col('placed_on'), 'month'))
    ->group_by('month', 'channel')
    ->summarise(revenue: sum('total'))
    ->get();

$fit = phplyr($orders)->lm('total ~ items + coupon + channel');

$chart = \Phiz\Plot::of($monthly)
    ->aes(x: 'month', y: 'revenue', color: 'channel')
    ->line();</x-slot:chain>
                </x-code-tabs>
            </div>
        </div>
    </section>

    {{-- The packages, by category. Choosing a package shows its code from resources/examples/packages. --}}
    <section id="packages" x-data="{ open: 'phibble' }" class="scroll-mt-16 py-12 sm:py-14">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="grid divide-y divide-zinc-200 rounded-2xl ring-1 ring-zinc-200 md:grid-cols-3 md:divide-x md:divide-y-0 dark:divide-white/10 dark:ring-white/10">
                @foreach (config('phpstats.layers') as $layer)
                    <div class="layer-{{ $layer['color'] }} px-5 py-6 sm:px-6">
                        <div class="text-center">
                            <x-dynamic-component :component="'heroicon-o-'.$layer['icon']" class="mx-auto size-7 text-(--l500) dark:text-(--l300)" />
                            <h2 class="mt-2.5 font-semibold text-zinc-950 dark:text-white">{{ $layer['title'] }}</h2>
                        </div>
                        <hr class="mt-4 border-zinc-200 dark:border-white/10">
                        <ul class="mt-3 space-y-0.5">
                            @foreach ($layer['packages'] as $key => $description)
                                <li>
                                    <button type="button" x-on:click="open = open === '{{ $key }}' ? null : '{{ $key }}'"
                                            x-bind:aria-expanded="(open === '{{ $key }}').toString()" aria-expanded="{{ $key === 'phibble' ? 'true' : 'false' }}" aria-controls="package-code-{{ $key }}"
                                            class="group -mx-2 flex w-[calc(100%+1rem)] gap-2 rounded-lg px-2 py-1.5 text-left transition hover:bg-(--l50) aria-expanded:bg-(--l50) dark:hover:bg-(--l400)/10 dark:aria-expanded:bg-(--l400)/10">
                                        <x-heroicon-m-chevron-right class="mt-0.5 size-4 shrink-0 text-(--l500) transition group-aria-expanded:rotate-90 dark:text-(--l300)" />
                                        <span>
                                            <span class="font-mono text-sm font-semibold text-zinc-950 dark:text-white">{{ $packages[$key]->name() }}</span>
                                            <span class="mt-0.5 block text-sm leading-[1.375rem] text-zinc-600 dark:text-zinc-400">{{ $description }}</span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            @foreach ($packages as $key => $package)
                @php $code = \App\Content\Example::code("packages/{$key}"); @endphp
                <div id="package-code-{{ $key }}" x-show="open === '{{ $key }}'" @unless ($key === 'phibble') x-cloak @endunless class="mt-4">
                    <x-code-tabs :pipe="$code['pipe']" :chain="$code['chain']" :label="$package->name()" :mono-label="true" />
                    <a href="{{ $package->url() }}" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                        More about <span class="font-mono">{{ $package->name() }}</span>
                        <x-heroicon-m-arrow-right class="size-4" />
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Examples --}}
    <section class="bg-zinc-50 py-20 sm:py-24 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">Simplify Statistical Workflows in PHP Applications with Native PHP</h2>
            </div>

            <div x-data="{ tab: '{{ $tabs->first()['id'] ?? '' }}', ids: @js($tabs->pluck('id')) }" x-init="if (ids.includes(location.hash.slice(1))) tab = location.hash.slice(1)" class="mt-14 overflow-hidden rounded-2xl bg-white shadow-xl shadow-php-900/5 ring-1 ring-zinc-200 lg:grid lg:min-h-[36rem] lg:grid-cols-[18rem_1fr] dark:bg-zinc-900 dark:ring-white/10">
                <div role="tablist" aria-label="Examples" class="flex overflow-x-auto border-b border-zinc-200 lg:block lg:border-b-0 lg:border-r dark:border-white/10">
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
                        <h3 class="text-xl font-semibold tracking-tight">{{ $tab['meta']['title'] }}</h3>
                        <div class="prose prose-zinc prose-php mt-5 max-w-none dark:prose-invert prose-p:leading-7">
                            {!! $tab['html'] !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layout>
