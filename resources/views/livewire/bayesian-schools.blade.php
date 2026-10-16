<div>
    <label class="flex items-center gap-3 text-sm">
        <span class="font-medium">Interval</span>
        <select wire:model.live.number="level" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
            @foreach (\App\Livewire\BayesianSchools::LEVELS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </label>

    @if (! $ready)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This page needs the php_stats extension with stats_lmer() and the stored posterior draws.
        </p>
    @else
        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Parameters</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Parameter</th>
                        <th class="py-2 pr-4 text-right font-medium">Posterior mean</th>
                        <th class="py-2 pr-4 text-right font-medium">Posterior SD</th>
                        <th class="py-2 pr-4 text-right font-medium">{{ $fit['level_label'] }} interval</th>
                        <th class="py-2 text-right font-medium">stats_lmer (REML)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fit['parameters'] as $p)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ $p['name'] }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($p['mean'], 3) }}</td>
                            <td class="py-2 pr-4 text-right">{{ number_format($p['sd'], 3) }}</td>
                            <td class="py-2 pr-4 text-right">[{{ number_format($p['lower'], 3) }}, {{ number_format($p['upper'], 3) }}]</td>
                            <td class="py-2 text-right">{{ number_format($p['lmer'], 3) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">School intercepts</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Blue points and lines are posterior medians and {{ $fit['level_label'] }} intervals. Red diamonds are the stats_lmer intercepts (fixed intercept plus the conditional mode of the random effect).
        </p>
        <x-phiz :plot="$fit['intercept_chart']" wire:key="bayes-intercepts" class="mt-3 w-full" />

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Between-school SD</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            The histogram shows the posterior draws of τ. The red line is the REML estimate.
            With 12 schools, the posterior for τ is wide and skewed to the right, which a single point estimate does not show.
        </p>
        <x-phiz :plot="$fit['tau_chart']" wire:key="bayes-tau" class="mt-3 w-full" />

        @php($c = $fit['convergence'])
        @php($m = $fit['meta'])
        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">The stored fit</h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            CmdStan {{ $m['cmdstan'] }}, seed {{ $m['seed'] }}, {{ $m['chains'] }} chains of {{ number_format($m['iter_sampling']) }} iterations after {{ number_format($m['iter_warmup']) }} warmup, thinned by {{ $m['thin'] }} to {{ number_format($m['chains'] * $m['iter_sampling'] / $m['thin']) }} draws.
            The largest R-hat is {{ number_format(max($c['rhat']), 3) }}, the smallest bulk effective sample size is {{ number_format(min($c['ess_bulk'])) }}, and there were {{ $m['divergent'] }} divergent transitions.
        </p>
    @endif
</div>
