<div>
    <div class="flex flex-wrap gap-8 text-sm">
        <fieldset class="flex flex-wrap gap-6">
            <legend class="mb-2 font-medium">Random effects</legend>
            @foreach (\App\Livewire\RandomEffects::STRUCTURES as $key => $structure)
                <label class="flex items-center gap-2">
                    <input type="radio" name="structure" value="{{ $key }}" wire:model.live="structure"> {{ $structure['label'] }}
                </label>
            @endforeach
        </fieldset>
        <label class="flex flex-col gap-2">
            <span class="font-medium">Interval</span>
            <select wire:model.live.number="level" class="rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-700">
                @foreach (\App\Livewire\RandomEffects::LEVELS as $option)
                    <option value="{{ $option }}">{{ $option }}%</option>
                @endforeach
            </select>
        </label>
    </div>

    <x-code-tabs class="mt-6">
        <x-slot:chain>$fit = phplyr(DataFrame::fromQuery(StudentScore::query()))->lmer('{{ $formula }}');

$fit['random'][0]['effects'];           // conditional modes, one row per school
$fit['random'][0]['effects_cond_var'];  // their conditional covariance, one matrix per school

random_effects_plot($fit, level: {{ $level / 100 }});</x-slot:chain>
        <x-slot:pipe>$fit = DataFrame::fromQuery(StudentScore::query()) |> lmer('{{ $formula }}');

$fit['random'][0]['effects'];           // conditional modes, one row per school
$fit['random'][0]['effects_cond_var'];  // their conditional covariance, one matrix per school

random_effects_plot($fit, level: {{ $level / 100 }});</x-slot:pipe>
    </x-code-tabs>

    @if (! $extensionLoaded)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This PHP process does not have the php_stats extension with stats_lmer(), so the model cannot be fitted.
        </p>
    @else
        @php($result = $fit['result'])
        @php($term = $fit['term'])

        @if ($result['singular'])
            <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                This fit is singular: the estimated random-effect covariance matrix is at the boundary of its parameter space.
                In these data the study-hours slope barely varies between schools, so the slope variance is estimated at or near zero.
                The slope effects and their intervals collapse toward zero.
            </p>
        @endif

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Caterpillar plot</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Each point is a school's conditional mode, its estimated deviation from the fixed effects.
            Each bar is a {{ $fit['level'] }}% interval of ±z standard deviations, where the standard deviation is the square root of the conditional variance.
            Schools are sorted by their intercept.
        </p>
        <x-phiz :plot="$fit['chart']" wire:key="random-effects-chart" class="mt-3 w-full" />

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Variance components</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Term</th>
                        <th class="py-2 pr-4 text-right font-medium">Variance</th>
                        <th class="py-2 text-right font-medium">Std. dev.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($term['columns'] as $a => $column)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">school: {{ $column }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($term['covariance'][$a][$a], 4) }}</td>
                            <td class="py-2 text-right">{{ number_format(sqrt($term['covariance'][$a][$a]), 4) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-b border-zinc-100 dark:border-zinc-900">
                        <td class="py-2 pr-4">Residual</td>
                        <td class="py-2 pr-4 text-right">{{ number_format($result['residual_variance'], 4) }}</td>
                        <td class="py-2 text-right">{{ number_format($result['sigma'], 4) }}</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $result['method'] }} fit on {{ $result['n_observations'] }} students in {{ count($fit['schools']) }} schools.
                AIC = {{ number_format($result['aic'], 1) }}.
            </p>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Conditional modes and standard deviations</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">School</th>
                        <th class="py-2 pr-4 text-right font-medium">Students</th>
                        @foreach ($term['columns'] as $column)
                            <th class="py-2 pr-4 text-right font-medium">{{ $column }}</th>
                            <th class="py-2 pr-4 text-right font-medium">Cond. SD</th>
                        @endforeach
                        @if (count($term['columns']) === 1)
                            <th class="py-2 text-right font-medium">Closed form</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fit['schools'] as $school)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ $school['school'] }}</td>
                            <td class="py-2 pr-4 text-right">{{ $school['students'] }}</td>
                            @foreach ($school['effects'] as $effect)
                                <td class="py-2 pr-4 text-right">{{ number_format($effect['mode'], 4) }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($effect['sd'], 4) }}</td>
                            @endforeach
                            @isset($school['closed_form_sd'])
                                <td class="py-2 text-right">{{ number_format($school['closed_form_sd'], 4) }}</td>
                            @endisset
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if (count($term['columns']) === 1)
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                    With one random intercept, the conditional variance of a school's effect is τ²σ² / (σ² + nτ²), where τ² is the school variance, σ² is the residual variance, and n is the number of students.
                    The closed-form column computes it from the variance components and matches the reported value.
                    Schools with more students have smaller conditional standard deviations.
                </p>
            @else
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                    With random slopes, each school's conditional covariance is a 2 × 2 matrix.
                    The table shows the square roots of its diagonal.
                </p>
            @endif
        </div>
    @endif
</div>
