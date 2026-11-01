{{--
    The landing page's verification section: one comparison at a time, chosen
    with tabs. Each shows phit code on the left, reading a public dataset from
    Rdatasets by URL, and on the right the values that code gives next to R's
    and Python's on the same data, rounded the same way: model results to two
    decimal places, distribution values to three significant figures. Where a
    library's rounded value differs from phit's, its signed difference follows
    it, green with a plus when the library's value is larger and red with a
    minus when it is smaller; a dash marks a library with no equivalent.
    phit's values come from running resources/examples/verified/*.php and are
    cached like the other landing examples, so a cold cache fetches the
    datasets once. R's and Python's come from scripts/verification/build,
    saved in database/data/verification/references.json. Each panel links
    phit's verification record for that model, and its note links the
    dataset's source and license in phpstats/datasets.
--}}
@php
    $references = \App\Data\VerificationReferences::load();
    $phit = \App\Content\Package::find('phit');
    $record = $phit->url('verification');

    // A signed difference: "+2.5 × 10⁻¹⁶", or "0" when the values are identical.
    $diff = function (float $d): array {
        if ($d == 0.0) {
            return ['0', 'text-zinc-500 dark:text-zinc-400'];
        }
        [$mantissa, $exponent] = explode('e', sprintf('%.1e', abs($d)));

        return [($d > 0 ? '+' : '−').$mantissa.' × 10<sup>'.(int) $exponent.'</sup>', $d > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'];
    };
    // Values as shown: model results to two decimal places, distribution
    // values to three significant figures.
    $value = function (float $x, bool $significant): string {
        if (! $significant) {
            return str_replace('-', '−', number_format($x, 2, '.', ','));
        }
        if ($x != 0.0 && (abs($x) < 1e-3 || abs($x) >= 1e4)) {
            [$mantissa, $exponent] = explode('e', sprintf('%.2e', $x));

            return $mantissa.' × 10<sup>'.(int) $exponent.'</sup>';
        }

        return sprintf('%.3g', $x);
    };

    // Each fitted model's rows: its coefficients by term, then the extra
    // quantities listed as [label, phit's key or getter, the references' key].
    $model = fn (array $extras) => function (array $fit, array $refs) use ($extras) {
        $rows = [];
        foreach ($fit['columns'] as $i => $term) {
            $rows[] = [$term, $fit['coefficients'][$i], array_map(fn ($r) => $r === null ? null : $r['coefficients'][$i], $refs), true];
        }
        foreach ($extras as [$label, $mine, $theirs]) {
            $rows[] = [$label, is_callable($mine) ? $mine($fit) : $fit[$mine], array_map(fn ($r) => $r[$theirs] ?? null, $refs), false];
        }

        return $rows;
    };
    $groupVariance = fn (array $fit) => $fit['random'][0]['covariance'][0][0] ?? $fit['random_intercept_variance'];
    $python = 'statsmodels (Python)';

    $panels = [
        'distributions' => [
            'dataset' => null,
            'record' => 'Probability distributions',
            'label' => 'Probability distributions',
            'example' => 'verified/distributions',
            'reference' => 'distributions',
            'libraries' => ['R' => 'R', 'Python' => 'SciPy (Python)'],
            'relative' => true,
            'note' => 'R: <code>pnorm</code>, <code>qnorm</code>, <code>pgamma</code>, and <code>qgamma</code>. Python: SciPy\'s <code>norm</code> and <code>gamma</code>.',
            'anchor' => 'distributions',
            'rows' => function (array $values, array $refs) {
                $points = [
                    ['Normal CDF at −8', 'normal_cdf'],
                    ['Normal quantile at 0.975', 'normal_quantile'],
                    ['Gamma CDF at 12', 'gamma_cdf'],
                    ['Gamma quantile at 0.999', 'gamma_quantile'],
                ];

                return array_map(fn ($p, $i) => [$p[0], $values[$i], array_map(fn ($r) => $r[$p[1]], $refs), false], $points, array_keys($points));
            },
        ],
        'ols' => [
            'dataset' => 'mtcars',
            'record' => 'OLS regression',
            'label' => 'OLS',
            'example' => 'verified/ols',
            'reference' => 'ols',
            'libraries' => ['R' => 'lm (R)', 'Python' => $python],
            'relative' => false,
            'note' => 'Motor Trend cars (<code>mtcars</code>). R: <code>lm()</code>. Python: <code>ols()</code>.',
            'anchor' => 'ols',
            'rows' => $model([['Residual SD', 'sigma', 'sigma'], ['R²', 'r_squared', 'r_squared']]),
        ],
        'logistic' => [
            'dataset' => 'mroz',
            'record' => 'Logistic regression',
            'label' => 'Logistic',
            'example' => 'verified/logistic',
            'reference' => 'logistic',
            'libraries' => ['R' => 'glm (R)', 'Python' => $python],
            'relative' => false,
            'note' => 'Labor force participation of 753 married women (Mroz). R: <code>glm(family = binomial)</code>. Python: <code>logit()</code>.',
            'anchor' => 'logistic',
            'rows' => $model([['Log-likelihood', 'log_likelihood', 'log_likelihood']]),
        ],
        'probit' => [
            'dataset' => 'mroz',
            'record' => 'Probit regression',
            'label' => 'Probit',
            'example' => 'verified/probit',
            'reference' => 'probit',
            'libraries' => ['R' => 'glm (R)', 'Python' => $python],
            'relative' => false,
            'note' => 'The same Mroz data with the probit link. R: <code>glm(family = binomial("probit"))</code>. Python: <code>probit()</code>.',
            'anchor' => 'probit',
            'rows' => $model([['Log-likelihood', 'log_likelihood', 'log_likelihood']]),
        ],
        'mixed-gaussian' => [
            'dataset' => 'sleepstudy',
            'record' => 'Gaussian mixed model',
            'label' => 'Mixed Gaussian',
            'example' => 'verified/mixed-gaussian',
            'reference' => 'mixed_gaussian',
            'libraries' => ['R' => 'lme4 (R)', 'Python' => $python],
            'relative' => false,
            'note' => 'lme4\'s sleep deprivation study (<code>sleepstudy</code>), fitted by REML. R: <code>lmer()</code>. Python: <code>mixedlm()</code>.',
            'anchor' => 'lmm',
            'rows' => $model([['Subject variance', $groupVariance, 'group_variance'], ['Residual variance', 'residual_variance', 'residual_variance'], ['Log-likelihood', 'log_likelihood', 'log_likelihood']]),
        ],
        'mixed-logistic' => [
            'dataset' => 'cbpp',
            'record' => 'Mixed logistic model',
            'label' => 'Mixed logistic',
            'example' => 'verified/mixed-logistic',
            'reference' => 'mixed_logistic',
            'libraries' => ['R' => 'lme4 (R)', 'Python' => 'GPBoost (Python)'],
            'relative' => false,
            'note' => 'lme4\'s contagious bovine pleuropneumonia data (<code>cbpp</code>), Laplace approximation. R: <code>glmer()</code>. Python: GPBoost, because statsmodels has no maximum-likelihood mixed logistic model.',
            'anchor' => 'logistic-mixed',
            'rows' => $model([['Herd variance', $groupVariance, 'group_variance'], ['Log-likelihood', 'log_likelihood', 'log_likelihood']]),
        ],
    ];

    // phit's values come from running each example, and the table is cached
    // like the other landing examples, keyed by the example and the stored
    // references it is compared with.
    foreach ($panels as $key => &$panel) {
        $panel['code'] = \App\Content\Example::code($panel['example'])['pipe'];
        try {
            $panel['table'] = \App\Content\Example::output(
                $panel['example'],
                'verified-table',
                fn (mixed $result) => view('components.landing.verified-table', [
                    'panel' => $panel,
                    'key' => $key,
                    'rows' => ($panel['rows'])($result, array_map(
                        fn (string $library) => $references[$library][$panel['reference']] ?? null,
                        array_combine(array_keys($panel['libraries']), array_keys($panel['libraries'])),
                    )),
                    'value' => $value,
                    'diff' => $diff,
                ])->render(),
                files: [\App\Data\VerificationReferences::path()],
            );
        } catch (\Throwable $e) {
            report($e);
            $panel['table'] = '<p class="text-sm text-zinc-500">This server can\'t run the example, so phit\'s values aren\'t shown.</p>';
        }
    }
    unset($panel);
    $first = array_key_first($panels);
@endphp
<section id="verified" aria-labelledby="verified-heading" class="scroll-mt-16 border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="verified-heading" class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Verified against established libraries</h2>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg leading-snug text-zinc-600 dark:text-zinc-400">
            <span class="font-mono">phit</span>'s results next to R and Python on the same data.
        </p>

        <div x-data="{ check: @js($first) }" class="mt-10 sm:mt-12">
            <div role="tablist" aria-label="Comparison" class="mx-auto flex w-fit max-w-full flex-wrap justify-center gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-white/5">
                @foreach ($panels as $key => $panel)
                    <button type="button" role="tab" id="check-tab-{{ $key }}" aria-controls="check-{{ $key }}"
                            x-on:click="check = '{{ $key }}'" x-bind:aria-selected="(check === '{{ $key }}').toString()" aria-selected="{{ $key === $first ? 'true' : 'false' }}"
                            class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 aria-selected:bg-white aria-selected:text-zinc-900 aria-selected:shadow-sm dark:text-zinc-400 dark:hover:text-white dark:aria-selected:bg-white/10 dark:aria-selected:text-white">
                        {{ $panel['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach ($panels as $key => $panel)
                <div role="tabpanel" id="check-{{ $key }}" aria-labelledby="check-tab-{{ $key }}" x-show="check === '{{ $key }}'" @unless ($key === $first) x-cloak @endunless
                     class="mt-8 grid gap-8 lg:grid-cols-2 lg:items-start lg:gap-12">
                    <x-code :code="$panel['code']" class="min-w-0" />

                    <div class="min-w-0">
                        {!! $panel['table'] !!}
                        <p class="mt-3 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                            {!! \App\Content\Libraries::mono($panel['note']) !!}
                            @if ($panel['dataset'])
                                <a href="https://github.com/phpstats/datasets#{{ $panel['dataset'] }}" class="underline decoration-zinc-300 underline-offset-2 hover:text-zinc-700 dark:decoration-zinc-600 dark:hover:text-zinc-200">Data source</a>.
                            @endif
                        </p>
                        <a href="{{ $record }}#{{ $panel['anchor'] }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                            {{ $panel['record'] }} verification record <x-heroicon-m-arrow-right class="size-4" />
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
