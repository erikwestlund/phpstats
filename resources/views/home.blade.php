<x-layout :bare="true">
<div class="landing">
    {{-- Hero: the introduction beside one example panel, with Code and Chart tabs. --}}
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

            @php
                // The example runs like the other landing examples: its code is
                // resources/examples/hero/temperatures.php (and .chain.php), and the
                // Chart tab shows the chart that code draws, from illustrative data.
                $hero = \App\Content\Example::code('hero/temperatures');
                try {
                    $heroChart = \App\Content\Example::output('hero/temperatures', 'hero-chart', fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => 'Illustrative data'])->render(), files: [database_path('data/daily-temperatures.csv')]);
                } catch (\Throwable $e) {
                    report($e);
                    $heroChart = null;
                }
                $render = fn (string $source) => \App\Content\Code::hanging(\App\Content\Code::highlight($source, 'php'));
                $syntaxes = ['pipe' => 'PHP 8.5+', 'chain' => 'PHP 8.3–8.4'];
            @endphp
            <div class="relative">
                <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-br from-php-500/20 via-violet-500/10 to-transparent blur-2xl" aria-hidden="true"></div>
                <div x-data="{ view: 'code' }" class="code-ink overflow-hidden rounded-xl shadow-2xl shadow-black/40 ring-1 ring-white/10">
                    <div x-data="syntaxTabs">
                        <div class="flex flex-wrap items-center gap-3 border-b border-white/5 px-3 py-2 text-xs">
                            <div role="tablist" aria-label="Example view" class="flex gap-1">
                                @foreach (['code' => 'Code', 'chart' => 'Chart'] as $key => $title)
                                    @continue ($key === 'chart' && $heroChart === null)
                                    <button type="button" role="tab" id="hero-tab-{{ $key }}" aria-controls="hero-panel-{{ $key }}" x-on:click="view = '{{ $key }}'"
                                            x-bind:aria-selected="(view === '{{ $key }}').toString()" aria-selected="{{ $key === 'code' ? 'true' : 'false' }}"
                                            class="rounded-md px-2.5 py-1 font-medium text-zinc-400 transition hover:text-white aria-selected:bg-white/10 aria-selected:text-white">
                                        {{ $title }}
                                    </button>
                                @endforeach
                            </div>
                            <div role="tablist" aria-label="Code syntax" x-show="view === 'code'" class="ml-auto flex gap-1 rounded-lg bg-white/5 p-0.5">
                                @foreach ($syntaxes as $key => $title)
                                    <button type="button" role="tab" x-on:click="choose('{{ $key }}')"
                                            x-bind:aria-selected="(syntax === '{{ $key }}').toString()" aria-selected="{{ $key === 'pipe' ? 'true' : 'false' }}"
                                            class="rounded-md px-2.5 py-1 text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white">
                                        {{ $title }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div role="tabpanel" id="hero-panel-code" aria-labelledby="hero-tab-code" x-show="view === 'code'">
                            @foreach ($syntaxes as $key => $title)
                                <pre @if ($key !== 'pipe') x-cloak @endif x-show="syntax === '{{ $key }}'" class="p-4 font-mono text-[0.8125rem] leading-6"><code>{!! $render($hero[$key]) !!}</code></pre>
                            @endforeach
                        </div>
                        @if ($heroChart)
                            <div role="tabpanel" id="hero-panel-chart" aria-labelledby="hero-tab-chart" x-show="view === 'chart'" x-cloak class="bg-white text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
                                {!! $heroChart !!}
                            </div>
                        @endif
                    </div>
                </div>
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
