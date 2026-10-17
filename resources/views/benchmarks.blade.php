<x-layout title="Benchmarks">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Benchmarks</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            These results come from the benchmark reports in the phit documentation.
            Each report fits the same simulated data with phit, R, and Python (statsmodels, or a SciPy reference for mixed logistic regression).
            It records the fit times and checks that every implementation's estimates agree with R's.
            The timings measure model fitting only, after the data is loaded.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            Each report shows the date it was recorded. phit changes often, so an older report may not reflect the current code.
        </p>
    </article>

    <section class="mt-10">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">phit, R, and Python</h2>
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
                Polyfill {{ $polyfill['polyfill_commit'] }} against phit {{ substr($polyfill['extension_reference_commit'], 0, 7) }}.
                "Agrees" means the polyfill returns the same result keys as the extension and its coefficients differ from the extension's by at most {{ sprintf('%.0e', max(array_map(fn ($c) => $c['agreement']['max_coefficient_difference_in_standard_errors'], $polyfill['cases']))) }} standard errors.
                <a href="{{ $polyfill['source'] }}" class="underline">Results file</a>.
            </p>
        </section>
    @endif

    @php($backends = \App\Data\Benchmarks::exists() ? (\App\Data\Benchmarks::load()['backends'] ?? null) : null)
    @if ($backends)
        @php($grid = \App\Data\Benchmarks::backendGrid($backends))
        @php($method = $backends['method'])
        @php($largest = array_key_last($grid))
        @php($allMatch = collect($backends['results'])->every(fn ($r) => $r['matches_frame'] && $r['error'] === null))
        <section class="mt-12">
            <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">phplyr backends</h2>
            <p class="mt-3 max-w-3xl text-zinc-700 dark:text-zinc-300">
                phplyr runs the same pipeline on any phibble table, so the backend determines the speed.
                These timings run three pipelines on an in-memory phibble frame, a phpolars PolarsTable, and a SQLite table.
                @if ($allMatch)
                    Every backend returned the same rows as the frame.
                @endif
            </p>

            <x-phiz :plot="\App\Data\Benchmarks::backendChart($backends, $largest)" class="mt-6 w-full" />

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-sm tabular-nums">
                    <thead class="text-left text-zinc-500">
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="py-2 pr-4 font-medium">Rows</th>
                            <th class="py-2 pr-4 font-medium">Pipeline</th>
                            @foreach (\App\Data\Benchmarks::BACKENDS as $label)
                                <th class="py-2 pr-4 text-right font-medium">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grid as $rows => $pipelines)
                            @foreach ($backends['pipelines'] as $pipeline)
                                <tr class="border-b border-zinc-100 dark:border-zinc-900">
                                    <td class="py-2 pr-4">{{ $loop->first ? number_format($rows) : '' }}</td>
                                    <td class="py-2 pr-4"><span class="font-medium">{{ $pipeline['id'] }}.</span> {{ ucfirst($pipeline['title']) }}</td>
                                    @foreach (array_keys(\App\Data\Benchmarks::BACKENDS) as $backend)
                                        @php($r = $pipelines[$pipeline['id']][$backend] ?? null)
                                        <td class="py-2 pr-4 text-right">
                                            @if (! $r || $r['error'] !== null || $r['seconds'] === null)
                                                Failed
                                            @else
                                                {{ $r['seconds'] < 0.1 ? number_format($r['seconds'] * 1000).' ms' : number_format($r['seconds'], 2).' s' }}{{ $r['fallbacks'] ? '*' : '' }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 max-w-3xl space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                <p>
                    PolarsTable is fastest when a pipeline reduces the data before collecting it, as pipeline A does.
                    When every row comes back to PHP, as in pipeline B, converting the values takes much of its time.
                    * A PHP closure makes PolarsTable fall back to an in-memory frame for that step, which is why pipeline C gains little from Polars. On a SQLite table, a closure loads the rows into PHP first.
                </p>
                <p>
                    {{ ucfirst($method['statistic']) }} of {{ $method['repeats'] }} runs, timing the {{ $method['timing'] }}.
                    Recorded on an {{ $method['machine']['cpu'] }} with PHP {{ $method['php'] }}, SQLite {{ $method['versions']['sqlite'] }}, and Polars on {{ $method['polars_threads'] }} threads; the frame and SQLite run single-threaded.
                    phplyr {{ $method['versions']['phplyr'] }}, phibble {{ $method['versions']['phibble'] }}, phpolars {{ $method['versions']['phpolars']['sha'] }}.
                    The data has no date or datetime columns, which would add conversion time to PolarsTable.
                    <a href="{{ $backends['data'] }}" class="underline">Results file</a>. <a href="{{ $backends['source'] }}" class="underline">phplyr's performance page</a> discusses the results.
                </p>
                <details>
                    <summary class="cursor-pointer">Pipeline code</summary>
                    @foreach ($backends['pipelines'] as $pipeline)
                        <p class="mt-3 font-medium">{{ $pipeline['id'] }}. {{ ucfirst($pipeline['title']) }}</p>
                        <pre class="mt-1 overflow-x-auto rounded-lg bg-zinc-100 p-3 text-xs dark:bg-zinc-900"><code>{{ $pipeline['code'] }}</code></pre>
                    @endforeach
                </details>
            </div>
        </section>
    @endif
</x-layout>
