{{--
    The landing hero's examples: a carousel of short scripts across what
    PHPstats does, each resources/examples/hero/{name}.php (with .chain.php
    where a method chain exists). A script that echoes one result is shown as
    it printed; one that echoes several returns them, keyed as in $examples. A
    slide shows the code and, under tabs a visitor opens, those results. The
    panel is as tall as the introduction beside it on wide screens. The carousel moves to the next example every five seconds
    until a visitor uses it. An example this server can't run is left out.
--}}
@php
    $examples = [
        'temperatures' => ['title' => 'Summarize and chart data', 'outputs' => ['monthly' => 'Data frame', 'chart' => 'Chart'], 'files' => ['daily-temperatures.csv']],
        'polars' => ['title' => 'Query a CSV file with Polars', 'outputs' => ['printed' => 'Table'], 'files' => ['daily-temperatures.csv']],
        'model' => ['title' => 'Fit a logistic regression', 'outputs' => ['printed' => 'Coefficients'], 'files' => []],
        'gamma' => ['title' => 'Work with probability distributions', 'outputs' => ['printed' => 'Chart'], 'files' => []],
    ];
    $slides = [];
    foreach ($examples as $name => $example) {
        try {
            $outputs = [];
            foreach ($example['outputs'] as $key => $title) {
                $outputs[$key] = \App\Content\Example::output(
                    "hero/{$name}",
                    "hero|{$key}",
                    fn (mixed $value) => view('components.package-output', ['value' => is_array($value) ? $value[$key] : $value])->render(),
                    files: array_map(fn (string $file) => database_path("data/{$file}"), $example['files']),
                );
            }
            $slides[$name] = $example + ['code' => \App\Content\Example::code("hero/{$name}"), 'rendered' => $outputs];
        } catch (\Throwable $e) {
            report($e);
        }
    }
    $render = fn (string $source) => \App\Content\Code::hanging(\App\Content\Code::highlight($source, 'php'));
    $syntaxes = ['pipe' => 'PHP 8.5+', 'chain' => 'PHP 8.3–8.4'];
