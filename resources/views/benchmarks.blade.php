<x-layout title="Benchmarks">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Benchmarks</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            These results come from the benchmark reports in the php-stats documentation.
            Each report fits the same simulated data with php-stats, R, and Python (statsmodels, or a SciPy reference for mixed logistic regression).
            It records the fit times and checks that every implementation's estimates agree with R's.
            The timings measure model fitting only, after the data is loaded.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            Each report shows the date it was recorded. php-stats changes often, so an older report may not reflect the current code.
        </p>
    </article>

    <section class="mt-10">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">php-stats, R, and Python</h2>
        <div class="mt-4">
            <livewire:benchmark-results />
        </div>
    </section>

    @php($polyfill = \App\Data\Benchmarks::exists() ? (\App\Data\Benchmarks::load()['polyfill'] ?? null) : null)
    @if ($polyfill)
        <section class="mt-12">
            <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Extension, polyfill, and plain PHP</h2>
            <p class="mt-3 max-w-3xl text-zinc-700 dark:text-zinc-300">
                php-stats-polyfill implements the same functions in PHP for hosts that cannot install the extension.
                These timings compare it with the extension on the same data: {{ lcfirst($polyfill['design']) }}.
                The plain PHP figure solves the normal equations directly. It computes coefficients only, with no standard errors or rank checks, and it squares the condition number of the design, so it is faster but less accurate and is not a substitute for either library.
            </p>

            <x-phiz :plot="\App\Data\Benchmarks::polyfillChart($polyfill)" class="mt-6 w-full" />

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-sm tabular-nums">
                    <thead class="text-left text-zinc-500">
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="py-2 pr-4 font-medium">Case</th>
                            @foreach (\App\Data\Benchmarks::POLYFILL_IMPLEMENTATIONS as $label)
                                <th class="py-2 pr-4 text-right font-medium">{{ $label }}</th>
                            @endforeach
                            <th class="py-2 text-right font-medium">Polyfill agrees</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($polyfill['cases'] as $case)
                            <tr class="border-b border-zinc-100 dark:border-zinc-900">
                                <td class="py-2 pr-4">{{ \App\Data\Benchmarks::polyfillCaseLabel($case) }}</td>
                                @foreach (array_keys(\App\Data\Benchmarks::POLYFILL_IMPLEMENTATIONS) as $name)
                                    <td class="py-2 pr-4 text-right">{{ isset($case['implementations'][$name]) ? number_format(1000 * $case['implementations'][$name]['median_seconds'], 1).' ms' : '' }}</td>
                                @endforeach
                                <td class="py-2 text-right">{{ $case['agreement']['agrees'] ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
                Median of {{ $polyfill['repeat'] }} runs after {{ $polyfill['warmup_calls'] }} warm-up call, recorded {{ \Illuminate\Support\Carbon::parse($polyfill['recorded_at'])->format('F j, Y') }} on an {{ $polyfill['cpu'] }} with PHP {{ $polyfill['php_version'] }}{{ $polyfill['opcache']['jit'] ? ' and the '.$polyfill['opcache']['jit_mode'].' JIT' : ' without JIT' }}.
                Polyfill {{ $polyfill['polyfill_commit'] }} against php-stats {{ substr($polyfill['extension_reference_commit'], 0, 7) }}.
                "Agrees" means the polyfill returns the same result keys as the extension and its coefficients differ from the extension's by at most {{ sprintf('%.0e', max(array_map(fn ($c) => $c['agreement']['max_coefficient_difference_in_standard_errors'], $polyfill['cases']))) }} standard errors.
                <a href="{{ $polyfill['source'] }}" class="underline">Results file</a>.
            </p>
        </section>
    @endif

    @php($backends = \App\Data\Benchmarks::backends())
    <section class="mt-12">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">phplyr backends</h2>
        <p class="mt-3 max-w-3xl text-zinc-700 dark:text-zinc-300">
            phplyr runs the same pipeline on any phibble table, so the backend determines the speed.
            These timings run three pipelines on {{ number_format($backends['rows']) }} rows in an in-memory phibble frame, a phpolars PolarsTable, and a SQLite table.
            Each backend returned the same rows.
        </p>

        <x-phiz :plot="\App\Data\Benchmarks::backendChart($backends)" class="mt-6 w-full" />

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Pipeline</th>
                        @foreach ($backends['backends'] as $label)
                            <th class="py-2 pr-4 text-right font-medium">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($backends['pipelines'] as $pipeline)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4"><span class="font-medium">{{ $pipeline['key'] }}.</span> {{ $pipeline['description'] }}</td>
                            @foreach (array_keys($backends['backends']) as $backend)
                                <td class="py-2 pr-4 text-right">{{ number_format($pipeline['seconds'][$backend], 2) }} s</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
            {{ $backends['method'] }} Recorded on an {{ $backends['machine'] }}.
            PolarsTable is fastest when a pipeline reduces the data before collecting it, as pipeline A does. When every row comes back to PHP, as in pipeline B, converting values takes most of its time.
            A closure makes PolarsTable and SQLite load the rows into PHP, which is why pipeline C is slower than A on both.
            Source: <a href="{{ $backends['source'] }}" class="underline">phplyr's performance page</a>, which reports phpolars' run. <a href="{{ $backends['phpolars_source'] }}" class="underline">phpolars' performance page</a> has more detail.
        </p>
    </section>
</x-layout>
