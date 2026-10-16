<div>
    <fieldset class="flex flex-wrap gap-6 text-sm">
        <legend class="mb-2 font-medium">Predictors</legend>
        @foreach (\App\Livewire\SchoolScores::PREDICTORS as $column => $label)
            <label class="flex items-center gap-2">
                <input type="checkbox" value="{{ $column }}" wire:model.live="predictors">
                {{ $label }}
            </label>
        @endforeach
    </fieldset>

    @if (! $extensionLoaded)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This PHP process does not have the php_stats extension with stats_lm(), so the model cannot be fitted.
        </p>
    @elseif (! $fit)
        <p class="mt-6 text-sm text-zinc-600 dark:text-zinc-400">Select at least one predictor.</p>
    @else
        @php($result = $fit['result'])

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Term</th>
                        <th class="py-2 pr-4 text-right font-medium">Estimate</th>
                        <th class="py-2 pr-4 text-right font-medium">Std. error</th>
                        <th class="py-2 pr-4 text-right font-medium">t</th>
                        <th class="py-2 pr-4 text-right font-medium">p</th>
                        <th class="py-2 text-right font-medium">95% CI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fit['terms'] as $i => $term)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ $term }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($result['coefficients'][$i], 4) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($result['std_errors'][$i], 4) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($result['t_statistics'][$i], 2) }}</td>
                            <td class="py-2 pr-4 text-right">{{ $result['p_values'][$i] < 0.0001 ? '< 0.0001' : number_format($result['p_values'][$i], 4) }}</td>
                            <td class="py-2 text-right">[{{ number_format($result['conf_int'][$i][0], 3) }}, {{ number_format($result['conf_int'][$i][1], 3) }}]</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                n = {{ $result['n_observations'] }}, R² = {{ number_format($result['r_squared'], 4) }},
                adjusted R² = {{ number_format($result['adj_r_squared'], 4) }}, residual SD = {{ number_format($result['sigma'], 3) }}.
            </p>
        </div>

        <x-phiz :plot="$fit['chart']" wire:key="school-scores-chart" class="mt-8 w-full" />
    @endif
</div>
