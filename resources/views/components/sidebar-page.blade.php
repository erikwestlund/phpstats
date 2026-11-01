{{--
    A page with a sticky section nav in the docs sidebar's style, as on
    Installation, Examples, and the Benchmarks and Verification sections:
    groups of { id, label, mono } links to the page's sections, highlighted as
    the reader scrolls, and an "On this page" menu on narrow screens. An item
    with href links another page instead; current marks the page being read,
    and its children, { id, label }, are that page's own sections. eyebrow
    goes above the title, wide widens the content column, and menu names the
    narrow-screen menu. The slot is the page's content under its title.
--}}
@props(['title', 'sidebar', 'eyebrow' => null, 'wide' => false, 'menu' => 'On this page'])
@php
    $link = 'block border-l border-transparent py-1 pl-4 -ml-px text-zinc-600 transition hover:border-zinc-400 hover:text-zinc-900 aria-[current]:border-php-500 aria-[current]:font-medium aria-[current]:text-php-700 dark:text-zinc-400 dark:hover:text-white dark:aria-[current]:text-php-300';
    $child = 'block py-0.5 pl-7 -ml-px text-[0.8125rem] text-zinc-500 transition hover:text-zinc-900 aria-[current]:text-php-700 dark:text-zinc-400 dark:hover:text-white dark:aria-[current]:text-php-300';
@endphp
<div x-data="tocSpy" @class(['mx-auto grid gap-10 lg:grid-cols-[13rem_minmax(0,1fr)]', 'max-w-6xl' => ! $wide, 'max-w-7xl' => $wide])>
    <nav aria-label="Sections" class="hidden text-sm lg:sticky lg:top-14 lg:block lg:max-h-[calc(100vh-3.5rem)] lg:self-start lg:overflow-y-auto lg:py-8">
        @foreach ($sidebar as $group)
            @if (! empty($group['title']))
                <p @class(['mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white', 'mt-7' => ! $loop->first])>{{ $group['title'] }}</p>
            @endif
            <ul @class(['space-y-0.5 border-l border-zinc-200 dark:border-white/10', 'mt-7' => ! $loop->first && empty($group['title'])])>
                @foreach ($group['items'] as $item)
                    <li>
                        @if (isset($item['href']))
                            <a href="{{ $item['href'] }}" @if ($item['current'] ?? false) aria-current="page" @endif
                               @class([$link, 'font-mono' => $item['mono'] ?? false])>{{ $item['label'] }}</a>
                            @if (($item['current'] ?? false) && ! empty($item['children']))
                                <ul class="mb-1 mt-0.5 space-y-0.5">
                                    @foreach ($item['children'] as $sub)
                                        <li><a href="#{{ $sub['id'] }}" data-toc="{{ $sub['id'] }}" x-bind:aria-current="active === '{{ $sub['id'] }}' ? 'location' : null" class="{{ $child }}">{{ $sub['label'] }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        @else
                            <a href="#{{ $item['id'] }}" data-toc="{{ $item['id'] }}"
                               x-bind:aria-current="active === '{{ $item['id'] }}' ? 'location' : null"
                               @class([$link, 'font-mono' => $item['mono'] ?? false])>{{ $item['label'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <article @class(['min-w-0 break-words py-6', 'max-w-3xl' => ! $wide])>
        @if ($eyebrow)
            <p class="text-sm font-semibold text-(--l600) dark:text-(--l300)">{{ $eyebrow }}</p>
        @endif
        <h1 @class(['text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white', 'mt-2' => $eyebrow])>{{ $title }}</h1>

        <details class="group/toc mt-6 rounded-lg bg-zinc-50 text-sm ring-1 ring-zinc-200 lg:hidden dark:bg-white/5 dark:ring-white/10">
            <summary class="flex cursor-pointer list-none items-center justify-between px-3 py-2 font-medium [&::-webkit-details-marker]:hidden">
                {{ $menu }} <x-heroicon-m-chevron-down class="size-4 text-zinc-400 transition group-open/toc:rotate-180" />
            </summary>
            <div class="px-3 pb-3" x-on:click="$event.target.closest('a') && $el.closest('details').removeAttribute('open')">
                @foreach ($sidebar as $group)
                    @if (! empty($group['title']))
                        <p class="mb-1 mt-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $group['title'] }}</p>
                    @endif
                    <ul class="flex flex-wrap gap-x-4 gap-y-1">
                        @foreach ($group['items'] as $item)
                            <li><a href="{{ $item['href'] ?? '#'.$item['id'] }}" @if ($item['current'] ?? false) aria-current="page" @endif
                                   @class(['text-php-700 hover:text-php-800 aria-[current]:font-semibold aria-[current]:underline aria-[current]:underline-offset-4 dark:text-php-300 dark:hover:text-php-200', 'font-mono' => $item['mono'] ?? false])>{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                    @foreach ($group['items'] as $item)
                        @if (($item['current'] ?? false) && ! empty($item['children']))
                            <p class="mb-1 mt-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">On this page</p>
                            <ul class="flex flex-wrap gap-x-4 gap-y-1">
                                @foreach ($item['children'] as $sub)
                                    <li><a href="#{{ $sub['id'] }}" class="text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">{{ $sub['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </details>

        {{ $slot }}
    </article>
</div>
