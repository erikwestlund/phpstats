<div>
    @if (! $data)
        <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            No benchmark results are stored. Run php artisan benchmarks:sync.
        </p>
    @else
        <label class="flex items-center gap-3 text-sm">
            <span class="font-medium">Model</span>
            <select wire:model.live="model" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
                @foreach ($data['models'] as $key => $m)
                    <option value="{{ $key }}">{{ $m['label'] }}</option>
                @endforeach
            </select>
        </label>

        <x-phiz :plot="$chart" wire:key="benchmark-chart" class="mt-6 w-full" />

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Rows</th>
                        <th class="py-2 pr-4 text-right font-medium">Coefficients</th>
                        @if ($report['cases'][0]['groups'])
                            <th class="py-2 pr-4 text-right font-medium">Groups</th>
                        @endif
                        @foreach ($implementations as $name)
                            <th class="py-2 pr-4 text-right font-medium">{{ \App\Livewire\BenchmarkResults::name($name) }}</th>
                        @endforeach
                        <th class="py-2 text-right font-medium">Agrees with R</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['cases'] as $case)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4">{{ number_format($case['n']) }}</td>
                            <td class="py-2 pr-4 text-right">{{ $case['p'] }}</td>
                            @if ($case['groups'])
                                <td class="py-2 pr-4 text-right">{{ number_format($case['groups']) }}</td>
                            @endif
                            @foreach ($implementations as $name)
                                @php($s = $case['implementations'][$name]['median_fit_seconds'])
                                <td class="py-2 pr-4 text-right">{{ $s > 0 ? ($s < 0.01 ? number_format($s * 1000, 2).' ms' : number_format($s, 3).' s') : '< 1 ms' }}</td>
                            @endforeach
                            <td class="py-2 text-right">{{ $case['agreement_passed'] ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
            Median fit time over {{ $report['repeats'] ?? 'several' }} repetitions, recorded {{ \Illuminate\Support\Carbon::parse($report['recorded_at'])->format('F j, Y') }} on an {{ $report['cpu'] }}.
            Versions:
            @foreach ($report['cases'][0]['implementations'] as $name => $impl)
                {{ \App\Livewire\BenchmarkResults::name($name) }} {{ \App\Livewire\BenchmarkResults::version($name, $impl['version']) }}{{ $loop->last ? '.' : ',' }}
            @endforeach
            R's timer has millisecond resolution, so fits under 1 ms show as &lt; 1 ms and are left out of the chart.
            <a href="{{ $report['source'] }}" class="underline">Full report</a>.
        </p>
        @if ($note)
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $note['text'] }} See <a href="{{ $note['url'] }}" class="underline">{{ $note['link'] }}</a>.
            </p>
        @endif
    @endif
</div>
