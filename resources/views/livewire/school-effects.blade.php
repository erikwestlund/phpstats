<div>
    <fieldset class="flex flex-wrap gap-6 text-sm">
        <legend class="mb-2 font-medium">Estimation method</legend>
        <label class="flex items-center gap-2">
            <input type="radio" name="method" wire:model.live.boolean="reml" value="1"> REML
        </label>
        <label class="flex items-center gap-2">
            <input type="radio" name="method" wire:model.live.boolean="reml" value="0"> Maximum likelihood
        </label>
    </fieldset>

    @if (! $extensionLoaded)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This PHP process does not have the phit extension, so the model cannot be fitted.
        </p>
    @else
        @php($mixed = $fit['mixed'])

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Fixed effects</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Term</th>
                        <th class="py-2 pr-4 text-right font-medium">Estimate</th>
                        <th class="py-2 pr-4 text-right font-medium">Std. error</th>
                        <th class="py-2 pr-4 text-right font-medium">z</th>
                        <th class="py-2 text-right font-medium">95% CI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mixed['columns'] as $i => $term)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ $term }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($mixed['coefficients'][$i], 3) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($mixed['std_errors'][$i], 3) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($mixed['z_statistics'][$i], 2) }}</td>
                            <td class="py-2 text-right">[{{ number_format($mixed['conf_int'][$i][0], 3) }}, {{ number_format($mixed['conf_int'][$i][1], 3) }}]</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Variance components</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Group</th>
                        <th class="py-2 pr-4 font-medium">Term</th>
                        <th class="py-2 pr-4 text-right font-medium">Variance</th>
                        <th class="py-2 text-right font-medium">Std. dev.</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-zinc-100 dark:border-zinc-900">
                        <td class="py-2 pr-4">school</td>
                        <td class="py-2 pr-4">(Intercept)</td>
                        <td class="py-2 pr-4 text-right">{{ number_format($fit['school_variance'], 3) }}</td>
                        <td class="py-2 text-right">{{ number_format($fit['school_sd'], 3) }}</td>
                    </tr>
                    <tr class="border-b border-zinc-100 dark:border-zinc-900">
                        <td class="py-2 pr-4">Residual</td>
                        <td class="py-2 pr-4"></td>
                        <td class="py-2 pr-4 text-right">{{ number_format($mixed['residual_variance'], 3) }}</td>
                        <td class="py-2 text-right">{{ number_format($mixed['sigma'], 3) }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $mixed['method'] }} fit on {{ $mixed['n_observations'] }} students in {{ count($fit['schools']) }} schools.
                Log-likelihood = {{ number_format($mixed['log_likelihood'], 2) }}, AIC = {{ number_format($mixed['aic'], 1) }}.
                Schools account for {{ number_format(100 * $fit['icc'], 1) }}% of the variance in scores after adjusting for study hours (the intraclass correlation).
            </p>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Shrinkage of school intercepts</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Red points are the no-pooling intercepts. Blue points are the mixed-model intercepts, which move toward the overall intercept (dashed line).
        </p>
        <x-phiz :plot="$fit['chart']" wire:key="school-effects-chart" class="mt-3 w-full" />

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">School</th>
                        <th class="py-2 pr-4 text-right font-medium">Students</th>
                        <th class="py-2 pr-4 text-right font-medium">No pooling</th>
                        <th class="py-2 pr-4 text-right font-medium">Partial pooling</th>
                        <th class="py-2 text-right font-medium">Shrinkage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fit['schools'] as $school)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ $school['school'] }}</td>
                            <td class="py-2 pr-4 text-right">{{ $school['students'] }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($school['no_pooling'], 2) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($school['partial_pooling'], 2) }}</td>
                            <td class="py-2 text-right">{{ number_format(100 * $school['shrinkage'], 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Shrinkage is σ²/n ÷ (τ² + σ²/n), where τ² is the school variance, σ² is the residual variance, and n is the number of students in the school.
                Each school's random intercept is its mean residual from the fixed part of the model, reduced by this share.
                Schools with fewer students are shrunk more.
                Shrinkage is small in these data because the school variance is large compared with σ²/n.
            </p>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                The no-pooling model fits its own study-hours slope, so the distance between its intercepts and the mixed-model intercepts is not exactly this share.
            </p>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">What if</h2>
        @if (! $fit['what_if'])
            <p class="mt-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                This PHP process does not have the phit extension, so it cannot predict for a new student.
            </p>
        @else
            @php($whatIf = $fit['what_if'])
            <div class="mt-3 flex flex-wrap items-end gap-6 text-sm">
                <label class="flex flex-col gap-2">
                    <span class="font-medium">School</span>
                    <select wire:model.live="whatIfSchool" class="rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-700">
                        <option value="new">A school not in the data</option>
                        @foreach ($fit['schools'] as $school)
                            <option value="{{ $school['school'] }}">{{ $school['school'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-2">
                    <span class="font-medium">Study hours</span>
                    <input type="number" min="0" max="20" step="0.5" wire:model.live.debounce.400ms.number="whatIfHours"
                           class="w-24 rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-700">
                </label>
            </div>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                    <dt class="text-zinc-500">Predicted score at {{ $whatIf['school'] === 'new' ? 'a new school' : $whatIf['school'] }}</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($whatIf['conditional'], 1) }}</dd>
                </div>
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                    <dt class="text-zinc-500">Population prediction</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($whatIf['population'], 1) }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Phit\predict() gives both predictions for a student who studies {{ $whatIf['hours'] }} hours.
                The first adds the school's random intercept, as lme4's predict() does by default.
                The second uses the fixed effects only, as predict() does with re.form = NA.
                A school outside the data has no estimated random intercept, so with allow_new_levels it gets the population prediction.
                lme4 does not compute standard errors for these predictions, and neither does Phit\predict().
            </p>
        @endif
    @endif
</div>
