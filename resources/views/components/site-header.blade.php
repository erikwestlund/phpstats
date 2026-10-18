{{-- The phpstats.org bar, dark to sit on the landing hero. --}}
<header class="sticky top-0 z-40 border-b border-white/5 bg-zinc-950/95 backdrop-blur-lg">
    <nav class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
        <x-family-menu :dark="true" />
        <div class="ml-auto flex items-center gap-1 text-sm text-zinc-300">
            <a href="{{ route('home') }}#packages" class="hidden rounded-lg px-3 py-1.5 hover:bg-white/10 hover:text-white sm:block">Packages</a>
            <a href="{{ route('page', 'about') }}" @class(['hidden rounded-lg px-3 py-1.5 hover:bg-white/10 hover:text-white sm:block', 'text-white' => request()->routeIs('page')])>Why PHPstats</a>
            <a href="https://github.com/phpstats" class="rounded-lg p-2 hover:bg-white/10 hover:text-white" aria-label="PHPstats on GitHub"><x-github-icon /></a>
        </div>
    </nav>
</header>
