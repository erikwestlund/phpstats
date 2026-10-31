<x-layout :title="$vignette['title']" :wide="true">
    @push('head')
        <script>
            window.MathJax = { tex: { inlineMath: [['\\(', '\\)']], displayMath: [['\\[', '\\]']] } };
        </script>
        <script src="https://cdn.jsdelivr.net/npm/mathjax@4.0.0/tex-chtml.js" defer></script>
    @endpush

    <a href="{{ $package->url('examples') }}" class="inline-flex items-center gap-1 text-sm font-medium text-(--l600) hover:text-(--l700) dark:text-(--l300)"><x-heroicon-m-arrow-left class="size-4" /> Examples</a>
    <x-page-header class="mt-4" :title="$vignette['title']">{{ $vignette['summary'] }}</x-page-header>
    <div class="mt-5 flex flex-wrap items-center gap-1.5">
        <span class="mr-1 text-xs text-zinc-500">Uses</span>
        @foreach ($vignette['uses'] as $use)
            <x-package-chip :package="$use === 'php_polars' ? 'phpolars' : $use" />
        @endforeach
    </div>

    <div class="mt-10">
        <livewire:script-vignette :package="$package->key" :slug="$vignette['slug']" />
    </div>

    @if ($sync = $package->sync())
        <p class="mt-12 text-xs text-zinc-500">
            @if ($sync['local'] ?? false)
                {{-- `site:sync --from` reads a working tree, which has no commit to link. --}}
                Script: <span class="font-mono">site/vignettes/{{ $vignette['slug'] }}.php</span> in a local checkout of <span class="font-mono">{{ $sync['repo'] }}</span>.
            @else
                Script: <a href="https://github.com/{{ $sync['repo'] }}/blob/{{ $sync['commit'] }}/site/vignettes/{{ $vignette['slug'] }}.php" class="font-mono underline decoration-zinc-300 underline-offset-2">site/vignettes/{{ $vignette['slug'] }}.php</a> in {{ $sync['repo'].'@'.substr($sync['commit'], 0, 7) }}.
            @endif
        </p>
    @endif
</x-layout>
