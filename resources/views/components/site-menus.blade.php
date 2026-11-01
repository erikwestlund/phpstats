{{-- The start of every bar: the PHPstats mark, linking home, and one menu per group of packages. --}}
@props(['current' => null, 'dark' => false])

<a href="{{ route('home') }}" @class([
    'flex shrink-0 items-center gap-2 rounded-lg py-1.5 pr-2 text-sm font-semibold',
    'text-white' => $dark,
    'text-zinc-900 dark:text-white' => ! $dark,
]) aria-label="PHPstats home">
    <x-logo :size="28" />
    {{-- On the narrowest screens the mark alone keeps the bar from overflowing; the link keeps its label. --}}
    <span @class(['hidden sm:inline' => $current, 'max-[419px]:sr-only' => ! $current])>PHPstats</span>
</a>
<div class="flex items-center">
    @foreach (config('phpstats.menus') as $menu)
        <x-group-menu :menu="$menu" :current="$current" :dark="$dark" />
    @endforeach
</div>
