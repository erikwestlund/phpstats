{{--
    One list of a package's capabilities on its overview: a heading, linked
    to the package's function reference when it has one, an optional lead
    sentence, and the items as inline Markdown, so function names are code.
--}}
<section class="min-w-0 {{ $class }}">
    <h2 class="text-lg font-semibold tracking-tight">
        @if ($list['url'])
            <a href="{{ $list['url'] }}" class="hover:text-(--l700) dark:hover:text-(--l300)">{{ $list['heading'] }}</a>
        @else
            {{ $list['heading'] }}
        @endif
    </h2>
    <div class="prose prose-sm prose-zinc prose-php mt-3 max-w-none break-words dark:prose-invert prose-li:my-1 prose-ul:mt-2">
        @if ($list['lead'])
            <p class="mt-0">{!! \App\Content\Markdown::inline($list['lead']) !!}</p>
        @endif
        <ul>
            @foreach ($list['items'] as $item)
                <li>{!! \App\Content\Markdown::inline($item) !!}</li>
            @endforeach
        </ul>
    </div>
</section>