@endphp
@if ($slides !== [])
<div x-data="heroCarousel({{ count($slides) }})" x-on:mouseenter="paused = true" x-on:mouseleave="paused = false" x-on:focusin="paused = true" x-on:focusout="paused = false"
     role="region" aria-roledescription="carousel" aria-label="Examples" class="code-ink flex h-full flex-col overflow-hidden rounded-xl shadow-2xl shadow-black/40 ring-1 ring-white/10">
    {{-- The slides share one grid cell, so the panel keeps the height of the tallest. --}}
    <div class="grid flex-1">
        @foreach ($slides as $name => $slide)
            @php $index = $loop->index; @endphp
            <div role="group" aria-roledescription="slide" aria-label="{{ $slide['title'] }}"
                 x-bind:inert="current !== {{ $index }}" @unless ($loop->first) inert @endunless
                 x-bind:class="{ 'opacity-0 invisible': current !== {{ $index }} }"
                 @class(['flex min-w-0 flex-col transition-[opacity,visibility] duration-500 [grid-area:1/1]', 'opacity-0 invisible' => ! $loop->first])
                 x-data="syntaxTabs">
                <div x-data="{ tab: 'code' }" class="flex flex-1 flex-col">
                    <div class="flex flex-wrap items-center gap-3 border-b border-white/5 px-3 py-2 text-xs">
                        <div role="tablist" aria-label="{{ $slide['title'] }}" class="flex gap-1">
                            @foreach (['code' => 'Code'] + $slide['outputs'] as $key => $title)
                                <button type="button" role="tab" id="hero-{{ $name }}-tab-{{ $key }}" aria-controls="hero-{{ $name }}-{{ $key }}" x-on:click="tab = '{{ $key }}'; stop()"
                                        x-bind:aria-selected="(tab === '{{ $key }}').toString()" aria-selected="{{ $key === 'code' ? 'true' : 'false' }}"
                                        class="rounded-md px-2.5 py-1 font-medium text-zinc-400 transition hover:text-white aria-selected:bg-white/10 aria-selected:text-white">
                                    {{ $title }}
                                </button>
                            @endforeach
                        </div>
                        @if ($slide['code']['chain'] !== null)
                            <div role="tablist" aria-label="Code syntax" x-show="tab === 'code'" class="ml-auto flex gap-1 rounded-lg bg-white/5 p-0.5">
                                @foreach ($syntaxes as $key => $title)
                                    <button type="button" role="tab" x-on:click="stop(); choose('{{ $key }}')"
                                            x-bind:aria-selected="(syntax === '{{ $key }}').toString()" aria-selected="{{ $key === 'pipe' ? 'true' : 'false' }}"
                                            class="rounded-md px-2.5 py-1 text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white">
                                        {{ $title }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    {{-- The code and each result share a cell too, so changing tabs never changes the height. --}}
                    <div class="grid flex-1">
                        <div role="tabpanel" id="hero-{{ $name }}-code" aria-labelledby="hero-{{ $name }}-tab-code"
                             x-bind:class="{ invisible: tab !== 'code' }" class="min-w-0 [grid-area:1/1]">
                            @if ($slide['code']['chain'] === null)
                                <pre class="px-4 py-3 font-mono text-[0.8125rem] leading-[1.6]"><code>{!! $render($slide['code']['pipe']) !!}</code></pre>
                            @else
                                @foreach ($syntaxes as $syntax => $label)
                                    <pre @if ($syntax !== 'pipe') x-cloak @endif x-show="syntax === '{{ $syntax }}'" class="px-4 py-3 font-mono text-[0.8125rem] leading-[1.6]"><code>{!! $render($slide['code'][$syntax]) !!}</code></pre>
                                @endforeach
                            @endif
                        </div>
                        @foreach ($slide['rendered'] as $key => $html)
                            <div role="tabpanel" id="hero-{{ $name }}-{{ $key }}" aria-labelledby="hero-{{ $name }}-tab-{{ $key }}"
                                 x-bind:class="{ invisible: tab !== '{{ $key }}' }" class="invisible flex min-w-0 flex-col justify-center bg-white text-zinc-900 [grid-area:1/1] dark:bg-zinc-900 dark:text-zinc-100">
                                {!! $html !!}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-t border-white/5 px-4 py-2">
        <div class="grid text-xs font-medium text-zinc-300" aria-live="polite">
            @foreach ($slides as $slide)
                <span x-bind:class="{ invisible: current !== {{ $loop->index }} }" @class(['[grid-area:1/1]', 'invisible' => ! $loop->first])>{{ $slide['title'] }}</span>
            @endforeach
        </div>
        @if (count($slides) > 1)
            <div class="flex items-center gap-3">
                <button type="button" x-on:click="show(current - 1)" aria-label="Previous example" class="rounded-md p-1 text-zinc-400 transition hover:bg-white/10 hover:text-white">
                    <x-heroicon-m-chevron-left class="size-4" />
                </button>
                @foreach ($slides as $slide)
                    <button type="button" x-on:click="show({{ $loop->index }})" aria-label="{{ $slide['title'] }}"
                            x-bind:aria-current="current === {{ $loop->index }} ? 'true' : null"
                            x-bind:class="{ 'bg-white': current === {{ $loop->index }}, 'bg-white/30 hover:bg-white/50': current !== {{ $loop->index }} }"
                            @class(['size-2 rounded-full transition', 'bg-white' => $loop->first, 'bg-white/30 hover:bg-white/50' => ! $loop->first])></button>
                @endforeach
                <button type="button" x-on:click="show(current + 1)" aria-label="Next example" class="rounded-md p-1 text-zinc-400 transition hover:bg-white/10 hover:text-white">
                    <x-heroicon-m-chevron-right class="size-4" />
                </button>
            </div>
        @endif
    </div>
</div>
@endif
