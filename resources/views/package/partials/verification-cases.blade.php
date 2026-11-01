{{--
    Every comparison of a verification record in one table: the case (shown
    once at the top of its run of rows), the quantity, the rule, the values
    compared, the package's and the reference's values where the record has
    them, the errors, each peer's error, and the status as plain text, with
    the reason under a failed or skipped one.
--}}
@use('App\Content\Results')
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm tabular-nums">
        <caption class="sr-only">Every comparison</caption>
        <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
            <tr>
                @if ($byCategory)<th scope="col" class="py-2 pr-4 font-medium">Category</th><th scope="col" class="py-2 pr-4 font-medium">Case</th>@endif
                @if ($showName)<th scope="col" class="py-2 pr-4 font-medium">Case</th>@endif
                @if ($hasImplementation)<th scope="col" class="py-2 pr-4 font-medium">Implementation</th>@endif
                <th scope="col" class="py-2 pr-4 font-medium">Quantity</th>
                @if ($hasRule)<th scope="col" class="py-2 pr-4 font-medium">Criterion</th>@endif
                @foreach ($columns as $column)
                    @if ($column === 'n_compared')
                        <th scope="col" class="py-2 pr-4 text-right font-medium">{{ $labels[$column] }}</th>
                        @if ($hasValues)
                            <th scope="col" class="py-2 pr-4 text-right font-medium">Package value</th>
                            <th scope="col" class="py-2 pr-4 text-right font-medium">Reference value</th>
                        @endif
                    @endif
                @endforeach
                @if ($hasValues && ! in_array('n_compared', $columns, true))
                    <th scope="col" class="py-2 pr-4 text-right font-medium">Package value</th>
                    <th scope="col" class="py-2 pr-4 text-right font-medium">Reference value</th>
                @endif
                @foreach ($columns as $column)
                    @if ($column !== 'n_compared')<th scope="col" class="py-2 pr-4 text-right font-medium">{{ $labels[$column] }}</th>@endif
                @endforeach
                @foreach ($peers as $peer)
                    <th scope="col" class="py-2 pr-4 text-right font-medium">{!! \App\Content\Libraries::mono(e($peer)) !!}</th>
                @endforeach
                <th scope="col" class="py-2 font-medium">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
            @php $previous = null; @endphp
            @foreach ($cases as $case)
                @php
                    $status = Results::status($case);
                    $name = (string) ($byCategory ? ($case['category'] ?? '') : ($case['name'] ?? ''));
                    $first = $name !== $previous;
                    $previous = $name;
                @endphp
                <tr @class(['border-t border-zinc-200 dark:border-white/10' => ($showName || $byCategory) && $first && ! $loop->first])>
                    @if ($byCategory)
                        <td class="py-1.5 pr-4 align-top">{{ $first ? $name : '' }}</td>
                        <td class="py-1.5 pr-4 align-top font-mono text-xs">{{ $case['name'] ?? '' }}</td>
                    @endif
                    @if ($showName)<td class="py-1.5 pr-4 align-top">{{ $first ? $name : '' }}</td>@endif
                    @if ($hasImplementation)<td class="py-1.5 pr-4 align-top">{!! \App\Content\Libraries::mono(e($case['implementation'] ?? '')) !!}</td>@endif
                    <td class="py-1.5 pr-4 align-top">{{ $case['quantity'] ?? '' }}</td>
                    @if ($hasRule)<td class="whitespace-nowrap py-1.5 pr-4 align-top font-mono text-xs">{{ $ruleText[$case['rule'] ?? ''] ?? ($case['rule'] ?? '') }}</td>@endif
                    @if (in_array('n_compared', $columns, true))
                        <td class="py-1.5 pr-4 text-right align-top">{{ isset($case['n_compared']) ? number_format($case['n_compared']) : '' }}</td>
                    @endif
                    @if ($hasValues)
                        <td class="py-1.5 pr-4 text-right align-top font-mono text-xs">{{ $number($case['actual'] ?? null) }}</td>
                        <td class="py-1.5 pr-4 text-right align-top font-mono text-xs">{{ $number($case['expected'] ?? null) }}</td>
                    @endif
                    @foreach ($columns as $column)
                        {{-- A relative error the record leaves null, where the reference is zero, is undefined, not zero. --}}
                        @if ($column === 'max_rel_error' && array_key_exists($column, $case) && $case[$column] === null)
                            <td class="whitespace-nowrap py-1.5 pr-4 text-right align-top text-xs text-zinc-500 dark:text-zinc-400">undefined at r = 0</td>
                        @elseif ($column !== 'n_compared')
                            <td class="py-1.5 pr-4 text-right align-top font-mono text-xs">{{ Results::error($case[$column] ?? null) }}</td>
                        @endif
                    @endforeach
                    @foreach ($peers as $peer)
                        <td class="py-1.5 pr-4 text-right align-top font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ Results::error($case['peers'][$peer] ?? null) }}</td>
                    @endforeach
                    <td class="py-1.5 align-top">
                        <span class="{{ $statusClass($status) }}">{{ $status }}</span>
                        @if ($status !== 'pass' && ! empty($case['reason']))<span class="block max-w-xs text-xs text-zinc-600 dark:text-zinc-400">{{ $case['reason'] }}</span>@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
