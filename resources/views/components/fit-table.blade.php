{{-- Coefficients of a phit regression fit, like the table in R's summary(). --}}
@props(['fit'])

@php
    $p = fn (float $p) => $p < 0.0001 ? '< 0.0001' : number_format($p, 4);
@endphp
<div {{ $attributes }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm tabular-nums">
            <thead>
                <tr class="text-left font-semibold text-zinc-800 dark:text-zinc-100">
                    <th class="px-4 py-2.5">Term</th>
                    <th class="px-4 py-2.5 text-right">Estimate</th>
                    <th class="px-4 py-2.5 text-right">Std. error</th>
                    <th class="px-4 py-2.5 text-right">t</th>
                    <th class="px-4 py-2.5 text-right">p</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($fit['columns'] as $i => $term)
                    <tr class="text-zinc-600 dark:text-zinc-300">
                        <td class="px-4 py-1.5 font-mono text-xs">{{ $term }}</td>
                        <td class="px-4 py-1.5 text-right">{{ number_format($fit['coefficients'][$i], 2) }}</td>
                        <td class="px-4 py-1.5 text-right">{{ number_format($fit['std_errors'][$i], 2) }}</td>
                        <td class="px-4 py-1.5 text-right">{{ number_format($fit['t_statistics'][$i], 1) }}</td>
                        <td class="px-4 py-1.5 text-right">{{ $p($fit['p_values'][$i]) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="border-t border-zinc-100 px-4 py-2 text-xs text-zinc-500 dark:border-white/5">
        {{ number_format($fit['n_observations']) }} observations, residual standard error {{ number_format($fit['sigma'], 2) }}, R² {{ number_format($fit['r_squared'], 3) }}.
    </p>
</div>
