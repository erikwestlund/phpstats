{{--
    The landing host's index of every vignette, from App\Http\Controllers\
    VignetteIndexController, grouped by the package whose host runs each one.
    There is no current package here, so no --l* layer colors are defined and
    the page uses the PHP purples.
--}}
<x-layout
    title="Vignettes"
    description="Worked examples that run on this server: regression and mixed models, pipelines, charts, Polars, and Bayesian models in Stan."
    :wide="true"
>
    <x-page-header title="Vignettes">
        Every vignette on the site. Each one runs on the server when its page loads, and again when an
        input changes.
    </x-page-header>

    <p class="mt-6 text-sm text-zinc-500 dark:text-zinc-400">{{ $total }} vignettes across {{ count($groups) }} packages.</p>

    <div class="mt-12 space-y-14">
        @foreach ($groups as $group)
            @php $package = $group['package']; @endphp
            <section>
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-zinc-200 pb-3 dark:border-white/10">
                    <h2>
                        <a href="{{ $package->url('vignettes') }}" class="font-mono text-xl font-semibold text-zinc-950 hover:text-php-700 dark:text-white dark:hover:text-php-300">{{ $package->name() }}</a>
                    </h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $package->tagline() }}</p>
                </div>

                <ul class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($group['vignettes'] as $vignette)
                        <li class="group relative flex flex-col rounded-2xl bg-white p-6 ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-php-500/10 hover:ring-php-300 dark:bg-white/[0.03] dark:ring-white/10 dark:hover:ring-php-400/40">
                            <span class="flex items-center gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                                Live
                            </span>
                            <a href="{{ $package->url('vignette', ['slug' => $vignette['slug']]) }}" class="mt-3 text-lg font-semibold tracking-tight text-zinc-950 after:absolute after:inset-0 group-hover:text-php-700 dark:text-white dark:group-hover:text-php-300">{{ $vignette['title'] }}</a>
                            @if ($vignette['summary'] !== '')
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $vignette['summary'] }}</p>
                            @endif
                            <div class="relative z-10 mt-auto flex flex-wrap gap-1.5 pt-5">
                                @foreach ($vignette['uses'] as $use)
                                    <x-package-chip :package="$use === 'php_polars' ? 'phpolars' : $use" />
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</x-layout>
