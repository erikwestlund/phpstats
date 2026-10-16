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
            The reports were recorded before later optimizations to php-stats, so current releases may be faster than these numbers show.
        </p>
    </article>

    <section class="mt-10">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">php-stats, R, and Python</h2>
        <div class="mt-4">
            <livewire:benchmark-results />
        </div>
    </section>

    <section class="mt-12 max-w-3xl">
        <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">Not yet published</h2>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-zinc-700 dark:text-zinc-300">
            <li>php-stats against php-stats-polyfill and plain PHP. The polyfill repository has a benchmark script, <a href="https://github.com/erikwestlund/php-stats-polyfill/blob/main/benchmarks/run.php" class="underline">benchmarks/run.php</a>, but no published results yet.</li>
            <li>phplyr pipelines on phibble's in-memory frames, SQL tables, and phpolars. phplyr has a benchmark script, <a href="https://github.com/erikwestlund/phplyr/blob/main/bench/pipeline.php" class="underline">bench/pipeline.php</a>, but no published results yet.</li>
        </ul>
    </section>
</x-layout>
