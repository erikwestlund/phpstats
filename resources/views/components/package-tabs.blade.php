@props(['package'])

@php
    $sections = [
        'overview' => 'Overview',
        'docs' => 'Docs',
        'vignettes' => 'Vignettes',
        'benchmarks' => 'Benchmarks',
        'verification' => 'Verification',
    ];
    $active = match (true) {
        request()->routeIs('package.docs') => 'docs',
        request()->routeIs('package.vignettes', 'package.vignette') || request()->is('livewire*') => 'vignettes',
        request()->routeIs('package.benchmarks') => 'benchmarks',
        request()->routeIs('package.verification') => 'verification',
        default => 'overview',
    };
@endphp

<div class="border-t border-zinc-100 dark:border-zinc-900">
    <nav class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-1 gap-y-1 px-4 text-sm" aria-label="{{ $package->name() }} sections">
        <span class="mr-4 py-3 font-semibold">{{ $package->name() }}</span>
        @foreach ($sections as $key => $label)
            @php $external = $key === 'docs' && ! $package->hasDocs(); @endphp
            @if ($key !== 'docs' || $package->hasDocs() || $package->externalDocs())
                <a href="{{ $external ? $package->externalDocs() : $package->url($key) }}"
                   @if ($active === $key) aria-current="page" @endif
                   @class([
                       'border-b-2 px-3 py-3',
                       'border-zinc-900 font-medium dark:border-zinc-100' => $active === $key,
                       'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $active !== $key,
                   ])>{{ $label }}@if ($external) <span aria-hidden="true">↗</span>@endif</a>
            @endif
        @endforeach
        @if ($package->repository())
            <a href="{{ $package->repository() }}" class="ml-auto py-3 text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">GitHub ↗</a>
        @endif
    </nav>
</div>
