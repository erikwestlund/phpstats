{{--
    How many times faster the first implementation in a benchmark report is
    than the second, case by case.
--}}
@props(['package', 'benchmark'])

@php
    $package = \App\Content\Package::find($package);
    $report = collect($package->benchmarks())->firstWhere('id', $benchmark);
    $pivot = \App\Content\Results::pivot($report);
    [$fast, $slow] = $pivot['implementations'];
    $rows = collect($pivot['cases'])
        ->filter(fn ($case) => ($case['values'][$fast] ?? 0) > 0 && ($case['values'][$slow] ?? 0) > 0)
        ->map(fn ($case) => ['case' => $case['case'], 'ratio' => $case['values'][$slow] / $case['values'][$fast], 'fast' => $case['values'][$fast], 'slow' => $case['values'][$slow]])
        ->values();
    $max = max(1, $rows->max('ratio'));
@endphp
<div class="not-prose">
    <div class="space-y-3">
        @foreach ($rows as $row)
            <div>
                <div class="flex items-baseline justify-between gap-4 text-sm">
                    <span class="text-zinc-700 dark:text-zinc-300">{{ $row['case'] }}</span>
                    <span class="font-semibold tabular-nums text-violet-700 dark:text-violet-300">{{ number_format($row['ratio'], $row['ratio'] < 10 ? 1 : 0) }}×</span>
                </div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/5">
                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-400 via-violet-500 to-fuchsia-500" style="width: {{ max(3, round(100 * log($row['ratio'] + 1) / log($max + 1))) }}%"></div>
                </div>
                <p class="mt-1 text-xs tabular-nums text-zinc-500">{{ \App\Content\Results::number($row['fast']) }} {{ $report['unit'] }} with {{ $fast }}, {{ \App\Content\Results::number($row['slow']) }} {{ $report['unit'] }} with {{ $slow }}</p>
            </div>
        @endforeach
    </div>
    <p class="mt-4 text-xs text-zinc-500">
        From <a href="{{ $package->url('benchmarks') }}#{{ $report['id'] }}" class="underline decoration-zinc-300 underline-offset-2 hover:text-zinc-800 dark:hover:text-zinc-200">{{ $report['title'] }}</a>{{ isset($report['environment']['cpu']) ? ', on '.$report['environment']['cpu'] : '' }}. Bar lengths use a log scale.
    </p>
</div>
