<x-layout :title="$page['title']" :wide="true">
    @if ($page['math'])
        @push('head')
            <script>
                window.MathJax = { tex: { inlineMath: [['\\(', '\\)']], displayMath: [['\\[', '\\]']] }, chtml: { displayOverflow: 'scroll' } };
            </script>
            <script src="https://cdn.jsdelivr.net/npm/mathjax@4.0.0/tex-chtml.js" defer></script>
        @endpush
    @endif

    <div class="grid gap-10 lg:grid-cols-[14rem_minmax(0,1fr)] xl:grid-cols-[14rem_minmax(0,1fr)_12rem]">
        <nav aria-label="Documentation" class="text-sm lg:sticky lg:top-6 lg:max-h-[calc(100vh-3rem)] lg:self-start lg:overflow-y-auto">
            <ul class="space-y-1">
                @foreach ($package->docsNav() as $item)
                    <li>
                        <a href="{{ $package->url('docs', ['path' => $item['path'] === 'index' ? null : $item['path']]) }}"
                           @if ($item['path'] === $path) aria-current="page" @endif
                           @class([
                               'block rounded px-2 py-1',
                               'bg-zinc-100 font-medium dark:bg-zinc-900' => $item['path'] === $path,
                               'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $item['path'] !== $path,
                           ])>{{ $item['title'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <article class="min-w-0">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $page['title'] }}</h1>
            <div class="docs-content prose prose-zinc mt-6 max-w-none dark:prose-invert">
                {!! $page['html'] !!}
            </div>
            @if ($sync = $package->sync())
                <p class="mt-12 text-xs text-zinc-500">
                    From <a href="https://github.com/{{ $sync['repo'] }}/tree/{{ $sync['commit'] }}" class="underline">{{ $sync['repo'].'@'.substr($sync['commit'], 0, 7) }}</a>.
                </p>
            @endif
        </article>

        @if (count($page['headings']) > 1)
            <nav aria-label="On this page" class="hidden text-sm xl:sticky xl:top-6 xl:block xl:self-start">
                <p class="font-medium">On this page</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($page['headings'] as $heading)
                        <li @class(['pl-3' => $heading['level'] === 3])>
                            <a href="#{{ $heading['id'] }}" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">{{ $heading['text'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </div>
</x-layout>
