<x-layout title="Benchmarks">
    <h1 class="text-3xl font-semibold tracking-tight">Benchmarks</h1>

    @php $benchmarks = $package->benchmarks(); @endphp
    @if ($benchmarks === [])
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ $package->name() }} has not published benchmark results yet.</p>
    @else
        <p class="mt-4 max-w-3xl text-zinc-700 dark:text-zinc-300">
            These results come from the benchmark files in the {{ $package->name() }} repository.
            Each report gives the machine and date it was recorded, so an older report may not reflect the current code.
        </p>
        <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm">
            @foreach ($benchmarks as $benchmark)
                <li><a href="#{{ $benchmark['id'] }}" class="underline">{{ $benchmark['title'] }}</a></li>
            @endforeach
        </ul>

        @foreach ($benchmarks as $benchmark)
            @php $pivot = \App\Content\Results::pivot($benchmark); @endphp
            <section id="{{ $benchmark['id'] }}" class="mt-12 scroll-mt-6">
                <h2 class="text-xl font-semibold">{{ $benchmark['title'] }}</h2>
                @if (! empty($benchmark['description']))
                    <p class="mt-2 max-w-3xl text-sm text-zinc-700 dark:text-zinc-300">{{ $benchmark['description'] }}</p>
                @endif
                @if ($environment = $benchmark['environment'] ?? null)
                    <p class="mt-2 text-xs text-zinc-500">
                        {{ collect([
                            $environment['cpu'] ?? null,
                            $environment['platform'] ?? null,
                            isset($environment['php']) ? "PHP {$environment['php']}" : null,
                            isset($environment['repeats']) ? "{$environment['repeats']} repeats" : null,
                            isset($environment['date']) ? 'recorded '.\Illuminate\Support\Str::before($environment['date'], 'T') : null,
                            isset($environment['commit']) ? 'commit '.substr($environment['commit'], 0, 7) : null,
                        ])->filter()->implode(' · ') }}
                    </p>
                @endif

                <x-phiz :plot="\App\Content\Results::benchmarkChart($benchmark)" class="mt-4 w-full" />

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm tabular-nums">
                        <thead class="text-left text-zinc-500">
                            <tr class="border-b border-zinc-200 dark:border-zinc-800">
                                <th class="py-2 pr-4 font-medium">Case</th>
                                @foreach ($pivot['implementations'] as $implementation)
                                    <th class="py-2 pr-4 text-right font-medium">{{ $implementation }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pivot['cases'] as $case)
                                <tr class="border-b border-zinc-100 dark:border-zinc-900">
                                    <td class="py-2 pr-4">{{ $case['case'] }}</td>
                                    @foreach ($pivot['implementations'] as $implementation)
                                        <td class="py-2 pr-4 text-right">{{ \App\Content\Results::number($case['values'][$implementation] ?? null) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-2 text-xs text-zinc-500">{{ ucfirst($benchmark['measure'] ?? 'value') }}, in {{ $benchmark['unit'] ?? 'the reported unit' }}.</p>
                </div>
            </section>
        @endforeach
    @endif
</x-layout>
