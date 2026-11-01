{{--
    The landing page's benchmarks, one workload at a time: each workload in
    config/phpstats.php benchmarks names a package's synced benchmark record,
    which holds the saved results, each implementation's code, and the
    environment. A selector chooses the workload; within it, two phiz bar
    charts, elapsed time and memory, sit side by side with equal weight (with
    the same numbers in a table for screen readers), then the code of each
    implementation in tabs, then a short summary of how it was measured and a
    link to the full record on the package's Benchmarks page. The page never
    runs a benchmark, and a workload whose record has not synced is left out.
--}}
@php
    $workloads = collect(config('phpstats.benchmarks'))->map(function (array $workload) {
        $package = \App\Content\Package::find($workload['package']);
        $record = $package ? collect($package->benchmarks())->firstWhere('id', $workload['id']) : null;

        return $record ? [...$workload, 'record' => $record, 'url' => $package->url('benchmarks').'#'.$workload['id'], 'name' => $package->name()] : null;
    })->filter()->values();
    $time = fn (float $ms) => $ms < 1000 ? number_format($ms, $ms < 10 ? 1 : 0).' ms' : number_format($ms / 1000, 1).' s';
    $memory = fn (float $mib) => $mib >= 1024 ? number_format($mib / 1024, 1).' GiB' : number_format($mib).' MiB';
    // A record's code is shown without its common indentation. Some records
    // keep a snippet's indentation on every line but the first, so the
    // indentation is measured on the lines after it.
    $dedent = function (string $code): string {
        $lines = explode("\n", trim(preg_replace('/^<\?php\s*/', '', $code), "\n"));
        $rest = array_filter(array_slice($lines, 1), fn ($l) => trim($l) !== '');
        $indent = $rest === [] ? 0 : min(array_map(fn ($l) => strlen($l) - strlen(ltrim($l)), $rest));

        return implode("\n", array_map(fn ($l) => preg_replace('/^ {0,'.$indent.'}/', '', $l), $lines));
    };
