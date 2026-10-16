<div>
    <label class="flex items-center gap-3 text-sm">
        <span class="font-medium">Sample size</span>
        <select wire:model.live.number="n" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
            @foreach (\App\Livewire\SimulatedRents::SIZES as $size)
                <option value="{{ $size }}">{{ number_format($size) }}</option>
            @endforeach
        </select>
    </label>

    @if (! $extensionLoaded)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This PHP process does not have the php_stats extension with stats_lm(), so the model cannot be fitted.
        </p>
    @else
        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Sample by neighborhood</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Neighborhood</th>
                        <th class="py-2 pr-4 text-right font-medium">Rentals</th>
                        <th class="py-2 pr-4 text-right font-medium">Mean area (m²)</th>
                        <th class="py-2 text-right font-medium">Mean rent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fit['by_neighborhood'] as $row)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ $row['neighborhood'] }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($row['rentals']) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($row['mean_area'], 1) }}</td>
                            <td class="py-2 text-right">{{ number_format($row['mean_rent'], 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Estimates</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Term</th>
                        <th class="py-2 pr-4 text-right font-medium">True value</th>
                        <th class="py-2 pr-4 text-right font-medium">Estimate</th>
                        <th class="py-2 pr-4 text-right font-medium">Std. error</th>
                        <th class="py-2 pr-4 text-right font-medium">95% CI</th>
                        <th class="py-2 text-right font-medium">CI covers true value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fit['terms'] as $term)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ $term['term'] }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($term['true'], 1) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($term['estimate'], 1) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($term['std_error'], 1) }}</td>
                            <td class="py-2 pr-4 text-right">[{{ number_format($term['lower'], 1) }}, {{ number_format($term['upper'], 1) }}]</td>
                            <td class="py-2 text-right">{{ $term['lower'] <= $term['true'] && $term['true'] <= $term['upper'] ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                n = {{ number_format($fit['n']) }}. Residual SD = {{ number_format($fit['sigma'], 1) }}, and the true value is {{ number_format(\App\Data\SimulatedRentals::SIGMA, 1) }}.
            </p>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Neighborhood effects</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Points are estimates with 95% confidence intervals. Red diamonds mark the true values.
        </p>
        <x-phit :plot="$fit['chart']" wire:key="simulated-rents-chart" class="mt-3 w-full" />
    @endif
</div>
