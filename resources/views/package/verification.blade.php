<x-layout title="Verification">
    <h1 class="text-3xl font-semibold tracking-tight">Verification</h1>

    @php $reports = $package->verifications(); @endphp
    @if ($reports === [])
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ $package->name() }} has not published verification results yet.</p>
    @else
        @php
            $total = array_sum(array_map(fn ($report) => count($report['cases']), $reports));
            $failed = array_sum(array_map(fn ($report) => count(array_filter($report['cases'], fn ($case) => ! ($case['passed'] ?? false))), $reports));
        @endphp
        <p class="mt-4 max-w-3xl text-zinc-700 dark:text-zinc-300">
            Each report compares {{ $package->name() }} with a reference implementation on the same inputs.
            The {{ count($reports) }} reports below hold {{ number_format($total) }} comparisons, and {{ $failed === 0 ? 'all of them pass' : number_format($failed).' do not pass' }}.
        </p>
        <ul class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm">
            @foreach ($reports as $report)
                <li><a href="#{{ $report['id'] }}" class="underline">{{ $report['title'] }}</a></li>
            @endforeach
        </ul>

        @foreach ($reports as $report)
            @php
                $groups = \App\Content\Results::groups($report);
                ['columns' => $columns, 'peers' => $peers] = \App\Content\Results::verificationColumns($report);
                $passed = count(array_filter($report['cases'], fn ($case) => $case['passed'] ?? false));
                $labels = ['n_compared' => 'Values', 'max_abs_error' => 'Max abs. error', 'max_rel_error' => 'Max rel. error'];
            @endphp
            <section id="{{ $report['id'] }}" class="mt-12 scroll-mt-6">
                <h2 class="text-xl font-semibold">{{ $report['title'] }}</h2>
                @if (! empty($report['description']))
                    <p class="mt-2 max-w-3xl text-sm text-zinc-700 dark:text-zinc-300">{{ $report['description'] }}</p>
                @endif
                <dl class="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[auto_1fr]">
                    @if (! empty($report['reference']))
                        <dt class="text-zinc-500">Reference</dt><dd>{{ $report['reference'] }}</dd>
                    @endif
                    @if (isset($report['tolerance']))
                        <dt class="text-zinc-500">Tolerance</dt><dd>{{ is_numeric($report['tolerance']) ? \App\Content\Results::error($report['tolerance']) : $report['tolerance'] }}</dd>
                    @endif
                    <dt class="text-zinc-500">Result</dt>
                    <dd @class(['font-medium', 'text-emerald-700 dark:text-emerald-400' => $passed === count($report['cases']), 'text-red-700 dark:text-red-400' => $passed !== count($report['cases'])])>
                        {{ number_format($passed) }} of {{ number_format(count($report['cases'])) }} comparisons pass, across {{ count($groups) }} {{ \Illuminate\Support\Str::plural('case', count($groups)) }}.
                    </dd>
                </dl>

                <div class="mt-4 divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-900 dark:border-zinc-800">
                    @foreach ($groups as $group)
                        <details class="group">
                            <summary class="flex cursor-pointer flex-wrap items-baseline gap-x-4 px-4 py-2 text-sm">
                                <span @class(['w-4', 'text-emerald-600' => $group['passed'], 'text-red-600' => ! $group['passed']])>{{ $group['passed'] ? '✓' : '✗' }}</span>
                                <span class="font-medium">{{ $group['name'] }}</span>
                                <span class="text-zinc-500">{{ count($group['cases']) }} {{ \Illuminate\Support\Str::plural('quantity', count($group['cases'])) }}</span>
                                <span class="ml-auto tabular-nums text-zinc-500">
                                    @if ($group['worst_abs'] !== null) worst abs. {{ \App\Content\Results::error($group['worst_abs']) }} @endif
                                    @if ($group['worst_rel'] !== null) worst rel. {{ \App\Content\Results::error($group['worst_rel']) }} @endif
                                </span>
                            </summary>
                            <div class="overflow-x-auto px-4 pb-3">
                                <table class="w-full text-sm tabular-nums">
                                    <thead class="text-left text-zinc-500">
                                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                                            <th class="py-1.5 pr-4 font-medium">Quantity</th>
                                            @foreach ($columns as $column)
                                                <th class="py-1.5 pr-4 text-right font-medium">{{ $labels[$column] }}</th>
                                            @endforeach
                                            @foreach ($peers as $peer)
                                                <th class="py-1.5 pr-4 text-right font-medium">{{ $peer }}</th>
                                            @endforeach
                                            <th class="py-1.5 text-right font-medium">Pass</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group['cases'] as $case)
                                            <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-900">
                                                <td class="py-1.5 pr-4">{{ $case['quantity'] ?? '' }}</td>
                                                @foreach ($columns as $column)
                                                    <td class="py-1.5 pr-4 text-right">{{ $column === 'n_compared' ? number_format($case[$column] ?? 0) : \App\Content\Results::error($case[$column] ?? null) }}</td>
                                                @endforeach
                                                @foreach ($peers as $peer)
                                                    <td class="py-1.5 pr-4 text-right text-zinc-500">{{ \App\Content\Results::error($case['peers'][$peer] ?? null) }}</td>
                                                @endforeach
                                                <td class="py-1.5 text-right">{{ ($case['passed'] ?? false) ? '✓' : '✗' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @endforeach
                </div>
                @if ($peers)
                    <p class="mt-2 text-xs text-zinc-500">Peer columns give the same error measure for {{ implode(' and ', $peers) }} against the reference.</p>
                @endif
            </section>
        @endforeach
    @endif
</x-layout>
