<div>
    <div class="flex flex-wrap gap-8 text-sm">
        <fieldset class="flex flex-wrap gap-6">
            <legend class="mb-2 font-medium">Model</legend>
            @foreach (\App\Livewire\GlmModels::MODELS as $key => $model)
                <label class="flex items-center gap-2">
                    <input type="radio" name="model" value="{{ $key }}" wire:model.live="model"> {{ $model['label'] }}
                </label>
            @endforeach
        </fieldset>
        <fieldset>
            <legend class="mb-2 font-medium">Terms</legend>
            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model.live="interaction"> Include the interaction
            </label>
        </fieldset>
    </div>

    <pre class="mt-6 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>$data = DataFrame::fromCsv('{{ $spec['file'] }}');
$fit = $data |> glm('{{ $formula }}', family: '{{ $spec['family'] }}', link: '{{ $spec['link'] }}');</code></pre>

    @if (! $extensionLoaded)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This PHP process does not have the php_stats extension with stats_glm(), so the model cannot be fitted.
        </p>
    @else
        @php($result = $fit['result'])

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Term</th>
                        <th class="py-2 pr-4 text-right font-medium">Estimate</th>
                        <th class="py-2 pr-4 text-right font-medium">Std. error</th>
                        <th class="py-2 pr-4 text-right font-medium">z</th>
                        <th class="py-2 pr-4 text-right font-medium">p</th>
                        @if ($spec['ratio'])
                            <th class="py-2 text-right font-medium">{{ $spec['ratio'] }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result['columns'] as $i => $term)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ $term }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($result['coefficients'][$i], 4) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($result['std_errors'][$i], 4) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($result['z_statistics'][$i], 2) }}</td>
                            <td class="py-2 pr-4 text-right">{{ $result['p_values'][$i] < 0.0001 ? '< 0.0001' : number_format($result['p_values'][$i], 4) }}</td>
                            @if ($spec['ratio'])
                                <td class="py-2 text-right">{{ $term === '(Intercept)' ? '' : number_format(exp($result['coefficients'][$i]), 3) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                {{ number_format($result['n_observations']) }} observations, {{ $result['family'] }} family with the {{ $result['link'] }} link.
                Residual deviance {{ number_format($result['deviance'], 2) }} on {{ $result['df_residual'] }} degrees of freedom; null deviance {{ number_format($result['null_deviance'], 2) }}.
                AIC = {{ number_format($result['aic'], 2) }}. McFadden's pseudo R² = {{ number_format($fit['pseudo_r2'], 3) }}.
                Converged in {{ $result['iterations'] }} iterations.
            </p>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Predictions</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            The line is the predicted {{ $spec['family'] === 'poisson' ? 'number of calls' : 'probability of passing' }} as {{ str_replace('_', ' ', $spec['x']) }} varies, with the other predictor held at its mean.
            The band is a 95% confidence interval, computed on the link scale and transformed to the response scale.
            Points are the observations.
        </p>
        <x-phiz :plot="$fit['chart']" wire:key="glm-chart" class="mt-3 w-full" />
    @endif
</div>
