{{--
    The landing page's connected example: phpolars reads a CSV file, phit
    fits a mixed model to the columns, and phiz charts each school's fitted
    line. resources/examples/together/schools.php runs as one script; its
    "// ---" lines split the code into the three steps shown, and the chart is
    what it prints. A caption under the code and chart describes the data and
    the steps, and the link after it opens the complete example in phit's docs, or
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
<section id="together" aria-labelledby="together-heading" class="scroll-mt-16 py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="together-heading" class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Designed to work together</h2>
        <p class="mx-auto mt-4 max-w-3xl text-center text-lg leading-snug text-zinc-600 dark:text-zinc-400">
            PHPstats is a family of interoperable PHP packages for data processing, statistical analysis, modeling, and visualization. Inspired by R's tidyverse, they share data structures and API idioms for a cohesive statistical computing experience in PHP.
        </p>

        <div class="mt-10 grid gap-8 sm:mt-12 lg:grid-cols-2 lg:items-start">
            <div class="min-w-0 space-y-6">
                @foreach ($steps as $i => $code)
                    <div>
                        <h3 class="mb-2 text-base font-semibold text-zinc-900 dark:text-white">
                            {{ $titles[$i][0] }} <span class="font-mono">{{ $titles[$i][1] }}</span>
                        </h3>
                        <x-code :code="$code" :hanging="true" />
                    </div>
                @endforeach
            </div>

            <div class="min-w-0 overflow-hidden rounded-xl bg-zinc-50/70 ring-1 ring-zinc-200 lg:sticky lg:top-24 dark:bg-white/[0.02] dark:ring-white/10">
                @if ($chart === null)
                    <p class="p-5 text-sm text-zinc-500 sm:p-7">This server can't run the example, so its chart isn't shown.</p>
                @else
                    {!! $chart !!}
                @endif
            </div>
        </div>
        <div class="mt-6 max-w-3xl space-y-3">
            <p class="text-sm leading-snug text-zinc-500 dark:text-zinc-400">Test scores for 250 students in 12 schools, loaded from a CSV with <span class="font-mono">phpolars</span>, modeled with a Gaussian mixed model in <span class="font-mono">phit</span>, and visualized with <span class="font-mono">phiz</span>.</p>
            @if ($path)
                <a href="{{ $phit->url('docs', ['path' => $path]) }}" aria-label="View the complete example in phit's docs" class="inline-flex items-center gap-1.5 text-[0.9375rem] font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                    View the complete example
                    <x-heroicon-m-arrow-right class="size-4" />
                </a>
            @endif
        </div>
    </div>
</section>
