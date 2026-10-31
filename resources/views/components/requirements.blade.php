{{--
    What a package needs, from its synced site.json: required, optional with the
    feature each enables, source-build prerequisites, and platforms. Compact
    names the optional features without their detail, which is on the
    package's installation page.
--}}
@props(['package', 'compact' => false])
@php
    $optional = array_map(
        fn (array $item) => $item['for'] && ! $compact ? $item['name'].' ('.$item['for'].')' : $item['name'],
        $package->optional(),
    );
    $groups = array_filter([
        'Requires' => $package->requires(),
        'Optional' => $optional,
        'Required when building from source' => $package->build(),
        'Supported platforms' => $package->platforms(),
    ]);
@endphp
@if ($groups !== [])
    <dl {{ $attributes->merge(['class' => 'text-sm']) }}>
        @foreach ($groups as $label => $items)
            <dt class="mt-3 text-xs first:mt-0 font-semibold uppercase leading-5 tracking-wider text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
            <dd class="mt-0.5 text-zinc-700 dark:text-zinc-300">
                @foreach ($items as $item)
                    <span class="block">{{ $item }}</span>
                @endforeach
            </dd>
        @endforeach
    </dl>
@endif
