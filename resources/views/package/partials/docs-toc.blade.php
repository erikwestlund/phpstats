{{--
    A docs page's sections for "On this page": each h2, with its h3 entries
    indented under it. spy marks the links for tocSpy, which highlights the
    section in view; the narrow-screen copy has no highlighting.
--}}
@php
    $link = 'block text-zinc-500 transition hover:text-zinc-900 aria-[current]:font-medium aria-[current]:text-(--l700) dark:text-zinc-400 dark:hover:text-white dark:aria-[current]:text-(--l300)';
@endphp
<ul class="space-y-2">
    @foreach ($toc as $section)
        <li>
            <a href="#{{ $section['id'] }}" @if ($spy) data-toc="{{ $section['id'] }}" x-bind:aria-current="active === '{{ $section['id'] }}' ? 'location' : null" @endif
               class="{{ $link }} font-medium text-zinc-700 dark:text-zinc-300">{{ $section['text'] }}</a>
            @if ($section['children'] !== [])
                <ul class="mt-1.5 space-y-1.5 border-l border-zinc-200 pl-3 dark:border-white/10">
                    @foreach ($section['children'] as $entry)
                        <li><a href="#{{ $entry['id'] }}" @if ($spy) data-toc="{{ $entry['id'] }}" x-bind:aria-current="active === '{{ $entry['id'] }}' ? 'location' : null" @endif class="{{ $link }}">{{ $entry['text'] }}</a></li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
