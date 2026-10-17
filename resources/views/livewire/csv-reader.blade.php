<div>
    <div class="flex flex-wrap items-end gap-6 text-sm">
        <label class="flex flex-col gap-2">
            <span class="font-medium">Delimiter</span>
            <select wire:model.live="delimiter" class="rounded border border-zinc-300 bg-transparent px-2 py-1 dark:border-zinc-700">
                @foreach (\App\Livewire\CsvReader::DELIMITERS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="button" wire:click="resetSample" class="rounded border border-zinc-300 px-3 py-1 hover:border-zinc-500 dark:border-zinc-700">
            Restore the sample
        </button>
    </div>

    <label class="mt-4 block text-sm">
        <span class="font-medium">CSV text</span>
        <textarea wire:model.live.debounce.500ms="csv" rows="9" spellcheck="false"
                  maxlength="{{ \App\Livewire\CsvReader::MAX_BYTES }}"
                  class="mt-2 block w-full rounded-lg border border-zinc-300 bg-transparent p-3 font-mono text-sm dark:border-zinc-700"></textarea>
    </label>

    @if (! $extensionLoaded)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This PHP process does not have the php_stats extension with stats_read_csv(), so the text cannot be read.
        </p>
    @elseif (isset($error))
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">{{ $error }}</p>
    @elseif ($native['names'] === [])
        <p class="mt-6 text-sm text-zinc-600 dark:text-zinc-400">The text has no header row.</p>
    @else
        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Columns</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        <th class="py-2 pr-4 font-medium">Name</th>
                        <th class="py-2 pr-4 font-medium">Guessed type</th>
                        <th class="py-2 pr-4 text-right font-medium">Missing</th>
                        <th class="py-2 text-right font-medium">Failed to parse</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($native['names'] as $i => $name)
                        @php($column = $native['columns'][$i])
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            <td class="py-2 pr-4 font-mono">{{ $name }}</td>
                            <td class="py-2 pr-4">{{ $column['type'] }}</td>
                            <td class="py-2 pr-4 text-right">{{ count(array_filter($column['values'], fn ($v) => $v === null)) }}</td>
                            <td class="py-2 text-right">{{ $column['failed'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Missing counts include the values that failed to parse, which the reader sets to NA.
            </p>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Problems</h2>
        @if ($native['problems'] === [])
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">None.</p>
        @else
            <ul class="mt-3 list-disc pl-5 font-mono text-sm">
                @foreach ($native['problems'] as $problem)
                    <li>{{ $problem }}</li>
                @endforeach
            </ul>
        @endif

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Values</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-sm tabular-nums">
                <thead class="text-left text-zinc-500">
                    <tr class="border-b border-zinc-200 dark:border-zinc-800">
                        @foreach ($native['names'] as $name)
                            <th class="whitespace-nowrap py-2 pr-4 font-mono font-medium">{{ $name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($preview as $row)
                        <tr class="border-b border-zinc-100 dark:border-zinc-900">
                            @foreach ($row as $value)
                                <td @class(['whitespace-nowrap py-2 pr-4', 'text-zinc-400' => $value === 'NA'])>{{ $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                The first 10 rows. Dates arrive as days since 1970-01-01 and datetimes as microseconds since the epoch in UTC; this table formats them.
            </p>
        </div>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Agreement with phibble's PHP reader</h2>
        @if ($parity['error'])
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">DataFrame::fromCsv() raised an error: {{ $parity['error'] }}</p>
        @else
            <ul class="mt-3 space-y-1 text-sm">
                <li>Column types: {{ $parity['same_types'] ? 'identical' : 'different' }}</li>
                <li>Values: {{ $parity['same_values'] ? 'identical' : 'different' }}</li>
                <li>Warnings: {{ $parity['same_warnings'] ? 'identical' : 'different' }}</li>
            </ul>
            @foreach ($parity['warnings'] as $warning)
                <p class="mt-3 font-mono text-sm text-zinc-600 dark:text-zinc-400">{{ $warning }}</p>
            @endforeach
        @endif
    @endif
</div>
