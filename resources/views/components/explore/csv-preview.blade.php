{{-- The first rows of a CSV in database/data, read by phibble with its inferred column types. --}}
@props(['file', 'rows' => 5])

@php
    $df = \Phibble\DataFrame::fromCsv(database_path("data/{$file}"));
    $types = $df->types();
@endphp
<div class="not-prose overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:ring-white/10">
    <div class="flex items-center justify-between gap-4 border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-xs dark:border-white/10 dark:bg-white/[0.03]">
        <span class="font-mono text-zinc-600 dark:text-zinc-300">{{ $file }}</span>
        <span class="text-zinc-500">{{ number_format($df->nrow()) }} rows × {{ count($types) }} columns</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm tabular-nums">
            <thead>
                <tr class="text-left">
                    @foreach ($types as $name => $type)
                        <th class="px-4 pt-2.5 font-semibold text-zinc-800 dark:text-zinc-100">{{ $name }}</th>
                    @endforeach
                </tr>
                <tr class="text-left">
                    @foreach ($types as $type)
                        <th class="px-4 pb-2 font-mono text-[0.7rem] font-normal text-php-600 dark:text-php-300">&lt;{{ $type->value }}&gt;</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($df->head($rows)->toRows() as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td class="px-4 py-1.5 text-zinc-600 dark:text-zinc-300">{{ is_float($value) ? rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.') : $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
