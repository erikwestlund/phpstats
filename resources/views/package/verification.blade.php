<x-layout title="Verification" :wide="true">
    <x-page-header :eyebrow="$package->name()" title="Verification">
        Each report compares {{ $package->name() }} with a reference implementation on the same inputs, at a stated tolerance.
    </x-page-header>

    @php
        // Reports with per-quantity cases get the full treatment; a record
        // without them, such as a comparison of interfaces, shows its
        // description so its anchor still resolves.
        $records = $package->verifications();
        $reports = array_values(array_filter($records, fn (array $r) => isset($r['cases'])));
        $comparisons = array_values(array_filter($records, fn (array $r) => ! isset($r['cases'])));
    @endphp
    @foreach ($comparisons as $comparison)
        <section id="{{ $comparison['id'] }}" class="mt-12 scroll-mt-24 rounded-2xl p-6 ring-1 ring-zinc-200 sm:p-8 dark:ring-white/10">
            <h2 class="text-xl font-semibold tracking-tight">{{ $comparison['title'] }}</h2>
            @if (! empty($comparison['description']))
                <p class="mt-2 max-w-3xl text-sm text-zinc-700 dark:text-zinc-300">{{ $comparison['description'] }}</p>
            @endif
        </section>
    @endforeach
    @if ($reports === [] && $comparisons === [])
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ $package->name() }} has not published verification results yet.</p>
    @elseif ($reports !== [])
        @php
            $total = array_sum(array_map(fn ($report) => count($report['cases']), $reports));
            $failed = array_sum(array_map(fn ($report) => count(array_filter($report['cases'], fn ($case) => ! ($case['passed'] ?? false))), $reports));
        @endphp
        <div class="mt-10 grid gap-4 sm:grid-cols-3">
            @foreach ([
                [count($reports), \Illuminate\Support\Str::plural('report', count($reports))],
                [number_format($total), 'comparisons'],
                [$failed === 0 ? 'All pass' : number_format($total - $failed).' pass', $failed === 0 ? 'within tolerance' : number_format($failed).' outside tolerance'],
            ] as [$value, $label])
                <div class="rounded-2xl bg-gradient-to-br from-(--l50) to-white p-5 ring-1 ring-(--l100) dark:from-(--l400)/10 dark:to-transparent dark:ring-(--l400)/15">
                    <p class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $value }}</p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $label }}</p>
                </div>
            @endforeach
        </div>
        <ul class="mt-8 flex flex-wrap gap-2 text-sm">
            @foreach ($reports as $report)
                <li><a href="#{{ $report['id'] }}" class="inline-block rounded-full bg-zinc-100 px-3 py-1 text-zinc-700 transition hover:bg-(--l100) hover:text-(--l800) dark:bg-white/5 dark:text-zinc-300 dark:hover:bg-(--l400)/15 dark:hover:text-white">{{ $report['title'] }}</a></li>
            @endforeach
        </ul>

        @foreach ($reports as $report)
            @php
                $groups = \App\Content\Results::groups($report);
                ['columns' => $columns, 'peers' => $peers] = \App\Content\Results::verificationColumns($report);
                $passed = count(array_filter($report['cases'], fn ($case) => $case['passed'] ?? false));
                $labels = ['n_compared' => 'Values', 'max_abs_error' => 'Max abs. error', 'max_rel_error' => 'Max rel. error'];
            @endphp
            <section id="{{ $report['id'] }}" class="mt-12 scroll-mt-24 rounded-2xl p-6 ring-1 ring-zinc-200 sm:p-8 dark:ring-white/10">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-semibold tracking-tight">{{ $report['title'] }}</h2>
                    <span @class(['rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', 'bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20' => $passed === count($report['cases']), 'bg-rose-50 text-rose-700 ring-rose-600/15 dark:bg-rose-400/10 dark:text-rose-300' => $passed !== count($report['cases'])])>{{ number_format($passed) }}/{{ number_format(count($report['cases'])) }} pass</span>
                </div>
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

                <div class="mt-6 divide-y divide-zinc-100 overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:divide-white/5 dark:ring-white/10">
                    @foreach ($groups as $group)
                        <details class="group">
                            <summary class="flex cursor-pointer flex-wrap items-baseline gap-x-4 px-4 py-2.5 text-sm transition hover:bg-zinc-50 group-open:bg-(--l50)/60 dark:hover:bg-white/[0.03] dark:group-open:bg-(--l400)/5">
                                <span @class(['w-4', 'text-emerald-600 dark:text-emerald-400' => $group['passed'], 'text-rose-600' => ! $group['passed']])>{{ $group['passed'] ? '✓' : '✗' }}</span>
                                <span class="font-medium">{{ $group['name'] }}</span>
                                <span class="text-zinc-500">{{ count($group['cases']) }} {{ \Illuminate\Support\Str::plural('quantity', count($group['cases'])) }}</span>
                                <span class="ml-auto font-mono text-xs tabular-nums text-zinc-500">
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
