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
@props(['w', 'record', 'rows', 'code', 'id', 'time', 'memory', 'dedent', 'title'])
@php
    // phit-polyfill comes last, after the extension and the R and Python libraries.
    $last = fn (string $name) => str_starts_with($name, 'phit-polyfill') ? 1 : 0;
    $rows = collect($rows)->sortBy(fn (array $r) => $last($r['implementation']))->values()->all();
    $code = collect($code)->sortBy(fn ($source, string $impl) => $last($impl))->all();
    $languages = $record['languages'] ?? [];
    $env = $record['environment'];
    $name = fn (array $row) => $w['names'][$row['implementation']] ?? $row['implementation'];
    $measure = $record['memory_measure'] ?? null;
    $footprint = in_array($measure, ['peak memory footprint', 'peak anonymous memory'], true);
    // A CPU with an entry in config/phpstats.php cpus is named by its cores, as in "2 AMD Zen 5 cores".
    $cores = config('phpstats.cpus')[$env['cpu']] ?? null;
    $machine = $cores === null
        ? "{$env['cpu']} ({$env['cores']} cores, {$env['memory_gb']} GB), {$env['os']}"
        : "{$env['cores']} {$cores} cores, {$env['memory_gb']} GB RAM, ".strtok($env['os'], ',');
    $timeChart = \App\Content\BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r), 'value' => $r['value'] / 1000, 'shown' => $time($r['value'])], $rows), 'Elapsed time (s)');
    // The chart is labeled by the exact metric the record names.
    $metric = \Illuminate\Support\Str::ucfirst(\App\Content\Results::memoryMeasure($record) ?? 'peak resident set size');
    $memoryChart = \App\Content\BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r), 'value' => $r['peak_memory_mib'], 'shown' => $memory($r['peak_memory_mib'])], $rows), "{$metric} (MiB)");
@endphp
<div class="grid gap-8 lg:grid-cols-2 lg:items-start lg:gap-12">
    <div class="min-w-0">
    @if ($code !== [])
        <x-implementation-code :code="$code" :id="$id" :label="$w['label']" :languages="$languages" :names="$w['names'] ?? []" />
    @endif
        <div class="mt-4 space-y-2 text-xs leading-snug text-zinc-500 dark:text-zinc-400">
            <p>Medians of {{ $env['repeats'] }} runs, each in a fresh process.</p>
            <p>{{ $machine }}, PHP {{ $env['php'] }}, recorded {{ $env['date'] }}.</p>
            @if ($measure === 'peak anonymous memory')
                <p>Memory is the peak anonymous memory: the most memory the process allocated at once, including the runtime, the data, and native allocations, but not file pages it maps.</p>
            @elseif ($footprint)
                <p>Memory is the peak memory footprint: the most memory the process held at once, including the runtime, the data, and native allocations, but not file pages the system can drop.</p>
            @else
                <p>Memory is the peak resident set size, including file pages the process mapped.</p>
            @endif
            <p>The full record gives versions, thread settings, and where timing starts and stops.</p>
        </div>
        <a href="{{ $w['url'] }}" aria-label="View the complete {{ strtolower($w['label']) }} benchmark on {{ $w['name'] }}'s Benchmarks page" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
            View the complete benchmark <x-heroicon-m-arrow-right class="size-4" />
        </a>
    </div>

    <div class="min-w-0">
        <h3 class="mb-3 text-base font-semibold text-zinc-950 dark:text-white">{{ $title }}</h3>
        <div class="space-y-6" aria-hidden="true">
            <x-phiz :plot="$timeChart" class="min-w-0 overflow-hidden" />
            <x-phiz :plot="$memoryChart" class="min-w-0 overflow-hidden" />
        </div>
        <div class="sr-only"><table>
            <caption>{{ $w['label'] }}: median elapsed time and {{ strtolower($metric) }} of {{ $env['repeats'] }} runs</caption>
            <thead><tr><th scope="col">Implementation</th><th scope="col">Elapsed time</th><th scope="col">{{ $metric }}</th></tr></thead>
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
