{{--
    One menu of the site bar: the packages of a group from config/phpstats.php
    menus, with their layers. The menu holding the current package is marked.
    dark styles the button for the landing page's dark bar. On a package bar
    ($current set), the full label waits for 2xl screens to leave room for
    the section links.
--}}
@props(['menu', 'current' => null, 'dark' => false, 'align' => 'left'])

@php
    $layers = array_intersect_key(config('phpstats.layers'), array_flip($menu['layers']));
    $holdsCurrent = $current && collect($layers)->contains(fn ($layer) => in_array($current->key, $layer['packages'], true));
@endphp

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false" class="relative">
    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-expanded="false" aria-haspopup="true"
            @class([
                'flex items-center gap-1 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm font-medium transition',
                'text-zinc-300 hover:bg-white/10 hover:text-white' => $dark,
                'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $dark,
                'text-zinc-900! dark:text-white!' => $holdsCurrent,
            ])>
        <span @class(['xl:hidden' => ! $current, '2xl:hidden' => $current])>{{ $menu['short'] }}</span><span @class(['hidden', 'xl:inline' => ! $current, '2xl:inline' => $current])>{{ $menu['title'] }}</span>
        <x-heroicon-m-chevron-down class="size-4 opacity-50 transition" x-bind:class="open && 'rotate-180'" />
    </button>

    <div x-show="open" x-cloak x-transition.origin.top.{{ $align }}
         class="fixed inset-x-4 top-16 z-50 overflow-hidden rounded-2xl bg-white p-1.5 shadow-2xl shadow-php-950/20 ring-1 ring-zinc-900/10 sm:absolute sm:inset-x-auto {{ $align === 'right' ? 'sm:right-0' : 'sm:left-0' }} sm:top-full sm:mt-2 sm:w-80 dark:bg-zinc-900 dark:ring-white/10">
        @foreach ($layers as $layer)
            <div class="layer-{{ $layer['color'] }}">
                @if (count($layers) > 1)
                    <p class="px-2.5 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-(--l600) dark:text-(--l300)">{{ $layer['title'] }}</p>
                @endif
                <ul>
                    @foreach ($layer['packages'] as $key)
                        @php $item = \App\Content\Package::find($key); @endphp
                        <li>
                            <a href="{{ $item->url() }}" @if ($current?->key === $key) aria-current="page" @endif
                               class="block rounded-lg px-3 py-2 transition hover:bg-(--l50) aria-[current]:bg-(--l50) dark:hover:bg-(--l400)/10 dark:aria-[current]:bg-(--l400)/10">
                                <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $item->name() }}</span>
                                <span class="mt-px block text-xs leading-[1.125rem] text-zinc-500 dark:text-zinc-400">{{ $item->tagline() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
