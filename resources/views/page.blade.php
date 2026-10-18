{{-- A Markdown page from resources/pages, rendered by App\Content\Markdown. --}}
<x-layout :title="$meta['title'] ?? null">
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
</x-layout>
