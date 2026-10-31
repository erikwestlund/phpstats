{{-- The phpstats.org bar, dark to sit on the landing hero. --}}
@php
    // The root host's own pages. A Markdown page is matched on its {page}
    // parameter, so About is not marked current on another Markdown page.
    $pages = [
        ['label' => 'Installation', 'url' => route('page', 'installation'), 'current' => request()->route('page') === 'installation'],
        ['label' => 'Examples', 'url' => route('vignettes'), 'current' => request()->routeIs('vignettes')],
        ['label' => 'About PHPstats', 'url' => route('page', 'about'), 'current' => request()->route('page') === 'about'],
    ];
@endphp
<header class="sticky top-0 z-40 border-b border-white/5 bg-zinc-950/95 backdrop-blur-lg">
    <nav class="mx-auto flex h-16 max-w-7xl items-center gap-2 px-4 sm:gap-4 sm:px-6 lg:px-8">
        <x-site-menus :dark="true" />
        <div class="ml-auto flex items-center gap-1 text-sm text-zinc-300">
            @foreach ($pages as $page)
                <a href="{{ $page['url'] }}" @if ($page['current']) aria-current="page" @endif
                   class="hidden whitespace-nowrap rounded-lg px-3 py-1.5 hover:bg-white/10 hover:text-white aria-[current]:text-white sm:block">{{ $page['label'] }}</a>
            @endforeach
            <x-theme-toggle :dark="true" />
            <a href="https://github.com/phpstats" class="rounded-lg p-2 hover:bg-white/10 hover:text-white" aria-label="PHPstats on GitHub"><x-github-icon /></a>
        </div>
    </nav>

    {{-- The same pages on small screens, where they do not fit in the bar. --}}
    <nav class="flex gap-1 overflow-x-auto border-t border-white/5 px-3 py-1.5 text-sm sm:hidden" aria-label="PHPstats pages">
        @foreach ($pages as $page)
            <a href="{{ $page['url'] }}" @if ($page['current']) aria-current="page" @endif
               class="shrink-0 rounded-md px-2.5 py-1 text-zinc-400 aria-[current]:bg-white/10 aria-[current]:font-medium aria-[current]:text-white">{{ $page['label'] }}</a>
        @endforeach
    </nav>
</header>
