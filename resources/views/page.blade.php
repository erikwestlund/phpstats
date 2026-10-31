{{--
    A Markdown page from resources/pages, rendered by App\Content\Markdown. A
    page whose front matter has a sidebar (groups of { id, label, mono } links
    to its sections) gets a sticky section nav in the docs sidebar's style,
    which follows the scroll, and an "On this page" menu on narrow screens.
--}}
@php $sidebar = $meta['sidebar'] ?? []; @endphp
<x-layout :title="$meta['title'] ?? null" :description="$meta['description'] ?? null">
    @if ($sidebar === [])
        <article class="mx-auto max-w-3xl py-6">
            @if (! empty($meta['title']))
                <h1 class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $meta['title'] }}</h1>
            @endif
            @if (! empty($meta['lead']))
                <p class="mt-4 text-xl leading-8 text-zinc-600 dark:text-zinc-300">{{ $meta['lead'] }}</p>
            @endif
            <div class="prose prose-zinc prose-php mt-10 max-w-none dark:prose-invert prose-headings:tracking-tight prose-h2:mt-10">
                {!! $html !!}
            </div>
        </article>
    @else
        <x-sidebar-page :title="$meta['title']" :sidebar="$sidebar">
            <div class="prose prose-zinc prose-php mt-10 max-w-none dark:prose-invert prose-headings:tracking-tight prose-h2:text-xl">
                {!! $html !!}
            </div>
        </x-sidebar-page>
    @endif
</x-layout>
