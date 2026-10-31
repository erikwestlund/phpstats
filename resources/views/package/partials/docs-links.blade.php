{{-- One docs sidebar group's links. --}}
<ul class="space-y-0.5 border-l border-zinc-200 dark:border-white/10">
    @foreach ($items as $item)
        <li>
            <a href="{{ $docsUrl($item['path']) }}" @if ($item['path'] === $path) aria-current="page" @endif
               class="-ml-px block border-l border-transparent py-1 pl-4 text-zinc-600 transition hover:border-zinc-400 hover:text-zinc-900 aria-[current]:border-(--l500) aria-[current]:font-medium aria-[current]:text-(--l700) dark:text-zinc-400 dark:hover:text-white dark:aria-[current]:text-(--l300)">{{ $item['title'] }}</a>
        </li>
    @endforeach
</ul>
