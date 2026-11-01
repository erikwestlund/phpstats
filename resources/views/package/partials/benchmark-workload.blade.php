{{--
    One benchmark workload on a package's Benchmarks page, from its saved
    record: its label, then three tabs. Results holds the task, the input,
    the code each implementation ran, paired phiz bar charts of time and of
    each memory metric (each labeled by its exact metric), and a table of the
    exact values; a record with more than six cases gets tables only. Methods
    holds every methods fact the record carries. Reproduction holds the
    commands and links to the raw record and the source. Without JavaScript,
    and in print, the three panels show one after another under their own
    headings; #{id}-methods and the like open a tab. A value the record does
    not have reads "not measured" and is left out of the charts, never drawn
    as zero. Nothing runs for the reader.
--}}
@use('App\Content\Results')
@use('App\Content\BenchmarkChart')
@php
    $id = $record['id'];
    $env = $record['environment'] ?? [];
    $unit = $record['unit'] ?? 'ms';
    $names = collect(config('phpstats.benchmarks'))->first(fn (array $w) => $w['package'] === $package->key && $w['id'] === $id)['names'] ?? [];
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
    $raw = "site/benchmarks/{$id}.json";
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
    // Each runtime's memory with its libraries loaded and no data, in the record's memory metric.
    if (is_array($record['baseline_memory_mib'] ?? null) && $record['baseline_memory_mib'] !== []) {
        $methodFacts['Baseline memory'] = implode('; ', array_map(fn ($k, $v) => $name($k).' '.Results::mebibytes($v), array_keys($record['baseline_memory_mib']), $record['baseline_memory_mib']))
            .' ('.(Results::memoryMeasure($record) ?? 'memory').' of the runtime with its libraries loaded and no data)';
    }
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
    $tabs = ['results' => 'Results', 'methods' => 'Methods', 'reproduction' => 'Reproduction'];
    $dl = 'grid max-w-4xl items-start gap-x-6 gap-y-1 text-sm leading-6 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-y-2.5';
    $dt = 'font-medium text-zinc-500 dark:text-zinc-400';
    $dd = 'mb-2 text-zinc-800 sm:mb-0 dark:text-zinc-200';
    $mono = fn (?string $text) => \App\Content\Libraries::mono(e((string) $text));
