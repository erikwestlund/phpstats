{{--
    The landing page's benchmarks, one workload at a time: each workload in
    config/phpstats.php benchmarks names a package's synced benchmark record,
    which holds the saved results, each implementation's code, and the
    environment. A selector chooses the workload; within it, elapsed time and
    peak memory sit side by side with equal weight, then the code of each
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
    $mono = fn (string $implementation) => (bool) preg_match('/^(phpolars|phibble|phit|phplyr)\b/', $implementation);
@endphp
@if ($workloads->isNotEmpty())
<section id="performance" aria-labelledby="performance-heading" class="scroll-mt-16 border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="performance-heading" class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Performant, powerful, and flexible</h2>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg leading-snug text-zinc-600 dark:text-zinc-400">
            Each workload has more than one implementation. Choose the one that fits its runtime, memory, and where it has to run. Every implementation below does the same work and gives the same results.
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
                    $maxTime = max(array_column($rows, 'value'));
                    $maxMemory = max(array_column($rows, 'peak_memory_mib'));
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

                    <table class="mx-auto mt-6 w-full max-w-4xl text-left text-[0.9375rem]">
                        <caption class="sr-only">{{ $w['label'] }}: median elapsed time and peak memory of {{ $env['repeats'] }} runs</caption>
                        <thead class="border-b border-zinc-200 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="py-2 pr-4 font-medium">Implementation</th>
                                <th scope="col" class="w-[34%] py-2 pr-4 font-medium">Elapsed time</th>
                                <th scope="col" class="w-[34%] py-2 font-medium">Memory</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 tabular-nums dark:divide-white/5">
                            @foreach ($rows as $row)
                                <tr>
                                    <th scope="row" @class(['py-3 pr-4 align-top font-medium', 'font-mono text-[0.875rem]' => $mono($row['implementation'])])>{{ $row['implementation'] }}</th>
                                    <td class="py-3 pr-4 align-top">
                                        {{ $time($row['value']) }}
                                        <span class="mt-1.5 block h-1.5 rounded-full bg-php-500/70 dark:bg-php-400/70" style="width: {{ max(1.5, round(100 * $row['value'] / $maxTime, 1)) }}%" aria-hidden="true"></span>
                                    </td>
                                    <td class="py-3 align-top">
                                        {{ $memory($row['peak_memory_mib']) }}
                                        <span class="mt-1.5 block h-1.5 rounded-full bg-zinc-400/80 dark:bg-zinc-500/80" style="width: {{ max(1.5, round(100 * $row['peak_memory_mib'] / $maxMemory, 1)) }}%" aria-hidden="true"></span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($w['note'])
                        <p class="mx-auto mt-4 max-w-4xl text-[0.9375rem] leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $w['note'] }}</p>
                    @endif

                    @if ($code !== [])
                        @php $first = array_key_first($code); @endphp
                        <div x-data="{ impl: @js($first) }" class="code-ink mx-auto mt-8 max-w-4xl overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)">
                            <div role="tablist" aria-label="{{ $w['label'] }} code" class="flex flex-wrap gap-1 border-b border-white/5 px-3 py-2 text-xs">
                                @foreach ($code as $impl => $source)
                                    @php $slug = $w['id'].'-'.\Illuminate\Support\Str::slug($impl); @endphp
                                    <button type="button" role="tab" id="code-tab-{{ $slug }}" aria-controls="code-{{ $slug }}"
                                            x-on:click="impl = @js($impl)" x-bind:aria-selected="(impl === @js($impl)).toString()" aria-selected="{{ $impl === $first ? 'true' : 'false' }}"
                                            @class(['rounded-md px-2.5 py-1 text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white', 'font-mono' => $mono($impl)])>{{ $impl }}</button>
                                @endforeach
                            </div>
                            @foreach ($code as $impl => $source)
                                @php $slug = $w['id'].'-'.\Illuminate\Support\Str::slug($impl); @endphp
                                <div role="tabpanel" id="code-{{ $slug }}" aria-labelledby="code-tab-{{ $slug }}" x-show="impl === @js($impl)" @unless ($impl === $first) x-cloak @endunless>
                                    <pre class="max-h-[30rem] overflow-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight(trim(preg_replace('/^<\?php\s*/', '', $source)), $languages[$impl] ?? 'php') !!}</code></pre>
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
