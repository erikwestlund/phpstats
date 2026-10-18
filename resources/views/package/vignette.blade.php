<x-layout :title="$vignette['title']">
    @push('head')
        <script>
            window.MathJax = { tex: { inlineMath: [['\\(', '\\)']], displayMath: [['\\[', '\\]']] } };
        </script>
        <script src="https://cdn.jsdelivr.net/npm/mathjax@4.0.0/tex-chtml.js" defer></script>
    @endpush

    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $vignette['title'] }}</h1>
        @if ($vignette['summary'])
            <p class="mt-4 text-zinc-700 dark:text-zinc-300">{{ $vignette['summary'] }}</p>
        @endif
    </article>

    <div class="mt-8">
        <livewire:script-vignette :package="$package->key" :slug="$vignette['slug']" />
    </div>

    @if ($sync = $package->sync())
        <p class="mt-12 text-xs text-zinc-500">
            Script: <a href="https://github.com/{{ $sync['repo'] }}/blob/{{ $sync['commit'] }}/site/vignettes/{{ $vignette['slug'] }}.php" class="underline">site/vignettes/{{ $vignette['slug'] }}.php</a> in {{ $sync['repo'].'@'.substr($sync['commit'], 0, 7) }}.
        </p>
    @endif
</x-layout>