@endphp
@if ($workloads->isNotEmpty())
<section id="performance" aria-labelledby="performance-heading" class="scroll-mt-16 border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="performance-heading" class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Performant, powerful, and flexible</h2>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg leading-snug text-zinc-600 dark:text-zinc-400">
            PHPstats's performance is competitive with and sometimes better than statistical computing alternatives. Use <span class="font-mono">phit-polyfill</span> for ease of installation and the <span class="font-mono">phit</span> extension for performance.
        </p>

        <div x-data="{ workload: @js($workloads[0]['id']) }" class="mt-10 sm:mt-12">
            <div role="tablist" aria-label="Workload" class="mx-auto flex w-fit max-w-full flex-wrap justify-center gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-white/5">
                @foreach ($workloads as $w)
                    <button type="button" role="tab" id="workload-tab-{{ $w['id'] }}" aria-controls="workload-{{ $w['id'] }}"
                            x-on:click="workload = '{{ $w['id'] }}'" x-bind:aria-selected="(workload === '{{ $w['id'] }}').toString()" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 aria-selected:bg-white aria-selected:text-zinc-900 aria-selected:shadow-sm dark:text-zinc-400 dark:hover:text-white dark:aria-selected:bg-white/10 dark:aria-selected:text-white">
                        {{ $w['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach ($workloads as $w)
                @php
                    $record = $w['record'];
                    $rows = $record['rows'];
                    $env = $record['environment'];
                    $code = $record['code'] ?? [];
                    $languages = $record['languages'] ?? [];
                @endphp
                <div role="tabpanel" id="workload-{{ $w['id'] }}" aria-labelledby="workload-tab-{{ $w['id'] }}" x-show="workload === '{{ $w['id'] }}'" @unless ($loop->first) x-cloak @endunless class="mt-8">
                    @php
                        $data = $record['dataset'] ?? null;
                        $caption = $record['workload'] ?? ($data ? number_format($data['rows']).' rows, '.number_format($data['bytes'] / 1e9, 2).' GB. '.($w['task'] ?? '') : $record['title']);
                    @endphp
                    <p class="text-center text-[0.9375rem] text-zinc-600 dark:text-zinc-400">{{ trim($caption) }}</p>

                    @php
                        $name = fn (array $row) => $w['names'][$row['implementation']] ?? $row['implementation'];
                        $timeChart = \App\Content\BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r), 'value' => $r['value'] / 1000, 'shown' => $time($r['value'])], $rows), 'Elapsed time (s)');
                        $memoryChart = \App\Content\BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r), 'value' => $r['peak_memory_mib'], 'shown' => $memory($r['peak_memory_mib'])], $rows), 'Memory (MiB)');
                    @endphp
                    <div class="mx-auto mt-6 grid max-w-5xl gap-8 md:grid-cols-2" aria-hidden="true">
                        <x-phiz :plot="$timeChart" class="min-w-0 overflow-hidden" />
                        <x-phiz :plot="$memoryChart" class="min-w-0 overflow-hidden" />
                    </div>
                    <div class="sr-only"><table>
                        <caption>{{ $w['label'] }}: median elapsed time and peak memory of {{ $env['repeats'] }} runs</caption>
                        <thead><tr><th scope="col">Implementation</th><th scope="col">Elapsed time</th><th scope="col">Memory</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr><th scope="row">{{ $name($row) }}</th><td>{{ $time($row['value']) }}</td><td>{{ $memory($row['peak_memory_mib']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table></div>
                    @if ($w['note'])
                        <p class="mx-auto mt-4 max-w-4xl text-[0.9375rem] leading-relaxed text-zinc-700 dark:text-zinc-300">{!! \App\Content\Libraries::mono(e($w['note'])) !!}</p>
                    @endif

                    @if ($code !== [])
                        @php $first = array_key_first($code); @endphp
                        <div x-data="{ impl: @js($first) }" class="code-ink mx-auto mt-8 max-w-4xl overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)">
                            <div role="tablist" aria-label="{{ $w['label'] }} code" class="flex flex-wrap gap-1 border-b border-white/5 px-3 py-2 text-xs">
                                @foreach ($code as $impl => $source)
                                    @php $slug = $w['id'].'-'.\Illuminate\Support\Str::slug($impl); @endphp
                                    <button type="button" role="tab" id="code-tab-{{ $slug }}" aria-controls="code-{{ $slug }}"
                                            x-on:click="impl = @js($impl)" x-bind:aria-selected="(impl === @js($impl)).toString()" aria-selected="{{ $impl === $first ? 'true' : 'false' }}"
                                            class="rounded-md px-2.5 py-1 text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white">{!! \App\Content\Libraries::mono(e($w['names'][$impl] ?? $impl)) !!}</button>
                                @endforeach
                            </div>
                            @foreach ($code as $impl => $source)
                                @php $slug = $w['id'].'-'.\Illuminate\Support\Str::slug($impl); @endphp
                                <div role="tabpanel" id="code-{{ $slug }}" aria-labelledby="code-tab-{{ $slug }}" x-show="impl === @js($impl)" @unless ($impl === $first) x-cloak @endunless>
                                    <pre class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($dedent($source), $languages[$impl] ?? 'php') !!}</code></pre>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <p class="mx-auto mt-4 max-w-4xl text-sm leading-6 text-zinc-500">
                        Medians of {{ $env['repeats'] }} runs, each in a fresh process, on {{ $env['cpu'] }} ({{ $env['cores'] }} cores, {{ $env['memory_gb'] }} GB), {{ $env['os'] }}, PHP {{ $env['php'] }}, recorded {{ $env['date'] }}.
                        @if (($record['memory_measure'] ?? null) === 'peak memory footprint')
                            Memory is each process's peak memory footprint, which leaves out file pages the system can drop.
                        @else
                            Memory is each process's peak resident set size.
                        @endif
                        The full record gives versions, thread settings, and where timing starts and stops.
                    </p>
                    <div class="mx-auto mt-3 max-w-4xl">
                        <a href="{{ $w['url'] }}" aria-label="View the complete {{ strtolower($w['label']) }} benchmark on {{ $w['name'] }}'s Benchmarks page" class="inline-flex items-center gap-1.5 text-[0.9375rem] font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                            View the complete benchmark <x-heroicon-m-arrow-right class="size-4" />
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
