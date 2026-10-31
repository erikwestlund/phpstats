{{--
    A Markdown page from resources/pages, rendered by App\Content\Markdown. A
    page whose front matter has a sidebar (groups of { id, label, mono } links
    to its sections) gets a sticky section nav in the docs sidebar's style,
    which follows the scroll, and an "On this page" menu on narrow screens.
--}}
@php
    $sidebar = $meta['sidebar'] ?? [];
    $link = 'block border-l border-transparent py-1 pl-4 -ml-px text-zinc-600 transition hover:border-zinc-400 hover:text-zinc-900 aria-[current]:border-php-500 aria-[current]:font-medium aria-[current]:text-php-700 dark:text-zinc-400 dark:hover:text-white dark:aria-[current]:text-php-300';
@endphp
<x-layout :title="$meta['title'] ?? null" :description="$meta['description'] ?? null">
    @if ($sidebar === [])
        <article class="mx-auto max-w-3xl py-6">
            @if (! empty($meta['title']))
                <h1 class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $meta['title'] }}</h1>
            @endif
            @if (! empty($meta['lead']))
                <p class="mt-4 text-xl leading-8 text-zinc-600 dark:text-zinc-300">{{ $meta['lead'] }}</p>
            @endif
            <div class="prose prose-zinc prose-php mt-10 max-w-none dark:prose-invert prose-headings:tracking-tight prose-h2:mt-12">
                {!! $html !!}
            </div>
        </article>
    @else
        <div x-data="tocSpy" class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[13rem_minmax(0,1fr)]">
            <nav aria-label="Sections" class="hidden text-sm lg:sticky lg:top-14 lg:block lg:max-h-[calc(100vh-3.5rem)] lg:self-start lg:overflow-y-auto lg:py-8">
                @foreach ($sidebar as $group)
                    @if (! empty($group['title']))
                        <p @class(['mb-2 text-xs font-semibold uppercase tracking-wider text-zinc-900 dark:text-white', 'mt-7' => ! $loop->first])>{{ $group['title'] }}</p>
                    @endif
                    <ul @class(['space-y-0.5 border-l border-zinc-200 dark:border-white/10', 'mt-7' => ! $loop->first && empty($group['title'])])>
                        @foreach ($group['items'] as $item)
                            <li>
                                <a href="#{{ $item['id'] }}" data-toc="{{ $item['id'] }}"
                                   x-bind:aria-current="active === '{{ $item['id'] }}' ? 'location' : null"
                                   @class([$link, 'font-mono' => $item['mono'] ?? false])>{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>

            <article class="min-w-0 max-w-3xl py-6">
                <h1 class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $meta['title'] }}</h1>

                <details class="group/toc mt-6 rounded-lg bg-zinc-50 text-sm ring-1 ring-zinc-200 lg:hidden dark:bg-white/5 dark:ring-white/10">
                    <summary class="flex cursor-pointer list-none items-center justify-between px-3 py-2 font-medium [&::-webkit-details-marker]:hidden">
                        On this page <x-heroicon-m-chevron-down class="size-4 text-zinc-400 transition group-open/toc:rotate-180" />
                    </summary>
                    <div class="px-3 pb-3" x-on:click="$event.target.closest('a') && $el.closest('details').removeAttribute('open')">
                        @foreach ($sidebar as $group)
                            @if (! empty($group['title']))
                                <p class="mb-1 mt-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $group['title'] }}</p>
                            @endif
                            <ul class="flex flex-wrap gap-x-4 gap-y-1">
                                @foreach ($group['items'] as $item)
                                    <li><a href="#{{ $item['id'] }}" @class(['text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200', 'font-mono' => $item['mono'] ?? false])>{{ $item['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </details>

                <div class="prose prose-zinc prose-php mt-10 max-w-none dark:prose-invert prose-headings:tracking-tight prose-h2:text-xl">
                    {!! $html !!}
                </div>
            </article>
        </div>
    @endif
</x-layout>
