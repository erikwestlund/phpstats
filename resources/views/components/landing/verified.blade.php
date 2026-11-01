{{--
    The landing page's verification section: one comparison at a time, chosen
    with tabs. Each shows phit code on the left and, on the right, the values
    that code gives next to each reference library's values, rounded the same
    way: model results to two decimal places, distribution values to three
    significant figures. Where a library's rounded value differs from phit's,
    its signed difference follows it, green with a plus when the library's
    value is larger and red with a minus when it is smaller. phit's values come from running the code shown
    (resources/examples/verified/*.php); the references are R's and Python's
    results from phit's verification records, copied into
    database/data/verification/references.json. Model differences are
    absolute; distribution differences are relative to phit's value. Each
    panel links its full verification record.
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

    $panels = [
        'mixed' => [
            'label' => 'Linear mixed model',
            'example' => 'verified/mixed',
            'files' => [database_path('data/schools.csv')],
            'libraries' => ['R' => 'lme4 (R)', 'statsmodels' => 'statsmodels (Python)'],
            'relative' => false,
            'note' => 'Maximum likelihood on the schools data. R: lme4 <code>lmer(REML = FALSE)</code>. Python: <code>MixedLM</code>.',
            'anchor' => 'lmm',
            'rows' => function (array $fit, array $ref) {
                $rows = [];
                foreach ($fit['columns'] as $i => $term) {
                    $rows[] = [$term, $fit['coefficients'][$i], array_map(fn ($r) => $r['coefficients'][$i], $ref)];
                }
                $rows[] = ['School variance', $fit['random'][0]['covariance'][0][0], array_map(fn ($r) => $r['random_intercept_variance'], $ref)];
                $rows[] = ['Residual variance', $fit['residual_variance'], array_map(fn ($r) => $r['residual_variance'], $ref)];
                $rows[] = ['Log-likelihood', $fit['log_likelihood'], array_map(fn ($r) => $r['log_likelihood'], $ref)];

                return $rows;
            },
        ],
        'logistic' => [
            'label' => 'Logistic regression',
            'example' => 'verified/logistic',
            'files' => [database_path('data/learning.csv')],
            'libraries' => ['R' => 'glm (R)', 'statsmodels' => 'statsmodels (Python)'],
            'relative' => false,
            'note' => 'Maximum likelihood on 80 simulated students. R: <code>glm(family = binomial)</code>. Python: <code>Logit</code>.',
            'anchor' => 'logistic',
            'rows' => function (array $fit, array $ref) {
                $rows = [];
                foreach ($fit['columns'] as $i => $term) {
                    $rows[] = [$term, $fit['coefficients'][$i], array_map(fn ($r) => $r['coefficients'][$i], $ref)];
                }
                $rows[] = ['Log-likelihood', $fit['log_likelihood'], array_map(fn ($r) => $r['log_likelihood'], $ref)];

                return $rows;
            },
        ],
        'distributions' => [
            'label' => 'Probability distributions',
            'example' => 'verified/distributions',
            'files' => [],
            'libraries' => ['R' => 'R', 'SciPy' => 'SciPy (Python)'],
            'relative' => true,
            'note' => 'R: <code>pnorm</code>, <code>qnorm</code>, <code>pgamma</code>, and <code>qgamma</code>. Python: SciPy\'s <code>norm</code> and <code>gamma</code>. Differences are relative.',
            'anchor' => 'distributions',
            'rows' => function (array $values, array $ref) {
                $labels = ['Normal CDF at −8', 'Normal quantile at 0.975', 'Gamma CDF at 12', 'Gamma quantile at 0.999'];
                $rows = [];
                foreach ($ref['points'] as $i => $point) {
                    $rows[] = [$labels[$i], $values[$i], $point['values']];
                }

                return $rows;
            },
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
                    'rows' => ($panel['rows'])($result, $key === 'distributions'
                        ? $references['distributions']
                        : array_intersect_key($references[$key], $panel['libraries'])),
                    'value' => $value,
                    'diff' => $diff,
                ])->render(),
                files: [\App\Data\VerificationReferences::path(), ...$panel['files']],
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
                     class="mt-8 grid gap-8 lg:grid-cols-[5fr_7fr] lg:items-start lg:gap-16">
                    <x-code :code="$panel['code']" class="min-w-0" />

                    <div class="min-w-0">
                        {!! $panel['table'] !!}
                        <p class="mt-3 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{!! $panel['note'] !!} Values are rounded, and a difference from <span class="font-mono">phit</span> appears only where the rounded values differ.</p>
                        <a href="{{ $record }}#{{ $panel['anchor'] }}" class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                            {{ $panel['label'] }} verification record <x-heroicon-m-arrow-right class="size-4" />
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
