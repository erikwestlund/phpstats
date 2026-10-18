{{--
    The PHPstats dropdown: every package by layer, plus the landing page.
    dark styles the button for the landing page's dark bar.
--}}
@props(['current' => null, 'dark' => false, 'label' => 'PHPstats'])

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false" class="relative">
    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-haspopup="true"
            @class([
                'flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-semibold transition',
                'text-white hover:bg-white/10' => $dark,
                'text-zinc-900 hover:bg-zinc-100 dark:text-white dark:hover:bg-white/5' => ! $dark,
            ])>
        <x-logo :size="24" />
        <span>{{ $label }}</span>
        <x-heroicon-m-chevron-down class="size-4 opacity-60 transition" x-bind:class="open && 'rotate-180'" />
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.left
         class="absolute left-0 top-full z-50 mt-2 w-[min(40rem,calc(100vw-2rem))] overflow-hidden rounded-2xl bg-white shadow-2xl shadow-php-950/20 ring-1 ring-zinc-900/10 dark:bg-zinc-900 dark:ring-white/10">
        <div class="grid gap-x-2 p-3 sm:grid-cols-2">
            @foreach (config('phpstats.layers') as $layer)
                <div class="layer-{{ $layer['color'] }} p-2">
                    <p class="flex items-center gap-2 px-2 text-xs font-semibold uppercase tracking-wider text-(--l600) dark:text-(--l300)">
                        <span class="size-1.5 rounded-full bg-(--l500)"></span>{{ $layer['title'] }}
                    </p>
                    <ul class="mt-1">
                        @foreach (array_keys($layer['packages']) as $key)
                            @php $item = \App\Content\Package::find($key); @endphp
                            <li>
                                <a href="{{ $item->url() }}" @if ($current?->key === $key) aria-current="page" @endif
                                   class="block rounded-lg px-2 py-1.5 transition hover:bg-(--l50) aria-[current]:bg-(--l50) dark:hover:bg-(--l400)/10 dark:aria-[current]:bg-(--l400)/10">
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $item->name() }}</span>
                                    <span class="mt-0.5 line-clamp-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $item->project['summary'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <div class="flex items-center justify-between gap-4 border-t border-zinc-100 bg-zinc-50 px-5 py-3 text-sm dark:border-white/5 dark:bg-white/[0.02]">
            <a href="{{ route('home') }}" class="font-medium text-php-600 hover:text-php-700 dark:text-php-300 dark:hover:text-php-200">phpstats.org home</a>
            <a href="https://github.com/phpstats" class="flex items-center gap-1.5 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"><x-github-icon class="size-4" /> github.com/phpstats</a>
        </div>
    </div>
</div>
