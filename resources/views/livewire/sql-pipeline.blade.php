<div>
    <label class="flex items-center gap-3 text-sm">
        <span class="font-medium">Minimum bedrooms</span>
        <select wire:model.live.number="minBedrooms" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
            @foreach (\App\Livewire\SqlPipeline::BEDROOMS as $bedrooms)
                <option value="{{ $bedrooms }}">{{ $bedrooms }}</option>
            @endforeach
        </select>
    </label>

    <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Generated SQL</h2>
    <pre class="mt-3 overflow-x-auto whitespace-pre-wrap break-words rounded-lg bg-zinc-100 p-4 text-xs dark:bg-zinc-900"><code>{{ $query }}</code></pre>
    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
        Bindings: [{{ implode(', ', $bindings) }}]. The database runs this one query and returns {{ count($sqlRows) }} rows to PHP.
    </p>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        @foreach (['Run as SQL' => $sqlRows, 'Run in memory' => $memoryRows] as $title => $rows)
            <div class="overflow-x-auto">
                <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">{{ $title }}</h2>
                <table class="mt-3 w-full text-sm tabular-nums">
                    <thead class="text-left text-zinc-500">
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            <th class="py-2 pr-4 font-medium">Neighborhood</th>
                            <th class="py-2 pr-4 text-right font-medium">Rentals</th>
                            <th class="py-2 text-right font-medium">Mean rent per m²</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-b border-zinc-100 dark:border-zinc-900">
                                <td class="py-2 pr-4">{{ $row['neighborhood'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($row['rentals']) }}</td>
                                <td class="py-2 text-right">{{ number_format($row['mean_rent_per_m2'], 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
        @if ($maxDifference === null)
            The two results differ in their rows.
        @else
            The two results have the same rows in the same order. The largest difference in mean rent per m² is {{ sprintf('%.1e', $maxDifference) }}.
        @endif
    </p>
</div>
