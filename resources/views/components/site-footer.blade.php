<footer class="border-t border-zinc-200 bg-zinc-50 dark:border-white/5 dark:bg-zinc-950">
    {{-- The brand on the left and the two package groups on the right, one column each, stacked on narrow screens. --}}
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 md:grid-cols-[minmax(0,1fr)_auto_auto] md:gap-x-20 lg:gap-x-28 lg:px-8">
        <div class="sm:col-span-2 md:col-span-1">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold"><x-logo :size="24" /> PHPstats</a>
            <p class="mt-3 max-w-sm text-sm leading-6 text-zinc-600 dark:text-zinc-400">Modern statistical computing for PHP. Open source under the phpstats organization on GitHub.</p>
        </div>
        @foreach (config('phpstats.layers') as $layer)
            <div class="layer-{{ $layer['color'] }}">
                <p class="text-xs font-semibold uppercase tracking-wider text-(--l600) dark:text-(--l300)">{{ $layer['title'] }}</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($layer['packages'] as $key)
                        <li>
                            <a href="{{ \App\Content\Package::find($key)->url() }}" class="font-mono text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">{{ \App\Content\Package::find($key)->name() }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</footer>
