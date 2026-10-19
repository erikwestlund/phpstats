@php
    $layer = $package->layer();
    $nav = $package->docsNav();
    $index = array_search($path, array_column($nav, 'path'), true);
    $previous = $index !== false && $index > 0 ? $nav[$index - 1] : null;
    $next = $index !== false && $index < count($nav) - 1 ? $nav[$index + 1] : null;
    $docsUrl = fn (string $page) => $package->url('docs', ['path' => $page === 'index' ? null : $page]);
@endphp
<x-layout :title="$page['title']" :bare="true">
    @if ($page['math'])
        @push('head')
            <script>
                window.MathJax = { tex: { inlineMath: [['\\(', '\\)']], displayMath: [['\\[', '\\]']] }, chtml: { displayOverflow: 'scroll' } };
            </script>
            <script src="https://cdn.jsdelivr.net/npm/mathjax@4.0.0/tex-chtml.js" defer></script>
        @endpush
    @endif

    <div class="layer-{{ $layer['color'] }} mx-auto grid max-w-[88rem] gap-10 px-4 sm:px-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:px-8 xl:grid-cols-[16rem_minmax(0,1fr)_14rem]">
        {{-- Pages --}}
        <nav aria-label="{{ $package->name() }} documentation" x-data="{ open: false }"
             class="text-sm lg:sticky lg:top-14 lg:h-[calc(100vh-3.5rem)] lg:overflow-y-auto lg:border-r lg:border-zinc-100 lg:py-8 lg:pr-6 dark:lg:border-white/5">
            <button type="button" x-on:click="open = ! open" class="mt-4 flex w-full items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 font-medium ring-1 ring-zinc-200 lg:hidden dark:bg-white/5 dark:ring-white/10">
                {{ $page['title'] }} <x-heroicon-m-chevron-up-down class="size-4 text-zinc-400" />
            </button>
            <div x-bind:class="open ? 'block' : 'hidden lg:block'" class="hidden pt-4 lg:block lg:pt-0">
                @php $groups = $package->docsGroups(); @endphp
                @foreach ($groups as $group => $items)
                    {{-- One group is the whole nav, so its heading says nothing, as with a menu of one layer. --}}
                    @if (count($groups) > 1)
                        <p @class(['mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white', 'mt-7' => ! $loop->first])>{{ $group }}</p>
                    @endif
                    <ul class="space-y-0.5 border-l border-zinc-200 dark:border-white/10">
                        @foreach ($items as $item)
                            <li>
                                <a href="{{ $docsUrl($item['path']) }}" @if ($item['path'] === $path) aria-current="page" @endif
                                   class="-ml-px block border-l border-transparent py-1 pl-4 text-zinc-600 transition hover:border-zinc-400 hover:text-zinc-900 aria-[current]:border-(--l500) aria-[current]:font-medium aria-[current]:text-(--l700) dark:text-zinc-400 dark:hover:text-white dark:aria-[current]:text-(--l300)">{{ $item['title'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach

                @if ($relations = $package->relations())
                    <p class="mb-2 mt-8 text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">Related packages</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (collect($relations)->unique(fn ($r) => $r['package']->key) as $relation)
                            <x-package-chip :package="$relation['package']" :title="$relation['text']" />
                        @endforeach
                    </div>
                @endif
            </div>
        </nav>

        {{-- Page --}}
        <article class="min-w-0 py-8 lg:py-10">
            <p class="text-sm font-semibold text-(--l600) dark:text-(--l300)">{{ $package->docsGroupOf($path) ?? 'Docs' }}</p>
            <h1 class="mt-2 text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $page['title'] }}</h1>
            <div class="docs-content prose prose-zinc prose-php mt-8 max-w-none dark:prose-invert prose-headings:scroll-mt-24 prose-headings:tracking-tight prose-h2:mt-12 prose-a:font-medium">
                {!! $page['html'] !!}
            </div>

            @if ($previous || $next)
                <nav class="mt-16 grid gap-4 border-t border-zinc-200 pt-8 sm:grid-cols-2 dark:border-white/10" aria-label="Previous and next pages">
                    @if ($previous)
                        <a href="{{ $docsUrl($previous['path']) }}" class="group rounded-xl p-4 ring-1 ring-zinc-200 transition hover:ring-(--l300) dark:ring-white/10">
                            <span class="text-xs text-zinc-500">Previous</span>
                            <span class="mt-1 flex items-center gap-1 font-medium text-zinc-900 group-hover:text-(--l700) dark:text-white dark:group-hover:text-(--l300)"><x-heroicon-m-arrow-left class="size-4" /> {{ $previous['title'] }}</span>
                        </a>
                    @else
                        <span></span>
                    @endif
                    @if ($next)
                        <a href="{{ $docsUrl($next['path']) }}" class="group rounded-xl p-4 text-right ring-1 ring-zinc-200 transition hover:ring-(--l300) dark:ring-white/10">
                            <span class="text-xs text-zinc-500">Next</span>
                            <span class="mt-1 flex items-center justify-end gap-1 font-medium text-zinc-900 group-hover:text-(--l700) dark:text-white dark:group-hover:text-(--l300)">{{ $next['title'] }} <x-heroicon-m-arrow-right class="size-4" /></span>
                        </a>
                    @endif
                </nav>
            @endif

            @if ($sync = $package->sync())
                <p class="mt-8 text-xs text-zinc-500">
                    @if ($sync['local'] ?? false)
                        {{-- `site:sync --from` reads a working tree, which has no commit to link. --}}
                        From a local checkout of <span class="font-mono">{{ $sync['repo'] }}</span>, not a pinned commit.
                    @else
                        From <a href="https://github.com/{{ $sync['repo'] }}/tree/{{ $sync['commit'] }}" class="font-mono underline decoration-zinc-300 underline-offset-2">{{ $sync['repo'].'@'.substr($sync['commit'], 0, 7) }}</a>.
                    @endif
                </p>
            @endif
        </article>

        {{-- On this page --}}
        @if (count($page['headings']) > 1)
            <nav aria-label="On this page" x-data="tocSpy" class="hidden text-sm xl:sticky xl:top-14 xl:block xl:max-h-[calc(100vh-3.5rem)] xl:self-start xl:overflow-y-auto xl:py-10">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">On this page</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($page['headings'] as $heading)
                        <li @class(['pl-3' => $heading['level'] === 3])>
                            <a href="#{{ $heading['id'] }}" data-toc="{{ $heading['id'] }}"
                               x-bind:aria-current="active === '{{ $heading['id'] }}' ? 'location' : null"
                               class="block text-zinc-500 transition hover:text-zinc-900 aria-[current]:font-medium aria-[current]:text-(--l700) dark:text-zinc-400 dark:hover:text-white dark:aria-[current]:text-(--l300)">{{ $heading['text'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </div>
</x-layout>
