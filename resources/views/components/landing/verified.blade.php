{{--
    The landing page's verification evidence, read from phit's synced
    verification records: two distribution families against 60-digit mpmath
    references, and two models against R and statsmodels. Each table shows
    the largest difference the record reports, and links the record on
    phit's Verification section, which lists versions, data, and limitations.
    A record that has not synced leaves its table out.
--}}
@php
    $phit = \App\Content\Package::find('phit');
    $records = collect($phit->verifications())->keyBy('id');
    $sci = function (float $x): string {
        if ($x == 0.0) {
            return '0';
        }
        [$mantissa, $exponent] = explode('e', sprintf('%.1e', $x));

        return $mantissa.' × 10<sup>'.(int) $exponent.'</sup>';
    };

    $families = [];
    if ($record = $records->get('distributions')) {
        foreach (['normal' => 'Normal', 'gamma' => 'Gamma (shape, scale)'] as $family => $label) {
            $cases = array_values(array_filter($record['cases'], fn ($c) => str_starts_with($c['name'], $family.'(')));
            $families[] = [
                'label' => $label,
                'sets' => count(array_unique(array_column($cases, 'name'))),
                'points' => array_sum(array_column($cases, 'n_compared')),
                'error' => max(array_column($cases, 'max_rel_error')),
            ];
        }
    }

    $models = [];
    $quantities = fn (array $record, string $case, string $peer, array $labels) => collect($labels)
        ->map(function (string $label, string $quantity) use ($record, $case, $peer) {
            $row = collect($record['cases'])->first(fn ($c) => $c['name'] === $case && $c['quantity'] === $quantity);

            return $row ? ['label' => $label, 'phit' => $row['max_abs_error'], 'peer' => $row['peers'][$peer] ?? null] : null;
        })->filter()->values()->all();
    if ($record = $records->get('lmm-public')) {
        $models[] = [
            'id' => 'lmm-public',
            'title' => 'Linear mixed model',
            'spec' => '<code>Reaction ~ Days + (1 | Subject)</code> on lme4\'s sleepstudy data, 180 observations, fitted by maximum likelihood.',
            'reference' => 'R: lme4 <code>lmer(REML = FALSE)</code>. Python: statsmodels <code>MixedLM</code>.',
            'rows' => $quantities($record, 'sleepstudy', 'Python', [
                'coefficients' => 'Coefficients',
                'std_errors' => 'Standard errors',
                'residual_variance' => 'Residual variance',
                'random_intercept_variance' => 'Intercept variance',
                'log_likelihood' => 'Log-likelihood',
            ]),
        ];
    }
    if ($record = $records->get('logistic')) {
        $models[] = [
            'id' => 'logistic',
            'title' => 'Logistic regression',
            'spec' => '<code>passed ~ study_hours + practice_score</code> on 80 simulated students, fitted by maximum likelihood.',
            'reference' => 'R: <code>glm(family = binomial)</code>. Python: statsmodels <code>Logit</code>.',
            'rows' => $quantities($record, 'simulated_learning', 'statsmodels', [
                'coefficients' => 'Coefficients',
                'std_errors' => 'Standard errors',
                'deviance' => 'Deviance',
                'log_likelihood' => 'Log-likelihood',
            ]),
        ];
    }
    $th = 'px-4 py-2 font-medium';
    $td = 'whitespace-nowrap px-4 py-2 tabular-nums';
@endphp
@if ($families !== [] || $models !== [])
<section id="verified" aria-labelledby="verified-heading" class="scroll-mt-16 border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="verified-heading" class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Verified against established libraries</h2>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg leading-snug text-zinc-600 dark:text-zinc-400">
            Four of the checks in <span class="font-mono">phit</span>'s verification records. Each value is the largest difference found across the record's test points.
        </p>

        <div class="mt-10 grid gap-6 sm:mt-12 lg:grid-cols-3">
            @if ($families !== [])
                <article class="min-w-0 rounded-xl p-5 ring-1 ring-zinc-200 sm:p-6 dark:ring-white/10">
                    <h3 class="text-lg font-semibold">Probability distributions</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                        Densities, distribution and survival functions, quantiles, and their logarithms, against 60-digit mpmath values at R's test points. R and SciPy are evaluated at the same points. The record's tolerance is 10<sup>−12</sup> relative.
                    </p>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                                <tr><th scope="col" class="{{ $th }} pl-0">Family</th><th scope="col" class="{{ $th }} text-right">Points</th><th scope="col" class="{{ $th }} pr-0 text-right">Largest relative error</th></tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                                @foreach ($families as $family)
                                    <tr>
                                        <th scope="row" class="{{ $td }} pl-0 font-normal">{{ $family['label'] }}<span class="block text-xs text-zinc-500">{{ $family['sets'] }} parameter sets</span></th>
                                        <td class="{{ $td }} text-right">{{ number_format($family['points']) }}</td>
                                        <td class="{{ $td }} pr-0 text-right">{!! $sci($family['error']) !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ $phit->url('verification') }}#distributions" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                        Distribution verification record <x-heroicon-m-arrow-right class="size-4" />
                    </a>
                </article>
            @endif

            @foreach ($models as $model)
                <article class="min-w-0 rounded-xl p-5 ring-1 ring-zinc-200 sm:p-6 dark:ring-white/10">
                    <h3 class="text-lg font-semibold">{{ $model['title'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">{!! $model['spec'] !!} {!! $model['reference'] !!}</p>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">Largest absolute difference from R</caption>
                            <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                                <tr><th scope="col" class="{{ $th }} pl-0">Difference from R</th><th scope="col" class="{{ $th }} text-right font-mono">phit</th><th scope="col" class="{{ $th }} pr-0 text-right">statsmodels</th></tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                                @foreach ($model['rows'] as $row)
                                    <tr>
                                        <th scope="row" class="{{ $td }} pl-0 font-normal">{{ $row['label'] }}</th>
                                        <td class="{{ $td }} text-right">{!! $sci($row['phit']) !!}</td>
                                        <td class="{{ $td }} pr-0 text-right">{!! $row['peer'] === null ? '–' : $sci($row['peer']) !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ $phit->url('verification') }}#{{ $model['id'] }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                        {{ $model['title'] }} verification record <x-heroicon-m-arrow-right class="size-4" />
                    </a>
                </article>
            @endforeach
        </div>

        <p class="mx-auto mt-8 max-w-3xl text-center text-sm leading-6 text-zinc-600 dark:text-zinc-400">
            The values agree to within each record's tolerance, not exactly. Mixed-model variances are found by numerical optimization, so they differ from R by more than the closed-form quantities do, and statsmodels differs from R by a similar amount. The records list the software versions and what the checks do not cover.
        </p>
    </div>
</section>
@endif
