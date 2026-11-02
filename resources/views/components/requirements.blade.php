{{--
    What a package needs, from its synced site.json: required, optional with the
    feature each enables, source-build prerequisites, and platforms. Compact,
    for the Installation page, keeps only what installing needs: the required
    items and the conditional build tools, each row on one line.
    The optional features and platforms are in the package's installation guide.
    Secondary, for the package overview, whose hero already gives PHP, the
    platforms, and the required items, keeps only the optional items and the
    build tools, each group a card with one row per item.
--}}
@props(['package', 'compact' => false, 'secondary' => false])
@php
    $optional = array_map(
        fn (array $item) => $item['for'] ? $item['name'].' ('.$item['for'].')' : $item['name'],
        $package->optional(),
    );
    $groups = array_filter($secondary ? [
        'Optional' => $optional,
        $package->buildLabel() => $package->build(),
    ] : ($compact ? [
        'Requires' => $package->requires(),
        $package->buildLabel() => $package->build(),
    ] : [
        'Requires' => $package->requires(),
        'Optional' => $optional,
        $package->buildLabel() => $package->build(),
        'Supported platforms' => $package->platforms(),
    ]));
@endphp
@if ($groups !== [] && $compact)
    {{-- Each row runs in after its label, so no column leaves a gap. --}}
    <div {{ $attributes->merge(['class' => 'space-y-1 text-[0.9375rem] leading-7 text-zinc-600 dark:text-zinc-400']) }}>
        @foreach ($groups as $label => $items)
            <p><span class="mr-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $label }}:</span> {{ implode(', ', $items) }}</p>
        @endforeach
    </div>
@elseif ($secondary && $groups !== [])
    {{-- One card per group: each dependency in mono with the feature it enables beside it. --}}
    <div {{ $attributes->merge(['class' => 'space-y-6']) }}>
        @foreach (array_filter(['Optional' => $package->optional(), $package->buildLabel() => array_map(fn (string $name) => ['name' => $name, 'for' => null], $package->build())]) as $label => $items)
            <div class="overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:ring-white/10">
                <p class="border-b border-zinc-200 bg-zinc-50 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-400">{{ $label }}</p>
                <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                    @foreach ($items as $item)
                        <li class="grid gap-1 px-5 py-3.5 text-sm sm:grid-cols-[minmax(0,15rem)_1fr] sm:gap-6">
                            {{-- An identifier (phit, php_polars, illuminate/database, FFI) is mono; a product name (Quarto) is not. --}}
                            @php([$head, $rest] = array_pad(explode(' ', $item['name'], 2), 2, null))
                            <span class="font-medium break-words text-zinc-900 dark:text-zinc-100"><span @class(['font-mono' => preg_match('/^([a-z]|[A-Z]+$)/', $head)])>{{ $head }}</span>{{ $rest !== null ? ' '.$rest : '' }}</span>
                            @if ($item['for'])
                                <span class="text-zinc-600 dark:text-zinc-400">{{ ucfirst($item['for']) }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
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
