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
    $value = function (int|float $x, bool $significant): string {
        if (is_int($x)) {
            return number_format($x);
        }
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
            'title' => 'Normal and gamma probabilities and quantiles',
            'model' => 'Distribution and quantile functions; gamma with shape 2 and scale 3',
            'dataset' => null,
            'record' => 'Distributions',
            'label' => 'Distributions',
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
        'csv' => [
            'title' => 'Weather at New York City airports in 2013',
            'model' => 'A CSV file of 26,115 hourly readings, summarized by airport, skipping missing values',
            'dataset' => 'weather',
            'record' => 'CSV processing',
            'label' => 'CSV processing',
            'primary' => 'phibble',
            'example' => 'verified/csv-phibble',
            'also' => ['phpolars' => 'verified/csv-phpolars'],
            'reference' => 'csv',
            'libraries' => ['phpolars' => 'phpolars', 'R' => 'dplyr (R)', 'Python' => 'pandas (Python)'],
            'relative' => false,
            'note' => 'R: readr and dplyr. Python: pandas.',
            'anchor' => null,
            'rows' => function (array $rows, array $refs) {
                $out = [];
                foreach ($rows as $i => $row) {
                    foreach (['hours' => 'hours', 'temp' => 'mean temperature', 'humid' => 'mean humidity'] as $field => $what) {
                        $out[] = ["{$row['origin']} {$what}", $row[$field], array_map(fn ($r) => $r === null ? null : $r[$field][$i], $refs), false];
                    }
                }

                return $out;
            },
        ],
        'ols' => [
            'title' => 'Fuel economy of 32 cars',
            'model' => '<code>mpg ~ wt + hp</code>, ordinary least squares',
            'dataset' => 'mtcars',
            'record' => 'OLS regression',
            'label' => 'OLS',
            'example' => 'verified/ols',
            'reference' => 'ols',
            'libraries' => ['R' => 'lm (R)', 'Python' => $python],
            'relative' => false,
            'note' => 'R: <code>lm()</code>. Python: <code>ols()</code>.',
            'anchor' => 'ols',
            'rows' => $model([['Residual SD', 'sigma', 'sigma'], ['R²', 'r_squared', 'r_squared']]),
        ],
        'logistic' => [
            'title' => 'Labor force participation of 753 married women',
            'model' => '<code>lfp ~ k5 + k618 + age + wc + hc + lwg + inc</code>, logistic regression',
            'dataset' => 'mroz',
            'record' => 'Logistic regression',
            'label' => 'Logistic',
            'example' => 'verified/logistic',
            'reference' => 'logistic',
            'libraries' => ['R' => 'glm (R)', 'Python' => $python],
            'relative' => false,
            'note' => 'R: <code>glm(family = binomial)</code>. Python: <code>logit()</code>.',
            'anchor' => 'logistic',
            'rows' => $model([['Log-likelihood', 'log_likelihood', 'log_likelihood']]),
        ],
        'probit' => [
            'title' => 'Labor force participation of 753 married women',
            'model' => '<code>lfp ~ k5 + k618 + age + wc + hc + lwg + inc</code>, probit regression',
            'dataset' => 'mroz',
            'record' => 'Probit regression',
            'label' => 'Probit',
            'example' => 'verified/probit',
            'reference' => 'probit',
            'libraries' => ['R' => 'glm (R)', 'Python' => $python],
            'relative' => false,
            'note' => 'R: <code>glm(family = binomial("probit"))</code>. Python: <code>probit()</code>.',
            'anchor' => 'probit',
            'rows' => $model([['Log-likelihood', 'log_likelihood', 'log_likelihood']]),
        ],
        'mixed-gaussian' => [
            'title' => 'Reaction times under sleep deprivation',
            'model' => '<code>Reaction ~ Days + (1 | Subject)</code>, linear mixed model fitted by REML',
            'dataset' => 'sleepstudy',
            'record' => 'Gaussian mixed model',
            'label' => 'Mixed Gaussian',
            'example' => 'verified/mixed-gaussian',
            'reference' => 'mixed_gaussian',
            'libraries' => ['R' => 'lme4 (R)', 'Python' => $python],
            'relative' => false,
            'note' => 'R: <code>lmer()</code>. Python: <code>mixedlm()</code>.',
            'anchor' => 'lmm',
            'rows' => $model([['Subject variance', $groupVariance, 'group_variance'], ['Residual variance', 'residual_variance', 'residual_variance'], ['Log-likelihood', 'log_likelihood', 'log_likelihood']]),
        ],
        'mixed-logistic' => [
            'title' => 'Pleuropneumonia in 15 cattle herds',
            'model' => '<code>cbind(incidence, size - incidence) ~ period + (1 | herd)</code>, mixed logistic regression with the Laplace approximation',
            'dataset' => 'cbpp',
            'record' => 'Mixed logistic model',
            'label' => 'Mixed logistic',
            'example' => 'verified/mixed-logistic',
            'reference' => 'mixed_logistic',
            'libraries' => ['R' => 'lme4 (R)', 'Python' => 'GPBoost (Python)'],
            'relative' => false,
            'note' => 'R: <code>glmer()</code>. Python: GPBoost.',
            'anchor' => 'logistic-mixed',
            'rows' => $model([['Herd variance', $groupVariance, 'group_variance'], ['Log-likelihood', 'log_likelihood', 'log_likelihood']]),
        ],
        'bayesian' => [
            'title' => 'Coaching effects in eight schools',
            'model' => '<code>y ~ normal(mu + tau * theta, sigma)</code>, a hierarchical model sampled by NUTS in four chains',
            'dataset' => 'schools',
            'record' => 'Bayesian interfaces',
            'label' => 'Mixed Gaussian (Bayesian)',
            'primary' => 'CmdStanPHP',
            'stored' => 'interfaces',
            'libraries' => ['R' => 'CmdStanR (R)', 'Python' => 'CmdStanPy (Python)'],
            'relative' => false,
            'note' => 'R: CmdStanR. Python: CmdStanPy. All three run the same CmdStan executable, and their draws are identical.',
            'anchor' => 'interfaces',
        ],
    ];

    // phit's values come from running each example, and the table is cached
    // like the other landing examples, keyed by the example and the stored
    // references it is compared with.
    foreach ($panels as $key => &$panel) {
        if (isset($panel['stored'])) {
            // A Stan fit the page cannot run: CmdStanPHP's saved comparison
            // record holds its code and each interface's summary.
            $cmdstan = \App\Content\Package::find('cmdstanphp');
            $saved = collect($cmdstan->verifications())->firstWhere('id', $panel['stored']);
            $panel['link'] = $cmdstan->url('verification').'#'.$panel['anchor'];
            if ($saved === null) {
                $panel['code'] = '';
                $panel['table'] = '<p class="text-sm text-zinc-500">The saved comparison has not synced yet.</p>';
                continue;
            }
            $panel['code'] = trim(preg_replace(['/^<\?php\s*/', "/^require 'vendor\/autoload.php';\n+/m", '/\n+print_r\(.*\);\s*$/s'], '', $saved['code']))."\n";
            $means = fn (string $interface) => array_column($saved['summaries'][$interface], 'mean', 'variable');
            [$php, $r, $py] = [$means('CmdStanPHP'), $means('cmdstanr'), $means('CmdStanPy')];
            $panel['table'] = view('components.landing.verified-table', [
                'panel' => $panel,
                'key' => $key,
                'rows' => array_map(fn (string $v) => [$v, $php[$v], ['R' => $r[$v], 'Python' => $py[$v]], false], array_keys($php)),
                'value' => $value,
                'diff' => $diff,
            ])->render();
            continue;
        }
        $panel['link'] = $panel['anchor'] === null ? null : $record.'#'.$panel['anchor'];
        $panel['code'] = isset($panel['also'])
            ? [$panel['primary'] => \App\Content\Example::code($panel['example'])['pipe'], ...array_map(fn (string $e) => \App\Content\Example::code($e)['pipe'], $panel['also'])]
            : \App\Content\Example::code($panel['example'])['pipe'];
        try {
            $panel['table'] = \App\Content\Example::output(
                $panel['example'],
                'verified-table',
                fn (mixed $result) => view('components.landing.verified-table', [
                    'panel' => $panel,
                    'key' => $key,
                    'rows' => ($panel['rows'])($result, array_map(
                        // Another PHPstats library's values come from running
                        // its own example, turned into columns like the references.
                        function (string $library) use ($panel, $references, $result) {
                            if (! isset($panel['also'][$library])) {
                                return $references[$library][$panel['reference']] ?? null;
                            }
                            // A library this server can't load shows a dash.
                            try {
                                $rows = \App\Content\Example::run($panel['also'][$library]);
                            } catch (\Throwable) {
                                return null;
                            }

                            return array_map(fn (string $field) => array_column($rows, $field), array_combine(array_keys($result[0]), array_keys($result[0])));
                        },
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
            All our libraries are verified against popular alternatives in R and Python.
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
                     class="mt-8">
                    <div class="grid gap-8 lg:grid-cols-2 lg:items-start lg:gap-12">
                    @if (is_array($panel['code']))
                        @php $firstCode = array_key_first($panel['code']); @endphp
                        <div x-data="{ lib: @js($firstCode) }" class="code-ink min-w-0 overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)">
                            <div role="tablist" aria-label="{{ $panel['label'] }} code" class="flex gap-1 border-b border-white/5 px-3 py-2 text-xs">
                                @foreach ($panel['code'] as $lib => $source)
                                    <button type="button" role="tab" id="check-code-tab-{{ $key }}-{{ $lib }}" aria-controls="check-code-{{ $key }}-{{ $lib }}"
                                            x-on:click="lib = '{{ $lib }}'" x-bind:aria-selected="(lib === '{{ $lib }}').toString()" aria-selected="{{ $lib === $firstCode ? 'true' : 'false' }}"
                                            class="rounded-md px-2.5 py-1 font-mono text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white">{{ $lib }}</button>
                                @endforeach
                            </div>
                            @foreach ($panel['code'] as $lib => $source)
                                <div role="tabpanel" id="check-code-{{ $key }}-{{ $lib }}" aria-labelledby="check-code-tab-{{ $key }}-{{ $lib }}" x-show="lib === '{{ $lib }}'" @unless ($lib === $firstCode) x-cloak @endunless>
                                    <pre class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($source, 'php') !!}</code></pre>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-code :code="$panel['code']" class="min-w-0" />
                    @endif

                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $panel['title'] }}</h3>
                        <p class="mb-3 mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">{!! $panel['model'] !!}</p>
                        {!! $panel['table'] !!}
                        <p class="mt-3 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                            {!! \App\Content\Libraries::mono($panel['note']) !!}
                            @if ($panel['dataset'])
                                Data: <code>{{ $panel['dataset'] }}</code> (<a href="https://github.com/phpstats/datasets#{{ $panel['dataset'] }}" class="underline decoration-zinc-300 underline-offset-2 hover:text-zinc-700 dark:decoration-zinc-600 dark:hover:text-zinc-200">source</a>).
                            @endif
                        </p>
                        @if ($panel['link'])
                        <a href="{{ $panel['link'] }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                            {{ $panel['record'] }} verification record <x-heroicon-m-arrow-right class="size-4" />
                        </a>
                        @endif
                    </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
