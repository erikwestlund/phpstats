{{-- What a package needs, from its synced site.json: required, optional with the feature each enables, source-build prerequisites, and platforms. --}}
@props(['package'])
@php
    $optional = array_map(
        fn (array $item) => $item['for'] ? $item['name'].' ('.$item['for'].')' : $item['name'],
        $package->optional(),
    );
    $groups = array_filter([
        'Requires' => $package->requires(),
        'Optional' => $optional,
        'Builds with' => $package->build(),
        'Platforms' => $package->platforms(),
    ]);
@endphp
@if ($groups !== [])
    <dl {{ $attributes->merge(['class' => 'grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm']) }}>
        @foreach ($groups as $label => $items)
            <dt class="whitespace-nowrap text-xs font-semibold uppercase leading-5 tracking-wider text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
            <dd class="text-zinc-700 dark:text-zinc-300">
                @foreach ($items as $item)
                    <span class="block">{{ $item }}</span>
                @endforeach
            </dd>
        @endforeach
    </dl>
@endif
