{{--
    Examples as a compact list: each title linked to its page in the owning
    package's docs, with its one-sentence description. from names the owner of
    an example listed under another package.
--}}
@props(['examples', 'package' => null])
<ul {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @foreach ($examples as $example)
        <li>
            <a href="{{ $example['url'] }}" class="text-[0.9375rem] font-semibold text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">{{ $example['title'] }}</a>
            @if ($package && $example['owner'] !== $package->key)
                <span class="text-sm text-zinc-500 dark:text-zinc-400">· from <span class="font-mono">{{ \App\Content\Package::find($example['owner'])->name() }}</span></span>
            @endif
            @if ($example['description'] !== '')
                <p class="mt-0.5 text-[0.9375rem] leading-7 text-zinc-600 dark:text-zinc-400">{{ $example['description'] }}</p>
            @endif
        </li>
    @endforeach
</ul>
