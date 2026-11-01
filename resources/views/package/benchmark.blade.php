{{--
    One benchmark workload, from its saved record, in the evidence reports'
    sections: Scope (the task), Data (input and output check), Method (every
    methods fact the record carries, then each implementation's code),
    Results (for each case paired phiz bar charts of time and of each memory metric the record
    measured (each labeled by its exact metric), and a table of the exact
    values), and Reproduction. The earlier ids methods and code still resolve. A record with more than six cases gets
    tables only. A value the record does not have reads "not measured" and is
    left out of the charts, never drawn as zero. Nothing runs for the reader.
--}}
@use('App\Content\Results')
@use('App\Content\BenchmarkChart')
@php
    $env = $record['environment'] ?? [];
    $unit = $record['unit'] ?? 'ms';
    $names = collect(config('phpstats.benchmarks'))->first(fn (array $w) => $w['package'] === $package->key && $w['id'] === $record['id'])['names'] ?? [];
    $name = fn (string $impl) => $names[$impl] ?? $impl;
    // phit-polyfill comes last, after the extension and the R and Python libraries.
    $rows = collect($record['rows'] ?? [])->sortBy(fn (array $r) => str_starts_with($r['implementation'], 'phit-polyfill') ? 1 : 0)->values();
    $cases = $rows->groupBy('case')->map(fn ($r) => $r->values()->all())->all();
    $metrics = Results::memoryMetrics($record);
    $statistic = Results::statistic($record);
    $timeLabel = Str::ucfirst($record['measure'] ?? 'time');
    $charted = count($cases) <= 6;
    $code = $record['code'] ?? [];
    $nested = $code !== [] && is_array(reset($code));
    $facts = Results::benchmarkFacts($record);
    $reproduce = array_values(array_filter((array) ($record['reproduce'] ?? []), 'is_string'));
    $raw = "site/benchmarks/{$record['id']}.json";
    $rawUrl = $package->recordUrl('benchmarks', $record['id']);
    $source = $record['source'] ?? null;
    $ms = fn (array $row) => Results::milliseconds($row['value'] ?? null, $unit);
    $range = function (array $row) use ($unit) {
        $reps = array_values(array_filter($row['reps'] ?? [], 'is_numeric'));
        if (count($reps) < 2) {
            return null;
        }

        return Results::duration(Results::milliseconds(min($reps), $unit)).' – '.Results::duration(Results::milliseconds(max($reps), $unit));
    };
    $dataFacts = array_intersect_key($facts, array_flip(['Workload', 'Dataset', 'Output check']));
    $methodFacts = array_diff_key($facts, $dataFacts);
    $children = array_values(array_filter([
        ! empty($record['description']) ? ['id' => 'scope', 'label' => 'Scope'] : null,
        $dataFacts !== [] ? ['id' => 'data', 'label' => 'Data'] : null,
        ['id' => 'method', 'label' => 'Method'],
        ['id' => 'results', 'label' => 'Results'],
        ['id' => 'reproduction', 'label' => 'Reproduction'],
    ]));
    $what = $record['workload'] ?? (count($cases) === 1 ? array_key_first($cases) : null);
    // Lowercase a label's first letter unless it starts an acronym, as PHP does.
    $lower = fn (string $label) => preg_match('/^[A-Z]{2}/', $label) ? $label : Str::lcfirst($label);
    $named = [$lower($timeLabel), ...array_map(fn ($m) => $lower($m['label']), $metrics)];
    $measures = count($named) < 3 ? implode(' and ', $named) : implode(', ', array_slice($named, 0, -1)).', and '.end($named);
    $over = implode(' ', array_filter([
        $statistic && ! str_contains(strtolower($timeLabel), strtolower($statistic)) ? $statistic : null,
        isset($env['repeats']) ? ($statistic ? 'of ' : '').$env['repeats'].' '.Str::plural('run', $env['repeats']) : null,
    ]));
    $lead = ($what ? rtrim($what, '.').': ' : '').($what ? $measures : Str::ucfirst($measures)).' per implementation'.(count($cases) > 1 ? ' and case' : '').($over !== '' ? ", {$over}" : '').'.'.($metrics === [] ? ' Memory not measured.' : '');
    $section = 'mt-8 scroll-mt-28 lg:scroll-mt-20';
    $h2 = 'text-xl font-semibold tracking-tight text-zinc-950 dark:text-white';
    $dl = 'mt-3 grid max-w-4xl items-start gap-x-6 gap-y-1 text-sm leading-6 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-y-2';
@endphp
<x-layout :title="Results::label($record).' benchmark'" :wide="true">
    <x-sidebar-page :title="Results::label($record)" :sidebar="Results::sidebar($package, 'benchmarks', $record['id'], $children)" :wide="true" menu="Benchmarks">
        @if (! empty($record['description']))
            <section id="scope" class="mt-6 scroll-mt-28 lg:scroll-mt-20" aria-labelledby="scope-heading">
                <h2 id="scope-heading" class="{{ $h2 }}">Scope</h2>
                <p class="mt-2 max-w-3xl leading-relaxed text-zinc-700 dark:text-zinc-300">{!! \App\Content\Libraries::mono(e($record['description'])) !!}</p>
            </section>
        @endif

        @if ($dataFacts !== [])
            <section id="data" class="{{ $section }}" aria-labelledby="data-heading">
                <h2 id="data-heading" class="{{ $h2 }}">Data</h2>
                <dl class="{{ $dl }}">
                    @foreach ($dataFacts as $label => $value)
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                        <dd class="mb-2 text-zinc-800 sm:mb-0 dark:text-zinc-200">{!! \App\Content\Libraries::mono(e($value)) !!}</dd>
                    @endforeach
                </dl>
            </section>
        @endif

        <span id="methods" class="block scroll-mt-28 lg:scroll-mt-20"></span>
        <section id="method" class="{{ $section }}" aria-labelledby="method-heading">
            <h2 id="method-heading" class="{{ $h2 }}">Method</h2>
            <dl class="{{ $dl }}">
                @foreach ($methodFacts as $label => $value)
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                    <dd class="mb-2 text-zinc-800 sm:mb-0 dark:text-zinc-200">{!! \App\Content\Libraries::mono(e($value)) !!}</dd>
                @endforeach
            </dl>
            @if ($code !== [])
                <h3 id="code" class="mt-5 scroll-mt-28 text-base font-semibold text-zinc-950 lg:scroll-mt-20 dark:text-white">Code</h3>
                @if ($nested)
                    @foreach ($code as $case => $caseCode)
                        <h4 class="mt-4 text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $case }}</h4>
                        <x-implementation-code class="mt-2" :code="$caseCode" :id="'code-'.Str::slug($case)" :label="$case" :languages="$record['languages'] ?? []" :names="$names" />
                    @endforeach
                @else
                    <x-implementation-code class="mt-3" :code="$code" id="code" :label="Results::label($record)" :languages="$record['languages'] ?? []" :names="$names" />
                @endif
            @endif
        </section>

        <section id="results" class="{{ $section }}" aria-labelledby="results-heading">
            <h2 id="results-heading" class="{{ $h2 }}">Results</h2>
            {{-- One sentence on what was run and measured: the workload, the measures, and the statistic. --}}
            <p class="mt-2 max-w-3xl text-zinc-700 dark:text-zinc-300">{!! \App\Content\Libraries::mono(e($lead)) !!}</p>

            @if ($charted)
                @foreach ($cases as $case => $caseRows)
                    @php
                        $slug = 'case-'.Str::slug($case);
                        $times = array_values(array_filter(array_map(fn ($r) => ['row' => $r, 'ms' => $ms($r)], $caseRows), fn ($t) => $t['ms'] !== null && $t['ms'] > 0));
                        $seconds = $times !== [] && max(array_column($times, 'ms')) >= 1000;
                        $charts = [];
                        if ($times !== []) {
                            $charts[] = BenchmarkChart::plot(array_map(fn ($t) => ['name' => $name($t['row']['implementation']), 'value' => $seconds ? $t['ms'] / 1000 : $t['ms'], 'shown' => Results::duration($t['ms'])], $times), $timeLabel.($seconds ? ' (s)' : ' (ms)'));
                        }
                        foreach ($metrics as $metric) {
                            $bars = array_values(array_filter($caseRows, fn ($r) => is_numeric($r[$metric['field']] ?? null) && $r[$metric['field']] > 0));
                            if ($bars !== []) {
                                $charts[] = BenchmarkChart::plot(array_map(fn ($r) => ['name' => $name($r['implementation']), 'value' => (float) $r[$metric['field']], 'shown' => Results::mebibytes($r[$metric['field']])], $bars), $metric['label'].' (MiB)');
                            }
                        }
                    @endphp
                    <div id="{{ $slug }}" class="mt-6 scroll-mt-28 lg:scroll-mt-20">
                        @if (count($cases) > 1)
                            <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $case }}</h3>
                        @endif
                        @if ($charts !== [])
                            <div class="mt-4 grid gap-8 md:grid-cols-2" aria-hidden="true">
                                @foreach ($charts as $chart)
                                    <x-phiz :plot="$chart" class="min-w-0 overflow-hidden" />
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full text-left text-sm tabular-nums">
                                <caption class="sr-only">{{ $case }}: {{ strtolower($timeLabel) }}{{ $metrics ? ' and memory' : '' }} by implementation</caption>
                                <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                                    <tr>
                                        <th scope="col" class="py-2 pr-4 font-medium">Implementation</th>
                                        <th scope="col" class="py-2 pr-4 text-right font-medium">{{ $timeLabel }}</th>
                                        <th scope="col" class="py-2 pr-4 text-right font-medium">Fastest – slowest run</th>
                                        @foreach ($metrics as $metric)
                                            <th scope="col" class="py-2 pr-4 text-right font-medium last:pr-0">{{ $metric['label'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                                    @foreach ($caseRows as $row)
                                        <tr>
                                            <th scope="row" class="py-2 pr-4 font-normal">{!! \App\Content\Libraries::mono(e($name($row['implementation']))) !!}@if (! empty($row['note']))<span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $row['note'] }}</span>@endif</th>
                                            <td @class(['py-2 pr-4 text-right', 'text-zinc-500 dark:text-zinc-400' => $ms($row) === null])>{{ Results::duration($ms($row)) }}</td>
                                            <td class="whitespace-nowrap py-2 pr-4 text-right text-zinc-600 dark:text-zinc-400">{{ $range($row) ?? '' }}</td>
                                            @foreach ($metrics as $metric)
                                                <td @class(['py-2 pr-4 text-right last:pr-0', 'text-zinc-500 dark:text-zinc-400' => ! is_numeric($row[$metric['field']] ?? null)])>{{ Results::mebibytes($row[$metric['field']] ?? null) }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                @php
                    $pivot = Results::pivot(['rows' => $rows->all()]);
                    $tables = [['label' => $timeLabel, 'format' => fn ($row) => Results::duration($ms($row)), 'missing' => fn ($row) => $ms($row) === null]];
                    foreach ($metrics as $metric) {
                        $tables[] = ['label' => $metric['label'], 'format' => fn ($row) => Results::mebibytes($row[$metric['field']] ?? null), 'missing' => fn ($row) => ! is_numeric($row[$metric['field']] ?? null)];
                    }
                    $byCase = $rows->groupBy('case')->map(fn ($r) => $r->keyBy('implementation'))->all();
                @endphp
                @foreach ($tables as $table)
                    @if (count($tables) > 1)
                        <h3 class="mt-8 text-base font-semibold text-zinc-950 dark:text-white">{{ $table['label'] }}</h3>
                    @endif
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm tabular-nums">
                            <caption class="sr-only">{{ $table['label'] }} by case and implementation</caption>
                            <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="py-2 pr-4 font-medium">Case</th>
                                    @foreach ($pivot['implementations'] as $impl)
                                        <th scope="col" class="py-2 pr-4 text-right font-medium last:pr-0">{!! \App\Content\Libraries::mono(e($name($impl))) !!}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                                @foreach ($pivot['cases'] as $case)
                                    <tr id="case-{{ Str::slug($case['case']) }}">
                                        <th scope="row" class="py-2 pr-4 font-normal">{{ $case['case'] }}</th>
                                        @foreach ($pivot['implementations'] as $impl)
                                            @php $row = $byCase[$case['case']][$impl] ?? null; @endphp
                                            <td @class(['whitespace-nowrap py-2 pr-4 text-right last:pr-0', 'text-zinc-500 dark:text-zinc-400' => $row === null || $table['missing']($row)])>{{ $row === null ? 'not measured' : $table['format']($row) }}@if ($row && ! empty($row['note']))<sup class="ml-0.5 text-zinc-500">{{ array_search($row['note'], $pivot['notes'], true) + 1 }}</sup>@endif</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
                @foreach ($pivot['notes'] as $n => $note)
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"><sup>{{ $n + 1 }}</sup> {{ Str::ucfirst($note) }}</p>
                @endforeach
            @endif
        </section>

        <section id="reproduction" class="{{ $section }}" aria-labelledby="reproduction-heading">
            <h2 id="reproduction-heading" class="{{ $h2 }}">Reproduction</h2>
            @if ($reproduce !== [])
                <x-command class="mt-3" :code="implode(PHP_EOL, $reproduce)" label="From the repository root" :name="$package->name()" />
            @else
                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">No reproduction command recorded.</p>
            @endif
            <dl class="{{ $dl }}">
                @foreach ([['Raw record', $raw], ['Source', $source]] as [$label, $path])
                    @if ($path)
                        @php $url = $package->sourceUrl($path); @endphp
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                        <dd class="mb-1.5 sm:mb-0">@if ($url)<a href="{{ $url }}" class="break-all font-mono text-xs underline decoration-zinc-300 underline-offset-2 hover:text-zinc-950 dark:decoration-zinc-600 dark:hover:text-white">{{ $path }}</a>@else<span class="break-all font-mono text-xs">{{ $path }}</span>@endif</dd>
                    @endif
                @endforeach
            </dl>
        </section>
    </x-sidebar-page>
</x-layout>
