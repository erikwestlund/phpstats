<x-layout title="Benchmarks" :wide="true">
    <x-page-header :eyebrow="$package->name()" title="Benchmarks">
        Timings from the benchmark files in the {{ $package->name() }} repository. Each report gives the machine and date it was recorded, so an older report may not reflect the current code. Values without a measurement are left out of the charts.
    </x-page-header>

    @php $benchmarks = $package->benchmarks(); @endphp
    @if ($benchmarks === [])
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ $package->name() }} has not published benchmark results yet.</p>
    @else
        @php
            // One machine is named once above the reports; several are named in each report.
            $machines = collect($benchmarks)->groupBy(fn (array $b) => \App\Content\Results::machine($b['environment'] ?? []));
        @endphp
        <ul class="mt-8 flex flex-wrap gap-2 text-sm">
            @foreach ($benchmarks as $benchmark)
                <li><a href="#{{ $benchmark['id'] }}" class="inline-block rounded-full bg-zinc-100 px-3 py-1 text-zinc-700 transition hover:bg-(--l100) hover:text-(--l800) dark:bg-white/5 dark:text-zinc-300 dark:hover:bg-(--l400)/15 dark:hover:text-white">{{ $benchmark['title'] }}</a></li>
            @endforeach
        </ul>

        @if ($machines->count() === 1 && $machines->keys()->first() !== '')
            <p class="mt-6 text-sm text-zinc-600 dark:text-zinc-400"><span class="font-medium text-zinc-900 dark:text-zinc-100">Machine:</span> {{ $machines->keys()->first() }}</p>
        @endif

        @foreach ($benchmarks as $benchmark)
            @php
                $pivot = \App\Content\Results::pivot($benchmark);
                $methods = \App\Content\Results::methods($benchmark);
                $machine = \App\Content\Results::machine($benchmark['environment'] ?? []);
            @endphp
            <section id="{{ $benchmark['id'] }}" class="mt-10 scroll-mt-24 rounded-2xl p-4 ring-1 ring-zinc-200 sm:p-8 dark:ring-white/10">
                <h2 class="text-xl font-semibold tracking-tight">{{ $benchmark['title'] }}</h2>
                @if (! empty($benchmark['description']))
                    <p class="mt-2 max-w-3xl text-sm text-zinc-700 dark:text-zinc-300">{{ $benchmark['description'] }}</p>
                @endif
                @if ($methods !== [] || ! empty($benchmark['method']))
                    <dl class="mt-4 grid max-w-4xl gap-x-6 gap-y-1 text-xs text-zinc-600 sm:grid-cols-[auto_minmax(0,1fr)] dark:text-zinc-400">
                        @if ($machines->count() > 1 && $machine !== '')
                            <dt class="font-medium text-zinc-500">Machine</dt><dd class="mb-1 sm:mb-0">{{ $machine }}</dd>
                        @endif
                        @foreach ($methods as $label => $value)
                            <dt class="font-medium text-zinc-500">{{ $label }}</dt><dd class="mb-1 sm:mb-0">{{ $value }}</dd>
                        @endforeach
                        @if (! empty($benchmark['method']))
                            <dt class="font-medium text-zinc-500">Timing</dt>
                            <dd>
                                <details>
                                    <summary class="cursor-pointer text-(--l700) dark:text-(--l300)">What the time includes</summary>
                                    <p class="mt-1 max-w-3xl">{{ $benchmark['method'] }}</p>
                                </details>
                            </dd>
                        @endif
                    </dl>
                @endif

                <x-phiz :plot="\App\Content\Results::benchmarkChart($benchmark)" class="mt-6 w-full" />

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-sm tabular-nums">
                        <thead class="text-left text-xs uppercase tracking-wider text-zinc-500">
                            <tr class="border-b border-zinc-200 dark:border-white/10">
                                <th class="py-2 pr-4 font-medium">Case</th>
                                @foreach ($pivot['implementations'] as $implementation)
                                    <th class="py-2 pr-4 text-right font-medium">{{ $implementation }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pivot['cases'] as $case)
                                <tr class="border-b border-zinc-100 last:border-0 hover:bg-zinc-50/60 dark:border-white/5 dark:hover:bg-white/[0.02]">
                                    <td class="py-2 pr-4">{{ $case['case'] }}</td>
                                    @foreach ($pivot['implementations'] as $implementation)
                                        @php
                                            $value = $case['values'][$implementation] ?? null;
                                            $mark = $case['marks'][$implementation] ?? null;
                                        @endphp
                                        <td class="py-2 pr-4 text-right">
                                            {{ $value === null && $mark ? 'not measured' : \App\Content\Results::number($value) }}@if ($mark)<sup class="ml-0.5 text-zinc-500">{{ $mark }}</sup>@endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-2 text-xs text-zinc-500">{{ ucfirst($benchmark['measure'] ?? 'value') }}, in {{ $benchmark['unit'] ?? 'the reported unit' }}.</p>
                    @foreach ($pivot['notes'] as $n => $note)
                        <p class="text-xs text-zinc-500"><sup>{{ $n + 1 }}</sup> {{ ucfirst($note) }}.</p>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
</x-layout>
