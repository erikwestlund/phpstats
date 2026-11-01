{{--
    The extra tables of a record that compares interfaces to one program, as
    CmdStanPHP's does: how each interface summarizes the draws, the posterior
    means each one reports with their signed differences from the first, and
    the largest draw and summary differences between each pair.
--}}
@use('App\Content\Results')
@php
    $summaries = is_array($record['summaries'] ?? null) ? $record['summaries'] : [];
    $interfaces = array_keys($summaries);
    $means = array_map(fn ($rows) => array_column($rows, 'mean', 'variable'), $summaries);
    $base = $interfaces[0] ?? null;
    $signed = fn (float $d) => $d == 0.0 ? '0' : ($d > 0 ? '+' : '−').sprintf('%.1e', abs($d));
@endphp

@if ($base)
    <h3 class="mt-10 text-base font-semibold text-zinc-950 dark:text-white">Posterior means by interface</h3>
    @if (is_array($record['summary_source'] ?? null))
        <ul class="mt-2 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
            @foreach ($record['summary_source'] as $interface => $how)
                <li><span class="font-mono">{{ $interface }}</span>: {{ $how }}</li>
            @endforeach
        </ul>
    @endif
    <div class="mt-3 overflow-x-auto">
        <table class="w-full text-left text-sm tabular-nums">
            <caption class="sr-only">Posterior means each interface reports, with the signed difference from {{ $base }}</caption>
            <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                <tr>
                    <th scope="col" class="py-2 pr-4 font-medium">Variable</th>
                    @foreach ($interfaces as $interface)
                        <th scope="col" class="py-2 pr-4 text-right font-mono font-medium last:pr-0">{{ $interface }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($means[$base] as $variable => $mean)
                    <tr>
                        <th scope="row" class="py-1.5 pr-4 font-mono text-xs font-normal">{{ $variable }}</th>
                        @foreach ($interfaces as $interface)
                            @php $value = $means[$interface][$variable] ?? null; @endphp
                            <td class="whitespace-nowrap py-1.5 pr-4 text-right font-mono text-xs last:pr-0">
                                {{ $value === null ? 'not reported' : sprintf('%.6f', $value) }}
                                @if ($interface !== $base && $value !== null)
                                    <span class="ml-1 text-zinc-500 dark:text-zinc-400">{{ $signed($value - $mean) }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Values to six decimal places; the signed number is the difference from <span class="font-mono">{{ $base }}</span>'s unrounded mean.</p>
@endif

@if (! empty($record['draw_differences']))
    <h3 class="mt-10 text-base font-semibold text-zinc-950 dark:text-white">Draws</h3>
    <div class="mt-3 overflow-x-auto">
        <table class="w-full text-left text-sm tabular-nums">
            <caption class="sr-only">Largest difference between the draws of each pair of interfaces</caption>
            <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                <tr><th scope="col" class="py-2 pr-4 font-medium">Interfaces</th><th scope="col" class="py-2 pr-4 text-right font-medium">Draws</th><th scope="col" class="py-2 pr-4 text-right font-medium">Columns</th><th scope="col" class="py-2 text-right font-medium">Max abs. difference</th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($record['draw_differences'] as $pair)
                    <tr>
                        <th scope="row" class="py-1.5 pr-4 font-normal">{!! \App\Content\Libraries::mono(e(implode(' and ', $pair['interfaces'] ?? []))) !!}</th>
                        <td class="py-1.5 pr-4 text-right">{{ number_format($pair['draws_compared'] ?? 0) }}</td>
                        <td class="py-1.5 pr-4 text-right">{{ number_format($pair['columns_compared'] ?? 0) }}</td>
                        <td class="py-1.5 text-right font-mono text-xs">{{ Results::error($pair['max_abs_draw_difference'] ?? null) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if (! empty($record['summary_differences']))
    @php $measures = array_keys($record['summary_differences'][0]['max_abs_difference'] ?? []); @endphp
    <h3 class="mt-10 text-base font-semibold text-zinc-950 dark:text-white">Summaries</h3>
    <div class="mt-3 overflow-x-auto">
        <table class="w-full text-left text-sm tabular-nums">
            <caption class="sr-only">Largest absolute difference between the summaries of each pair of interfaces, by measure</caption>
            <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                <tr>
                    <th scope="col" class="py-2 pr-4 font-medium">Interfaces</th>
                    @foreach ($measures as $measure)<th scope="col" class="py-2 pr-4 text-right font-mono font-medium last:pr-0">{{ $measure }}</th>@endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($record['summary_differences'] as $pair)
                    <tr>
                        <th scope="row" class="py-1.5 pr-4 font-normal">{!! \App\Content\Libraries::mono(e(implode(' and ', $pair['interfaces'] ?? []))) !!}</th>
                        @foreach ($measures as $measure)<td class="py-1.5 pr-4 text-right font-mono text-xs last:pr-0">{{ Results::error($pair['max_abs_difference'][$measure] ?? null) }}</td>@endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if (! empty($record['summary_differences_note']))
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{!! \App\Content\Libraries::mono(e($record['summary_differences_note'])) !!}</p>
    @endif
@endif
