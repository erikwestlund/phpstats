<div>
    <div class="flex flex-wrap gap-8 text-sm">
        <label class="flex flex-col gap-2">
            <span class="font-medium">Rows</span>
            <select wire:model.live.number="copies" class="rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-700">
                @foreach (\App\Livewire\PolarsPipeline::COPIES as $copies)
                    <option value="{{ $copies }}">{{ number_format(3000 * $copies) }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-2">
            <span class="font-medium">Minimum bedrooms</span>
            <select wire:model.live.number="minBedrooms" class="rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-700">
                @foreach (\App\Livewire\SqlPipeline::BEDROOMS as $bedrooms)
                    <option value="{{ $bedrooms }}">{{ $bedrooms }}</option>
                @endforeach
            </select>
        </label>
    </div>

    @unless ($available)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            @if (! $extensionLoaded)
                This PHP process does not have the php_polars extension, so the pipeline runs only on the in-memory DataFrame.
            @else
                php_polars is loaded, but the installed phibble release does not include Phibble\Polars\PolarsTable, so the pipeline runs only on the in-memory DataFrame.
            @endif
        </p>
    @endunless

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm tabular-nums">
            <thead class="text-left text-zinc-500">
                <tr class="border-b border-zinc-200 dark:border-zinc-800">
                    <th class="py-2 pr-4 font-medium">Neighborhood</th>
                    <th class="py-2 pr-4 text-right font-medium">Rentals</th>
                    <th class="py-2 pr-4 text-right font-medium">DataFrame: rent per m²</th>
                    @if ($polars)
                        <th class="py-2 text-right font-medium">Polars: rent per m²</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($dfRows as $i => $row)
                    <tr class="border-b border-zinc-100 dark:border-zinc-900">
                        <td class="py-2 pr-4">{{ $row['neighborhood'] }}</td>
                        <td class="py-2 pr-4 text-right">{{ number_format($row['rentals']) }}</td>
                        <td class="py-2 pr-4 text-right">{{ number_format($row['mean_rent_per_m2'], 4) }}</td>
                        @if ($polars)
                            <td class="py-2 text-right">{{ isset($polars['rows'][$i]) ? number_format($polars['rows'][$i]['mean_rent_per_m2'], 4) : '' }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">This request</h2>
    <ul class="mt-3 space-y-1 text-sm tabular-nums">
        <li>Input: {{ number_format($rowsIn) }} rows.</li>
        <li>DataFrame pipeline: {{ number_format(1000 * $dfSeconds, 1) }} ms.</li>
        @if ($polars)
            <li>Copying the DataFrame into Polars: {{ number_format(1000 * $polars['load_seconds'], 1) }} ms.</li>
            <li>PolarsTable pipeline, including collecting the result: {{ number_format(1000 * $polars['seconds'], 1) }} ms.</li>
            <li>
                @if ($polars['max_difference'] === null)
                    The two backends returned different rows.
                @else
                    The backends returned the same rows in the same order. The largest difference in mean rent per m² is {{ sprintf('%.1e', $polars['max_difference']) }}.
                @endif
            </li>
            <li>php_polars {{ $polars['version'] }}.</li>
        @endif
    </ul>
    <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
        Timings come from one run on this server and vary between requests.
        The benchmarks page has repeated measurements.
    </p>
</div>
