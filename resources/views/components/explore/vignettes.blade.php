{{-- Links to live vignettes, chosen by owner/slug. --}}
@props(['items'])

<div {{ $attributes->class('not-prose grid gap-3 sm:grid-cols-2') }}>
    @foreach ($items as $item)
        @php
            [$owner, $slug] = explode('/', $item);
            $package = \App\Content\Package::find($owner);
            $vignette = $package?->vignette($slug);
        @endphp
        @if ($vignette)
            <a href="{{ $package->url('vignette', ['slug' => $slug]) }}" class="group rounded-xl bg-white p-4 ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-php-500/10 hover:ring-php-300 dark:bg-white/[0.03] dark:ring-white/10 dark:hover:ring-php-500/40">
                <span class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
                    <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                    {{ $vignette['title'] }}
                </span>
                <span class="mt-1 line-clamp-2 block text-xs text-zinc-500 dark:text-zinc-400">{{ $vignette['summary'] }}</span>
            </a>
        @endif
    @endforeach
</div>
