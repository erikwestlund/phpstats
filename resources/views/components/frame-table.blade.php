{{-- The first rows of a phibble or phpolars table, with its size and column types. --}}
@props(['frame', 'rows' => 6])

@php
    $types = $frame->types();
    $format = fn (mixed $value) => match (true) {
        $value === null => 'NA',
        $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
        is_float($value) => number_format($value, 2, '.', ''),
        is_bool($value) => $value ? 'TRUE' : 'FALSE',
        default => (string) $value,
    };
@endphp
<div {{ $attributes }}>
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
                        <th class="px-4 pb-2 font-mono text-[0.7rem] font-normal text-php-600 dark:text-php-300">&lt;{{ $type instanceof \BackedEnum ? $type->value : $type }}&gt;</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                @foreach ($frame->head($rows)->toRows() as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td class="px-4 py-1.5 whitespace-nowrap text-zinc-600 dark:text-zinc-300">{{ $format($value) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="border-t border-zinc-100 px-4 py-2 text-xs text-zinc-500 dark:border-white/5">
        {{ number_format($frame->nrow()) }} {{ $frame->nrow() === 1 ? 'row' : 'rows' }} × {{ count($types) }} columns{{ $frame->nrow() > $rows ? ', first '.$rows.' shown' : '' }}
    </p>
</div>
