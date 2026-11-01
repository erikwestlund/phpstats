{{--
    One case of a landing benchmark workload: phiz bar charts of elapsed time
    and memory for the case's rows, the same numbers as a table for screen
    readers, and each implementation's code in tabs. landing.performance
    renders it once per workload, or once per case under subtabs when a
    record measures the workload more than one way.
--}}
@props(['w', 'record', 'rows', 'code', 'id', 'time', 'memory', 'dedent'])
@php
    $languages = $record['languages'] ?? [];
    $env = $record['environment'];
    $name = fn (array $row) => $w['names'][$row['implementation']] ?? $row['implementation'];
    $timeChart = \App\Content\BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r), 'value' => $r['value'] / 1000, 'shown' => $time($r['value'])], $rows), 'Elapsed time (s)');
    $memoryChart = \App\Content\BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r), 'value' => $r['peak_memory_mib'], 'shown' => $memory($r['peak_memory_mib'])], $rows), ($record['memory_measure'] ?? null) === 'peak memory footprint' ? 'Peak memory footprint (MiB)' : 'Peak resident memory (MiB)');
@endphp
<div class="mx-auto mt-6 grid max-w-5xl gap-8 md:grid-cols-2" aria-hidden="true">
    <x-phiz :plot="$timeChart" class="min-w-0 overflow-hidden" />
    <x-phiz :plot="$memoryChart" class="min-w-0 overflow-hidden" />
</div>

<p class="mx-auto mt-3 max-w-4xl text-sm leading-6 text-zinc-600 dark:text-zinc-400">
    @if (($record['memory_measure'] ?? null) === 'peak memory footprint')
        Memory is each process's peak memory footprint: the most memory it held at once, as macOS reports it. That includes the language runtime, the data, and native allocations such as Polars' and phit's, and leaves out file pages the system can drop.
    @else
        Memory is each process's peak resident set size: the most physical memory it used at once, including file pages it mapped.
    @endif
</p>
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

