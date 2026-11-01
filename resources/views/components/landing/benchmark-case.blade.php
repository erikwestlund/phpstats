{{--
    One case of a landing benchmark workload, laid out like the verification
    tabs: each implementation's code in tabs on the left, with the details
    (the statistic, the machine, and what memory measures) in small text and
    the link to the full record below it; and on the right phiz bar charts of
    elapsed time and memory stacked one above the other, then the workload's
    note. The numbers are also in a table for screen readers.
    landing.performance renders it once per workload, or once per case under
    subtabs when a record measures the workload more than one way.
--}}
@props(['w', 'record', 'rows', 'code', 'id', 'time', 'memory', 'dedent'])
@php
    $languages = $record['languages'] ?? [];
    $env = $record['environment'];
    $name = fn (array $row) => $w['names'][$row['implementation']] ?? $row['implementation'];
    $footprint = ($record['memory_measure'] ?? null) === 'peak memory footprint';
    $timeChart = \App\Content\BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r), 'value' => $r['value'] / 1000, 'shown' => $time($r['value'])], $rows), 'Elapsed time (s)');
    $memoryChart = \App\Content\BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r), 'value' => $r['peak_memory_mib'], 'shown' => $memory($r['peak_memory_mib'])], $rows), $footprint ? 'Peak memory footprint (MiB)' : 'Peak resident memory (MiB)');
    $first = array_key_first($code);
@endphp
<div class="mt-6 grid gap-8 lg:grid-cols-2 lg:items-start lg:gap-12">
    <div class="min-w-0">
    @if ($code !== [])
        <div x-data="{ impl: @js($first) }" class="code-ink min-w-0 overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)">
            <div role="tablist" aria-label="{{ $w['label'] }} code" class="flex flex-wrap gap-1 border-b border-white/5 px-3 py-2 text-xs">
                @foreach ($code as $impl => $source)
                    @php $slug = $id.'-'.\Illuminate\Support\Str::slug($impl); @endphp
                    <button type="button" role="tab" id="code-tab-{{ $slug }}" aria-controls="code-{{ $slug }}"
                            x-on:click="impl = @js($impl)" x-bind:aria-selected="(impl === @js($impl)).toString()" aria-selected="{{ $impl === $first ? 'true' : 'false' }}"
                            class="rounded-md px-2.5 py-1 text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white">{!! \App\Content\Libraries::mono(e($w['names'][$impl] ?? $impl)) !!}</button>
                @endforeach
            </div>
            @foreach ($code as $impl => $source)
                @php $slug = $id.'-'.\Illuminate\Support\Str::slug($impl); @endphp
                <div role="tabpanel" id="code-{{ $slug }}" aria-labelledby="code-tab-{{ $slug }}" x-show="impl === @js($impl)" @unless ($impl === $first) x-cloak @endunless>
                    <pre class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($dedent($source), $languages[$impl] ?? 'php') !!}</code></pre>
                </div>
            @endforeach
        </div>
    @endif
        <p class="mt-4 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
            Medians of {{ $env['repeats'] }} runs, each in a fresh process, on {{ $env['cpu'] }} ({{ $env['cores'] }} cores, {{ $env['memory_gb'] }} GB), {{ $env['os'] }}, PHP {{ $env['php'] }}, recorded {{ $env['date'] }}.
            @if ($footprint)
                Memory is the peak memory footprint: the most memory the process held at once, including the runtime, the data, and native allocations, but not file pages the system can drop.
            @else
                Memory is the peak resident set size, including file pages the process mapped.
            @endif
            The full record gives versions, thread settings, and where timing starts and stops.
        </p>
        <a href="{{ $w['url'] }}" aria-label="View the complete {{ strtolower($w['label']) }} benchmark on {{ $w['name'] }}'s Benchmarks page" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
            View the complete benchmark <x-heroicon-m-arrow-right class="size-4" />
        </a>
    </div>

    <div class="min-w-0">
        <div class="space-y-6" aria-hidden="true">
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
            <p class="mt-4 text-[0.9375rem] leading-relaxed text-zinc-700 dark:text-zinc-300">{!! \App\Content\Libraries::mono(e($w['note'])) !!}</p>
        @endif

    </div>
</div>
