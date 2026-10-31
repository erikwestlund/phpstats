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
    {{-- The label column is as wide as its longest label, so a value starts just after it and wraps under itself. --}}
    <dl {{ $attributes->merge(['class' => 'grid gap-y-1 text-sm leading-6 sm:grid-cols-[max-content_minmax(0,1fr)] sm:gap-x-4']) }}>
        @foreach ($groups as $label => $items)
            <dt @class(['font-medium text-zinc-900 dark:text-zinc-100', 'mt-1 sm:mt-0' => ! $loop->first])>{{ $label }}</dt>
            <dd class="text-zinc-600 dark:text-zinc-400">{{ implode(', ', $items) }}</dd>
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
