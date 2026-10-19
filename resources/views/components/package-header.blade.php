{{--
    A package host's bar: the PHPstats mark and menus, this package, and its sections.
    It replaces the site bar, so package pages show one bar.
--}}
@props(['package'])

@php
    // A section the package has nothing for is left out, rather than linking an empty page.
    $sections = array_filter([
        'overview' => 'Overview',
        'docs' => $package->hasDocs() || $package->externalDocs() ? 'Docs' : null,
        'vignettes' => $package->vignettes() ? 'Vignettes' : null,
        'benchmarks' => $package->benchmarks() ? 'Benchmarks' : null,
        'verification' => $package->verifications() ? 'Verification' : null,
    ]);
    $active = match (true) {
        request()->routeIs('package.docs') => 'docs',
        request()->routeIs('package.vignettes', 'package.vignette') || request()->is('livewire*') => 'vignettes',
        request()->routeIs('package.benchmarks') => 'benchmarks',
        request()->routeIs('package.verification') => 'verification',
        default => 'overview',
    };
    $layer = $package->layer();
@endphp

<header class="layer-{{ $layer['color'] }} sticky top-0 z-40 border-b border-zinc-200/80 bg-white/85 backdrop-blur-lg dark:border-white/5 dark:bg-zinc-950/85">
    <div class="mx-auto flex h-14 max-w-[88rem] items-center gap-1 px-4 sm:px-6 lg:px-8">
        <x-site-menus :current="$package" />
        <span class="mx-1 h-5 w-px bg-zinc-200 dark:bg-white/10" aria-hidden="true"></span>
        <a href="{{ $package->url() }}" class="flex items-center gap-2 rounded-lg px-2 py-1.5 font-mono text-sm font-semibold hover:bg-zinc-100 dark:hover:bg-white/5">{{ $package->name() }}
        </a>

        <nav class="ml-2 hidden items-center gap-1 text-sm lg:flex" aria-label="{{ $package->name() }} sections">
            @foreach ($sections as $key => $label)
                @php $external = $key === 'docs' && ! $package->hasDocs(); @endphp
                <a href="{{ $external ? $package->externalDocs() : $package->url($key) }}" @if ($active === $key) aria-current="page" @endif
                   class="relative whitespace-nowrap rounded-lg px-3 py-1.5 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 aria-[current]:text-(--l700) dark:text-zinc-400 dark:hover:bg-white/5 dark:hover:text-white dark:aria-[current]:text-(--l300)">
                    {{ $label }}@if ($external)<x-heroicon-m-arrow-up-right class="ml-0.5 inline size-3 opacity-60" />@endif
                    @if ($active === $key)<span class="absolute inset-x-3 -bottom-[0.6rem] h-0.5 rounded-full bg-(--l500)"></span>@endif
                </a>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-1">
            <x-theme-toggle />
            @if ($package->repository())
                <a href="{{ $package->repository() }}" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/5 dark:hover:text-white" aria-label="{{ $package->name() }} on GitHub"><x-github-icon /></a>
            @endif
        </div>
    </div>

    {{-- Sections on small screens --}}
    <nav class="flex gap-1 overflow-x-auto border-t border-zinc-100 px-3 py-1.5 text-sm lg:hidden dark:border-white/5" aria-label="{{ $package->name() }} sections">
        @foreach ($sections as $key => $label)
            <a href="{{ $key === 'docs' && ! $package->hasDocs() ? $package->externalDocs() : $package->url($key) }}" @if ($active === $key) aria-current="page" @endif
               class="shrink-0 rounded-md px-2.5 py-1 text-zinc-600 aria-[current]:bg-(--l50) aria-[current]:font-medium aria-[current]:text-(--l700) dark:text-zinc-400 dark:aria-[current]:bg-(--l400)/10 dark:aria-[current]:text-(--l300)">{{ $label }}</a>
        @endforeach
    </nav>
</header>
