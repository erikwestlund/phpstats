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
                    <x-phiz :plot="$scatter" wire:key="dashboard-scatter" class="mt-3 w-full" />
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Chance of exceeding the budget</h2>
                    @if ($curves)
                        <x-phiz :plot="$curves" wire:key="dashboard-curves" class="mt-3 w-full" />
                    @else
                        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $error }}</p>
                    @endif
                </div>
            </div>

            <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">What if</h2>
            @if (! function_exists('Phit\\predict'))
                <p class="mt-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                    This PHP process does not have the phit extension, so it cannot predict for a new rental.
                </p>
            @else
                <div class="mt-3 flex flex-wrap items-end gap-6 text-sm">
                    <label class="flex flex-col gap-2">
                        <span class="font-medium">Neighborhood</span>
                        <select wire:model.live="whatIfNeighborhood" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach (array_intersect(array_keys(\App\Data\SimulatedRentals::NEIGHBORHOODS), $neighborhoods) as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-medium">Area (m²)</span>
                        <input type="number" min="20" max="200" step="1" wire:model.live.debounce.400ms.number="whatIfArea"
                               class="w-24 rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
                    </label>
                    <label class="flex flex-col gap-2">
                        <span class="font-medium">Bedrooms</span>
                        <select wire:model.live.number="whatIfBedrooms" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach ([1, 2, 3, 4] as $count)
                                <option value="{{ $count }}">{{ $count }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                @if ($whatIf['error'])
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ $whatIf['error'] }}</p>
                @else
                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <dt class="text-zinc-500">Predicted rent</dt>
                            <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($whatIf['rent']['fit']) }}</dd>
                            <dd class="mt-1 tabular-nums text-zinc-600 dark:text-zinc-400">95% prediction interval {{ number_format($whatIf['rent']['lower']) }} to {{ number_format($whatIf['rent']['upper']) }}</dd>
                        </div>
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <dt class="text-zinc-500">Chance the rent is over {{ number_format($budget) }}</dt>
                            @if ($whatIf['over'])
                                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format(100 * $whatIf['over']['fit'], 1) }}%</dd>
                                <dd class="mt-1 tabular-nums text-zinc-600 dark:text-zinc-400">95% confidence interval {{ number_format(100 * $whatIf['over']['lower'], 1) }}% to {{ number_format(100 * $whatIf['over']['upper'], 1) }}%</dd>
                            @else
                                <dd class="mt-1 text-zinc-600 dark:text-zinc-400">No logistic model for this selection.</dd>
                            @endif
                        </div>
                    </dl>
                    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                        Phit\predict() predicts both values for one {{ $whatIf['area'] }} m², {{ $whatIf['bedrooms'] }}-bedroom rental in {{ $whatIf['neighborhood'] }}, using models fitted to the selected rentals.
                        The rent comes from the linear model {{ $whatIf['formula'] }}.
                        Its prediction interval covers a single rental's rent, so it is wider than an interval for the average rent.
                        The chance of exceeding the budget comes from the logistic model below, and its interval is computed on the log-odds scale.
                        @if ($whatIf['extrapolating'])
                            The selection does not include {{ $whatIf['bedrooms'] }}-bedroom rentals, so these predictions extrapolate beyond the data.
                        @endif
                    </p>
                @endif
            @endif

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
