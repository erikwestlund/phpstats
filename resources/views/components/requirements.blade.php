{{--
    What a package needs, from its synced site.json: required, optional with the
    feature each enables, source-build prerequisites, and platforms. Compact,
    for the Installation page, keeps only what installing needs: the required
    items and the conditional build tools, each row on one line.
    The optional features and platforms are in the package's installation guide.
--}}
@props(['package', 'compact' => false])
@php
    $optional = array_map(
        fn (array $item) => $item['for'] ? $item['name'].' ('.$item['for'].')' : $item['name'],
        $package->optional(),
    );
    $groups = array_filter($compact ? [
        'Requires' => $package->requires(),
        $package->buildLabel() => $package->build(),
    ] : [
        'Requires' => $package->requires(),
        'Optional' => $optional,
        $package->buildLabel() => $package->build(),
        'Supported platforms' => $package->platforms(),
    ]);
@endphp
@if ($groups !== [] && $compact)
    <dl {{ $attributes->merge(['class' => 'grid gap-y-2 text-sm sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-x-4']) }}>
        @foreach ($groups as $label => $items)
            <dt class="text-xs font-semibold uppercase leading-6 tracking-wider text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
            <dd class="-mt-2 leading-6 text-zinc-700 sm:mt-0 dark:text-zinc-300">{{ implode(', ', $items) }}</dd>
        @endforeach
    </dl>
@elseif ($groups !== [])
    <dl {{ $attributes->merge(['class' => 'text-sm']) }}>
        @foreach ($groups as $label => $items)
            <dt class="mt-3 text-xs font-semibold uppercase first:mt-0 leading-5 tracking-wider text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
            <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">
                @foreach ($items as $item)
                    <span class="block">{{ $item }}</span>
                @endforeach
            </dd>
        @endforeach
    </dl>
@endif
