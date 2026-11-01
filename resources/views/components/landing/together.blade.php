{{--
    The landing page's connected example: phpolars reads a CSV file, phit
    fits a mixed model to the columns, and phiz charts each school's fitted
    line. resources/examples/together/schools.php runs as one script; its
    "// ---" lines split the code into the three steps shown, and the chart is
    what it prints. The link opens the complete example in phit's docs, or
    phit's mixed model example until that page syncs.
--}}
@php
    $steps = \App\Content\Example::steps('together/schools');
    $titles = [
        ['Read the table with', 'phpolars'],
        ['Fit a mixed model with', 'phit'],
        ['Chart the fitted lines with', 'phiz'],
    ];
    try {
        $chart = \App\Content\Example::output(
            'together/schools',
            'package-output|together',
            fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => 'Test scores and fitted school lines'])->render(),
            files: [database_path('data/schools.csv')],
        );
    } catch (\Throwable $e) {
        report($e);
        $chart = null;
    }
    $phit = \App\Content\Package::find('phit');
    $path = collect(['examples/polars-mixed-model', 'examples/mixed-model'])->first(fn ($p) => in_array($p, $phit->docsPaths(), true));
@endphp
<section id="together" aria-labelledby="together-heading" class="scroll-mt-16 border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="together-heading" class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Designed to work together</h2>
        <p class="mx-auto mt-4 max-w-2xl text-center text-lg leading-snug text-zinc-600 dark:text-zinc-400">
            Read data with Polars, fit a Gaussian mixed model, and visualize the results with phiz—all from PHP.
        </p>

        <p class="mt-10 text-sm text-zinc-500 sm:mt-12 dark:text-zinc-400">The example reads one CSV file of test scores: 250 students in 12 schools.</p>
        <div class="mt-4 grid gap-8 lg:grid-cols-2 lg:items-start">
            <ol class="min-w-0 space-y-6">
                @foreach ($steps as $i => $code)
                    <li>
                        <h3 class="mb-2 text-base font-semibold text-zinc-900 dark:text-white">
                            <span class="mr-1.5 text-zinc-500 dark:text-zinc-400">{{ $i + 1 }}.</span>
                            {{ $titles[$i][0] }} <span class="font-mono">{{ $titles[$i][1] }}</span>
                        </h3>
                        <x-code :code="$code" />
                    </li>
                @endforeach
            </ol>

            <div class="min-w-0 overflow-hidden rounded-xl bg-zinc-50/70 ring-1 ring-zinc-200 lg:sticky lg:top-24 dark:bg-white/[0.02] dark:ring-white/10">
                @if ($chart === null)
                    <p class="p-5 text-sm text-zinc-500 sm:p-7">This server can't run the example, so its chart isn't shown.</p>
                @else
                    {!! $chart !!}
                @endif
                <p class="border-t border-zinc-200 px-5 py-4 text-[0.9375rem] leading-relaxed text-zinc-700 sm:px-7 dark:border-white/10 dark:text-zinc-300">
                    <code class="font-mono text-[0.875em]">toColumns()</code> copies the three columns out of Polars once, and phit and phiz both read those arrays. Each line is a school's intercept from the model, with the slope the schools share.
                </p>
                @if ($path)
                    <div class="border-t border-zinc-200 px-5 py-3 sm:px-7 dark:border-white/10">
                        <a href="{{ $phit->url('docs', ['path' => $path]) }}" aria-label="View the complete example in phit's docs" class="inline-flex items-center gap-1.5 text-[0.9375rem] font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                            View the complete example
                            <x-heroicon-m-arrow-right class="size-4" />
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
