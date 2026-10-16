<div class="grid gap-8 lg:grid-cols-[14rem_1fr]">
    <aside class="space-y-6 text-sm">
        <fieldset>
            <legend class="mb-2 font-medium">Neighborhoods</legend>
            @foreach (array_keys(\App\Data\SimulatedRentals::NEIGHBORHOODS) as $name)
                <label class="flex items-center gap-2 py-0.5">
                    <input type="checkbox" value="{{ $name }}" wire:model.live="neighborhoods"> {{ $name }}
                </label>
            @endforeach
        </fieldset>

        <fieldset class="flex items-center gap-2">
            <legend class="mb-2 font-medium">Bedrooms</legend>
            <select wire:model.live.number="minBedrooms" aria-label="Minimum bedrooms" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
                @foreach (range(1, 4) as $b) <option value="{{ $b }}">{{ $b }}</option> @endforeach
            </select>
            <span>to</span>
            <select wire:model.live.number="maxBedrooms" aria-label="Maximum bedrooms" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
                @foreach (range(1, 4) as $b) <option value="{{ $b }}">{{ $b }}</option> @endforeach
            </select>
        </fieldset>

        <label class="block">
            <span class="mb-2 block font-medium">Monthly budget</span>
            <select wire:model.live.number="budget" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
                @foreach (\App\Livewire\RentDashboard::BUDGETS as $b) <option value="{{ $b }}">{{ number_format($b) }}</option> @endforeach
            </select>
        </label>

        <p class="text-zinc-600 dark:text-zinc-400" wire:loading.remove>{{ number_format($rentals) }} rentals selected.</p>
        <p class="text-zinc-600 dark:text-zinc-400" wire:loading>Updating…</p>
    </aside>

    <div class="min-w-0">
        @if ($summary === [])
            <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">{{ $error }}</p>
        @else
            <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">By neighborhood</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-sm tabular-nums">
                    <thead class="text-left text-zinc-500">
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="py-2 pr-4 font-medium">Neighborhood</th>
                            <th class="py-2 pr-4 text-right font-medium">Rentals</th>
                            <th class="py-2 pr-4 text-right font-medium">Median rent</th>
                            <th class="py-2 pr-4 text-right font-medium">Mean area (m²)</th>
                            <th class="py-2 text-right font-medium">Over {{ number_format($budget) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary as $row)
                            <tr class="border-b border-zinc-100 dark:border-zinc-900">
                                <td class="py-2 pr-4">{{ $row['neighborhood'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row['rentals']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row['median_rent']) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row['mean_area'], 1) }}</td>
                                <td class="py-2 text-right">{{ number_format(100 * $row['share_over_budget'], 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-8 grid gap-8 xl:grid-cols-2">
                <div class="min-w-0">
                    <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Rent and area</h2>
                    <x-phit :plot="$scatter" wire:key="dashboard-scatter" class="mt-3 w-full" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Chance of exceeding the budget</h2>
                    @if ($curves)
                        <x-phit :plot="$curves" wire:key="dashboard-curves" class="mt-3 w-full" />
                    @else
                        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $error }}</p>
                    @endif
                </div>
            </div>

            @if ($model)
                <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Logistic model: {{ $formula }}</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-sm tabular-nums">
                        <thead class="text-left text-zinc-500">
                            <tr class="border-b border-zinc-200 dark:border-zinc-800">
                                <th class="py-2 pr-4 font-medium">Term</th>
                                <th class="py-2 pr-4 text-right font-medium">Log odds</th>
                                <th class="py-2 pr-4 text-right font-medium">Std. error</th>
                                <th class="py-2 text-right font-medium">Odds ratio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($model['columns'] as $i => $term)
                                <tr class="border-b border-zinc-100 dark:border-zinc-900">
                                    <td class="py-2 pr-4">{{ $term }}</td>
                                    <td class="py-2 pr-4 text-right">{{ number_format($model['coefficients'][$i], 3) }}</td>
                                    <td class="py-2 pr-4 text-right">{{ number_format($model['std_errors'][$i], 3) }}</td>
                                    <td class="py-2 text-right">{{ $term === '(Intercept)' ? '' : number_format(exp($model['coefficients'][$i]), 3) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                        n = {{ number_format($model['n_observations']) }}, residual deviance = {{ number_format($model['deviance'], 1) }} against a null deviance of {{ number_format($model['null_deviance'], 1) }}, AIC = {{ number_format($model['aic'], 1) }}.
                    </p>
                </div>
            @endif
        @endif
    </div>
</div>
