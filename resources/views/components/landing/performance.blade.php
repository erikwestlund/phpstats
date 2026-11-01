{{--
    The landing page's benchmark: phpolars' saved csv-pipeline record, which
    times one CSV pipeline with phpolars and with plain PHP. The page renders
    the saved record and never runs the workload. The tabs show the two
    scripts exactly as the record holds them, and the heading states the
    measured times. Nothing renders until the record has synced.
--}}
@php
    $phpolars = \App\Content\Package::find('phpolars');
    $record = collect($phpolars->benchmarks())->firstWhere('id', 'csv-pipeline');
@endphp
@if ($record)
@php
    $rows = collect($record['rows'])->keyBy('implementation');
    [$fast, $plain] = [$rows['phpolars'], $rows['plain PHP']];
    $env = $record['environment'];
    $data = $record['dataset'];
    $gb = number_format($data['bytes'] / 1e9, 2);
    $seconds = fn (float $ms) => number_format($ms / 1000, 1);
    $memory = fn (float $mib) => $mib >= 1024 ? number_format($mib / 1024, 1).' GiB' : number_format($mib).' MiB';
    $tabs = ['phpolars' => 'phpolars', 'plain PHP' => 'Plain PHP'];
@endphp
<section id="performance" aria-labelledby="performance-heading" class="scroll-mt-16 border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="performance-heading" class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">A {{ $gb }} GB CSV file in {{ $seconds($fast['value']) }} seconds instead of {{ $seconds($plain['value']) }}</h2>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg leading-snug text-zinc-600 dark:text-zinc-400">
            The same pipeline with <span class="font-mono">phpolars</span> and with plain PHP: keep the completed orders, then total them by region and month.
        </p>

        <div class="mt-10 grid gap-8 sm:mt-12 lg:grid-cols-[3fr_2fr] lg:items-start">
            <div x-data="{ tab: 'phpolars' }" class="min-w-0 code-ink overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)">
                <div role="tablist" aria-label="Implementation" class="flex gap-1 border-b border-white/5 px-3 py-2 text-xs">
                    @foreach ($tabs as $key => $label)
                        <button type="button" role="tab" id="perf-tab-{{ Str::slug($key) }}" aria-controls="perf-code-{{ Str::slug($key) }}"
                                x-on:click="tab = '{{ $key }}'" x-bind:aria-selected="(tab === '{{ $key }}').toString()" aria-selected="{{ $key === 'phpolars' ? 'true' : 'false' }}"
                                @class(['rounded-md px-2.5 py-1 text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white', 'font-mono' => $key === 'phpolars'])>{{ $label }}</button>
                    @endforeach
                </div>
                @foreach ($tabs as $key => $label)
                    <div role="tabpanel" id="perf-code-{{ Str::slug($key) }}" aria-labelledby="perf-tab-{{ Str::slug($key) }}" x-show="tab === '{{ $key }}'" @unless ($key === 'phpolars') x-cloak @endunless>
                        <pre class="max-h-[34rem] overflow-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight(trim(preg_replace('/^<\?php\s*/', '', $record['code'][$key])), 'php') !!}</code></pre>
                    </div>
                @endforeach
            </div>

            <div class="min-w-0">
                <table class="w-full text-left text-[0.9375rem]">
                    <caption class="sr-only">Median elapsed time and peak memory of {{ $env['repeats'] }} runs</caption>
                    <thead class="border-b border-zinc-200 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                        <tr><th scope="col" class="py-2 pr-4 font-medium">Implementation</th><th scope="col" class="py-2 pr-4 text-right font-medium">Elapsed time</th><th scope="col" class="py-2 text-right font-medium">Peak memory</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 tabular-nums dark:divide-white/5">
                        @foreach ($tabs as $key => $label)
                            <tr>
                                <th scope="row" @class(['py-2.5 pr-4 font-medium', 'font-mono' => $key === 'phpolars'])>{{ $label }}</th>
                                <td class="py-2.5 pr-4 text-right">{{ $seconds($rows[$key]['value']) }} s</td>
                                <td class="py-2.5 text-right">{{ $memory($rows[$key]['peak_memory_mib']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-sm text-zinc-500">Medians of {{ $env['repeats'] }} runs, each in a fresh process. Both produce the same {{ $record['check']['groups'] }} groups.</p>

                <dl class="mt-6 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
                    <dt class="text-zinc-500">Data</dt>
                    <dd>{{ number_format($data['rows']) }} rows, {{ $gb }} GB, about 1% of amounts missing</dd>
                    <dt class="text-zinc-500">Machine</dt>
                    <dd>{{ $env['cpu'] }}, {{ $env['cores'] }} cores, {{ $env['memory_gb'] }} GB, {{ $env['os'] }}</dd>
                    <dt class="text-zinc-500">Software</dt>
                    <dd>PHP {{ $env['php'] }}, <span class="font-mono">phpolars</span> {{ $env['phpolars'] }} (Polars {{ $env['polars'] }})</dd>
                    <dt class="text-zinc-500">Threads</dt>
                    <dd><span class="font-mono">phpolars</span>: {{ $env['threads'] }}, the default; plain PHP: 1</dd>
                    <dt class="text-zinc-500">Measured</dt>
                    <dd>Inside each script, from opening the file to holding the result. Peak memory is the process's resident set size. The file was in the page cache.</dd>
                </dl>

                <p class="mt-6 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                    Polars reads the columns as typed data and treats an empty amount as missing. Plain PHP casts each field and skips an empty amount. Both leave missing amounts out of revenue and the mean. Their sums differ by at most {{ sprintf('%.0e', max($record['check']['max_rel_difference'], 1e-16)) }} relative, from adding in a different order. Plain PHP reads one row at a time, so its memory stays small.
                </p>

                <a href="{{ $phpolars->url('benchmarks') }}#csv-pipeline" aria-label="View the complete benchmark in phpolars' benchmarks" class="mt-4 inline-flex items-center gap-1.5 text-[0.9375rem] font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                    View the complete benchmark <x-heroicon-m-arrow-right class="size-4" />
                </a>
            </div>
        </div>
    </div>
</section>
@endif
