{{-- Switches between light and dark mode. dark styles it for the landing page's dark bar. --}}
@props(['dark' => false])

<button type="button" x-data="themeToggle" x-on:click="toggle()" x-bind:aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'" aria-label="Switch color mode"
        @class([
            'rounded-lg p-2 transition',
            'text-zinc-300 hover:bg-white/10 hover:text-white' => $dark,
            'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/5 dark:hover:text-white' => ! $dark,
        ])>
    <x-heroicon-o-moon class="size-5 dark:hidden" />
    <x-heroicon-o-sun class="hidden size-5 dark:block" />
</button>
