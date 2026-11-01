@php
    $layer = $package->layer();
    $nav = $package->docsNav();
    $index = array_search($path, array_column($nav, 'path'), true);
    $previous = $index !== false && $index > 0 ? $nav[$index - 1] : null;
    $next = $index !== false && $index < count($nav) - 1 ? $nav[$index + 1] : null;
    $docsUrl = fn (string $page) => $package->url('docs', ['path' => $page === 'index' ? null : $page]);
    // The page's sections for "On this page": each h2 with the h3s under it.
    // An h3 before any h2 stands on its own.
    $toc = [];
    foreach ($page['headings'] as $heading) {
        if ($heading['level'] === 3 && $toc !== []) {
            $toc[array_key_last($toc)]['children'][] = $heading;
        } elseif (in_array($heading['level'], [2, 3], true)) {
            $toc[] = [...$heading, 'children' => []];
        }
    }
    $toc = count($page['headings']) > 1 ? $toc : [];
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
                @php
                    $groups = $package->docsGroups();
                    $collapsed = config('phpstats.collapsed_docs_groups', []);
                @endphp
                @foreach ($groups as $group => $items)
                    @php
                        $collapsible = count($groups) > 1 && in_array($group, $collapsed, true);
                        $current = in_array($path, array_column($items, 'path'), true);
                    @endphp
                    @if ($collapsible)
                        <details @if ($current) open @endif @class(['group/docs', 'mt-7' => ! $loop->first])>
                            <summary class="mb-2 flex cursor-pointer list-none items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white [&::-webkit-details-marker]:hidden">
                                {{ $group }}
                                <span class="flex items-center gap-1 font-normal normal-case tracking-normal text-zinc-500 dark:text-zinc-400">{{ count($items) }} <x-heroicon-m-chevron-right class="size-4 transition group-open/docs:rotate-90" /></span>
                            </summary>
                            @include('package.partials.docs-links', ['items' => $items])
                        </details>
                    @else
                        {{-- One group is the whole nav, so its heading says nothing, as with a menu of one layer. --}}
                        @if (count($groups) > 1)
                            <p @class(['mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white', 'mt-7' => ! $loop->first])>{{ $group }}</p>
                        @endif
                        @include('package.partials.docs-links', ['items' => $items])
                    @endif
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
            <h1 class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $page['title'] }}</h1>
            @if ($toc !== [])
                {{-- Below xl the right-hand list is hidden, so the page's sections open from here. --}}
                <details class="group/toc mt-6 rounded-lg bg-zinc-50 text-sm ring-1 ring-zinc-200 xl:hidden dark:bg-white/5 dark:ring-white/10">
                    <summary class="flex cursor-pointer list-none items-center justify-between px-3 py-2 font-medium [&::-webkit-details-marker]:hidden">
                        On this page <x-heroicon-m-chevron-down class="size-4 text-zinc-400 transition group-open/toc:rotate-180" />
                    </summary>
                    <div class="max-h-[60vh] overflow-y-auto overscroll-contain px-3 pb-3" x-data x-on:click="$event.target.closest('a') && $el.closest('details').removeAttribute('open')">
                        @include('package.partials.docs-toc', ['toc' => $toc, 'spy' => false])
                    </div>
                </details>
            @endif
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

        @if ($moved = $package->movedFragments($path))
            <script>
                // Sections of this page that moved to the Benchmarks and Verification pages.
                (function () {
                    var moved = @js($moved);
                    var id = decodeURIComponent(location.hash.slice(1));
                    if (Object.prototype.hasOwnProperty.call(moved, id) && !document.getElementById(id)) location.replace(moved[id]);
                })();
            </script>
        @endif

        {{-- On this page: the page's h2 sections, each with its h3 entries, in
             a list that scrolls on its own when it is taller than the window,
             keeping the current entry in view. --}}
        @if ($toc !== [])
            <nav aria-label="On this page" x-data="tocSpy" class="hidden text-sm xl:sticky xl:top-14 xl:block xl:max-h-[calc(100vh-3.5rem)] xl:self-start xl:overflow-y-auto xl:overscroll-contain xl:py-10">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white">On this page</p>
                <div class="mt-3">
                    @include('package.partials.docs-toc', ['toc' => $toc, 'spy' => true])
                </div>
            </nav>
        @endif
    </div>
</x-layout>
