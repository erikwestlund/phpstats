{{-- What a package requires and what it can optionally use, from its synced site.json. --}}
@props(['package'])
@php
    $groups = array_filter(['Requires' => $package->requires(), 'Optional' => $package->optional()]);
@endphp
@if ($groups !== [])
    <dl {{ $attributes->merge(['class' => 'grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm']) }}>
        @foreach ($groups as $label => $items)
            <dt class="text-xs font-semibold uppercase leading-5 tracking-wider text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
            <dd class="text-zinc-700 dark:text-zinc-300">{{ implode(' · ', $items) }}</dd>
        @endforeach
    </dl>
@endif