@endphp
<section id="{{ $id }}" aria-labelledby="{{ $id }}-heading" x-data="evidenceTabs(@js($id))" class="evidence-tabs scroll-mt-10 lg:scroll-mt-0">
    <h2 id="{{ $id }}-heading" class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ Results::label($record) }}</h2>

    {{-- Hidden until the script runs, so without it the panels simply follow one another. --}}
    <div role="tablist" aria-label="{{ Results::label($record) }}" x-cloak x-on:keydown="key($event)" class="mt-4 flex gap-1 border-b border-zinc-200 text-sm dark:border-white/10">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" id="{{ $id }}-tab-{{ $key }}" aria-controls="{{ $id }}-{{ $key }}" x-ref="{{ $key }}"
                    x-on:click="select('{{ $key }}')" x-bind:aria-selected="(tab === '{{ $key }}').toString()" x-bind:tabindex="tab === '{{ $key }}' ? 0 : -1"
                    aria-selected="{{ $key === 'results' ? 'true' : 'false' }}" tabindex="{{ $key === 'results' ? 0 : -1 }}"
                    class="-mb-px border-b-2 border-transparent px-3 py-2 font-medium text-zinc-600 transition hover:text-zinc-900 aria-selected:border-php-500 aria-selected:text-php-700 dark:text-zinc-400 dark:hover:text-white dark:aria-selected:text-php-300">{{ $label }}</button>
        @endforeach
    </div>

    <div role="tabpanel" id="{{ $id }}-results" aria-labelledby="{{ $id }}-tab-results" x-show="tab === 'results'" tabindex="0" class="space-y-8 pt-6 focus:outline-none">
        <h3 class="tab-heading text-lg font-semibold text-zinc-950 dark:text-white">Results</h3>
        @if (! empty($record['description']) || $dataFacts !== [])
            <div class="space-y-4">
                @if (! empty($record['description']))
                    <p class="max-w-3xl leading-relaxed text-zinc-700 dark:text-zinc-300">{!! $mono($record['description']) !!}</p>
                @endif
                @if ($dataFacts !== [])
                    <dl class="{{ $dl }}">
                        @foreach ($dataFacts as $label => $value)
                            <dt class="{{ $dt }}">{{ $label }}</dt>
                            <dd class="{{ $dd }}">{!! $mono($value) !!}</dd>
                        @endforeach
                    </dl>
                @endif
            </div>
        @endif

        @if ($code !== [])
            <div>
                @if ($nested)
                    @foreach ($code as $case => $caseCode)
                        <h4 @class(['text-sm font-semibold text-zinc-800 dark:text-zinc-200', 'mt-6' => ! $loop->first])>{{ $case }}</h4>
                        <x-implementation-code class="mt-2" :code="$caseCode" :id="$id.'-code-'.Str::slug($case)" :label="$case" :languages="$record['languages'] ?? []" :names="$names" />
                    @endforeach
                @else
                    <x-implementation-code :code="$code" :id="$id.'-code'" :label="Results::label($record)" :languages="$record['languages'] ?? []" :names="$names" />
                @endif
            </div>
        @endif

        {{-- One sentence on what was run and measured: the workload, the measures, and the statistic. --}}
        <p class="max-w-3xl text-zinc-700 dark:text-zinc-300">{!! $mono($lead) !!}</p>

            @if ($charted)
                @foreach ($cases as $case => $caseRows)
                    @php
                        $slug = $id.'-case-'.Str::slug($case);
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
                    <div id="{{ $slug }}" class="scroll-mt-10 lg:scroll-mt-0">
                        @if (count($cases) > 1)
                            <h3 class="text-base font-semibold text-zinc-950 dark:text-white">{{ $case }}</h3>
                        @endif
                        @if ($charts !== [])
                            <div class="mt-4 grid gap-x-10 gap-y-8 md:grid-cols-2" aria-hidden="true">
                                @foreach ($charts as $chart)
                                    <x-phiz :plot="$chart" class="min-w-0 overflow-hidden" />
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-8 overflow-x-auto">
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
                                    <tr id="{{ $id }}-case-{{ Str::slug($case['case']) }}">
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
    </div>

    <div role="tabpanel" id="{{ $id }}-methods" aria-labelledby="{{ $id }}-tab-methods" x-show="tab === 'methods'" tabindex="0" class="pt-6 focus:outline-none">
        <h3 class="tab-heading mb-4 text-lg font-semibold text-zinc-950 dark:text-white">Methods</h3>
        <dl class="{{ $dl }}">
            @foreach ($methodFacts as $label => $value)
                <dt class="{{ $dt }}">{{ $label }}</dt>
                <dd class="{{ $dd }}">{!! $mono($value) !!}</dd>
            @endforeach
        </dl>
    </div>

    <div role="tabpanel" id="{{ $id }}-reproduction" aria-labelledby="{{ $id }}-tab-reproduction" x-show="tab === 'reproduction'" tabindex="0" class="space-y-4 pt-6 focus:outline-none">
        <h3 class="tab-heading text-lg font-semibold text-zinc-950 dark:text-white">Reproduction</h3>
        @if ($reproduce !== [])
            <x-command :code="implode(PHP_EOL, $reproduce)" label="From the repository root" :name="$package->name()" />
        @else
            <p class="text-sm text-zinc-700 dark:text-zinc-300">No reproduction command recorded.</p>
        @endif
        <dl class="{{ $dl }}">
            @foreach ([['Raw record', $raw], ['Source', $source]] as [$label, $path])
                @if ($path)
                    @php $url = $package->sourceUrl($path); @endphp
                    <dt class="{{ $dt }}">{{ $label }}</dt>
                    <dd class="mb-2 sm:mb-0">@if ($url)<a href="{{ $url }}" class="break-all font-mono text-xs underline decoration-zinc-300 underline-offset-2 hover:text-zinc-950 dark:decoration-zinc-600 dark:hover:text-white">{{ $path }}</a>@else<span class="break-all font-mono text-xs">{{ $path }}</span>@endif</dd>
                @endif
            @endforeach
        </dl>
    </div>
</section>
