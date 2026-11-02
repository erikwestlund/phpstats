{{--
    One verification topic, from its saved record, in the fixed sections of
    the evidence reports, each left out when the record has nothing for it:
    Scope (what was checked), References (implementations and versions),
    Data (inputs), Method (how the check ran), Tolerances (one criterion per
    rule, with the symbols defined once), Results (counts, the comparisons
    that did not pass, a summary by case or category, and every comparison,
    in one disclosure when the list is long), Limitations (exclusions and
    known differences), and Reproduction. Empty anchors keep the earlier
    section ids (compared, reference-and-versions, metric-and-tolerance,
    known-exceptions) working.
--}}
@use('App\Content\Results')
@php
    $cases = $record['cases'] ?? [];
    $counts = Results::statusCounts($cases);
    // Cases with a category are summarized by category; otherwise by name,
    // when several rows share one.
    $byCategory = collect($cases)->contains(fn ($c) => isset($c['category']));
    $groups = Results::groups(['cases' => $cases], $byCategory ? 'category' : 'name');
    $names = count(array_unique(array_map(fn ($c) => $c['name'] ?? '', $cases)));
    $grouped = count($groups) > 1 && count($groups) < count($cases);
    ['columns' => $columns, 'peers' => $peers] = Results::verificationColumns(['cases' => $cases]);
    $hasValues = collect($cases)->contains(fn ($c) => array_key_exists('actual', $c) || array_key_exists('expected', $c));
    $hasRule = collect($cases)->contains(fn ($c) => isset($c['rule']));
    $rules = Results::rules($record);
    $floors = collect($record['comparison'] ?? [])->filter(fn ($r) => is_array($r) && isset($r['rel_floor']))->pluck('rel_floor')->unique()->values();
    $problems = array_values(array_filter($cases, fn ($c) => Results::status($c) !== 'pass'));
    $kinds = ['reference' => 'Comparison with a reference implementation', 'contract' => 'Expected values and invariants of the package', 'integration' => 'End-to-end run with the external program'];
    $inputs = $record['inputs'] ?? null;
    $versions = is_array($record['versions'] ?? null) ? $record['versions'] : [];
    $env = is_array($record['environment'] ?? null) ? $record['environment'] : [];
    $recorded = implode(', ', array_filter([
        isset($env['date']) ? Str::before($env['date'], 'T') : null,
        isset($env['php']) ? 'PHP '.$env['php'] : null,
        $env['os'] ?? null,
        isset($env['commit']) ? 'commit '.substr($env['commit'], 0, 7) : null,
    ]));
    $reproduce = array_values(array_filter((array) ($record['reproduce'] ?? []), 'is_string'));
    $raw = "site/verifications/{$record['id']}.json";
    $long = count($cases) > 60;
    $labels = ['n_compared' => 'Values', 'max_abs_error' => 'Max abs. error', 'max_rel_error' => 'Max rel. error'];
    $number = fn ($v) => is_numeric($v) ? (is_int($v) ? number_format($v) : rtrim(rtrim(sprintf('%.10g', $v), '0'), '.')) : (string) ($v ?? '');
    $statusClass = fn (string $status) => match ($status) {
        'fail' => 'font-semibold text-rose-700 dark:text-rose-400',
        'pass' => 'text-zinc-700 dark:text-zinc-300',
        default => 'font-medium text-amber-700 dark:text-amber-400',
    };
    $extras = isset($record['draw_differences']) || isset($record['summary_differences']) || isset($record['summaries']);
    $scope = $record['checked'] ?? $record['description'] ?? null;
    $method = isset($record['checked']) ? ($record['description'] ?? null) : null;
    $hasData = $inputs !== null || is_array($record['data'] ?? null) || is_string($record['model'] ?? null) || is_array($record['arguments'] ?? null);
    $hasMethod = $method || isset($kinds[$record['kind'] ?? '']) || $recorded !== '' || ! empty($record['note']);
    $hasReferences = ! empty($record['reference']) || $versions !== [];
    $hasLimitations = ! empty($record['exclusions']) || ! empty($record['known_differences']);
    $children = array_values(array_filter([
        $scope ? ['id' => 'scope', 'label' => 'Scope'] : null,
        $hasReferences ? ['id' => 'references', 'label' => 'References'] : null,
        $hasData ? ['id' => 'data', 'label' => 'Data'] : null,
        $hasMethod ? ['id' => 'method', 'label' => 'Method'] : null,
        ['id' => 'tolerances', 'label' => 'Tolerances'],
        ['id' => 'results', 'label' => 'Results'],
        $hasLimitations ? ['id' => 'limitations', 'label' => 'Limitations'] : null,
        ['id' => 'reproduction', 'label' => 'Reproduction'],
    ]));
    $section = 'mt-8 scroll-mt-10 lg:scroll-mt-0';
    $alias = 'block scroll-mt-10 lg:scroll-mt-0';
    $h2 = 'text-xl font-semibold tracking-tight text-zinc-950 dark:text-white';
    $dl = 'mt-3 grid max-w-4xl items-start gap-x-6 gap-y-1 text-sm leading-6 sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-y-2';
    $dt = 'font-medium text-zinc-500 dark:text-zinc-400';
    $dd = 'mb-2 text-zinc-800 sm:mb-0 dark:text-zinc-200';
    $link = 'break-all font-mono text-xs underline decoration-zinc-300 underline-offset-2 hover:text-zinc-950 dark:decoration-zinc-600 dark:hover:text-white';
    $mono = fn (?string $text) => \App\Content\Libraries::mono(e((string) $text));
@endphp
<x-layout :title="Results::label($record).' verification'" :wide="true">
    <x-sidebar-page :title="Results::label($record)" :sidebar="Results::sidebar($package, 'verification', $record['id'], $children)" :wide="true" menu="Verification">
        {{-- Earlier section ids, before the sections took their report names. --}}
        <span id="compared" class="{{ $alias }}"></span>
        @if ($scope)
            <section id="scope" class="mt-6 scroll-mt-10 lg:scroll-mt-0" aria-labelledby="scope-heading">
                <h2 id="scope-heading" class="{{ $h2 }}">Scope</h2>
                <p class="mt-2 max-w-3xl leading-relaxed text-zinc-700 dark:text-zinc-300">{!! $mono($scope) !!}</p>
            </section>
        @endif

        <span id="reference-and-versions" class="{{ $alias }}"></span>
        @if ($hasReferences)
            <section id="references" class="{{ $section }}" aria-labelledby="references-heading">
                <h2 id="references-heading" class="{{ $h2 }}">References</h2>
                <dl class="{{ $dl }}">
                    @if (! empty($record['reference']))
                        <dt class="{{ $dt }}">Reference</dt>
                        <dd class="{{ $dd }}">{!! $mono($record['reference']) !!}</dd>
                    @endif
                    @if ($versions !== [])
                        <dt class="{{ $dt }}">Versions</dt>
                        <dd class="{{ $dd }}">{!! $mono(implode(', ', array_map(fn ($k, $v) => "{$k} ".preg_replace('/^.*?\bversion\s+/', '', (string) $v), array_keys($versions), $versions))) !!}</dd>
                    @endif
                </dl>
            </section>
        @endif

        @if ($hasData)
            <section id="data" class="{{ $section }}" aria-labelledby="data-heading">
                <h2 id="data-heading" class="{{ $h2 }}">Data</h2>
                <dl class="{{ $dl }}">
                    @if (is_string($inputs))
                        <dt class="{{ $dt }}">Inputs</dt>
                        <dd class="{{ $dd }}">{!! $mono($inputs) !!}</dd>
                    @elseif (is_array($inputs) && $inputs !== [])
                        @foreach ($inputs as $input)
                            @php $url = $package->sourceUrl($input['url'] ?? null); @endphp
                            <dt class="{{ $dt }} font-mono">{{ $input['name'] ?? '' }}</dt>
                            <dd class="{{ $dd }}">{{ $input['detail'] ?? '' }}@if ($url) <a href="{{ $url }}" class="underline decoration-zinc-300 underline-offset-2 dark:decoration-zinc-600">Source</a>@endif</dd>
                        @endforeach
                    @elseif (is_array($record['data'] ?? null))
                        {{-- The input values themselves, as the Stan data of an interface comparison. --}}
                        <dt class="{{ $dt }}">Values</dt>
                        <dd class="{{ $dd }}"><code class="break-all font-mono text-xs">{{ json_encode($record['data'], JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) }}</code></dd>
                    @endif
                    @if (is_array($record['arguments'] ?? null))
                        <dt class="{{ $dt }}">Arguments</dt>
                        <dd class="{{ $dd }}">{{ implode('; ', array_map(fn ($k, $v) => str_replace('_', ' ', $k).' '.(is_array($v) ? implode(', ', $v) : $v), array_keys($record['arguments']), $record['arguments'])) }}</dd>
                    @endif
                </dl>
                @if (is_string($record['model'] ?? null))
                    <x-code class="mt-4" :code="$record['model']" lang="txt" label="Stan program" />
                @endif
            </section>
        @endif

        @if ($hasMethod)
            <section id="method" class="{{ $section }}" aria-labelledby="method-heading">
                <h2 id="method-heading" class="{{ $h2 }}">Method</h2>
                @if ($method)
                    <p class="mt-2 max-w-3xl leading-relaxed text-zinc-700 dark:text-zinc-300">{!! $mono($method) !!}</p>
                @endif
                <dl class="{{ $dl }}">
                    @if (isset($kinds[$record['kind'] ?? '']))
                        <dt class="{{ $dt }}">Check</dt>
                        <dd class="{{ $dd }}">{{ $kinds[$record['kind']] }}</dd>
                    @endif
                    @if (! empty($record['note']))
                        <dt class="{{ $dt }}">Note</dt>
                        <dd class="{{ $dd }}">{!! $mono($record['note']) !!}</dd>
                    @endif
                    @if ($recorded !== '')
                        <dt class="{{ $dt }}">Recorded</dt>
                        <dd class="{{ $dd }}">{{ $recorded }}</dd>
                    @endif
                </dl>
            </section>
        @endif

        <span id="metric-and-tolerance" class="{{ $alias }}"></span>
        <section id="tolerances" class="{{ $section }}" aria-labelledby="tolerances-heading">
            <h2 id="tolerances-heading" class="{{ $h2 }}">Tolerances</h2>
            @if ($rules === [])
                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">Not stated in the record.</p>
            @else
                <dl class="{{ $dl }}">
                    @foreach ($rules as $rule)
                        <dt class="{{ $dt }}">{{ $rule['applies_to'] ? Str::ucfirst(Str::before($rule['applies_to'], ';')) : ($rule['id'] ?? 'All values') }}</dt>
                        <dd class="{{ $dd }} font-mono text-[0.8125rem]">{!! $mono($rule['text']) !!}@if ($rule['applies_to'] && str_contains($rule['applies_to'], ';'))<span class="block font-sans text-xs text-zinc-500 dark:text-zinc-400">{{ Str::ucfirst(trim(Str::after($rule['applies_to'], ';'))) }}</span>@endif</dd>
                    @endforeach
                </dl>
            @endif
            @if ($rules !== [] || array_intersect(['max_abs_error', 'max_rel_error'], $columns))
            <p class="mt-3 max-w-3xl text-sm text-zinc-600 dark:text-zinc-400">
                x: package value; r: reference value.
                @if (in_array('max_abs_error', $columns, true)) Absolute error: |x − r|. @endif
                @if (in_array('max_rel_error', $columns, true))
                    Relative error: |x − r| / {{ $floors->isNotEmpty() ? 'max(|r|, '.$floors->map(fn ($f) => Results::tolerance((float) $f))->implode(' or ').')' : '|r|' }}{{ $floors->isEmpty() ? '; undefined at r = 0' : '' }}.
                @endif
                @if ($columns !== [] && $columns !== ['n_compared']) Max errors are over the values a row compares. @endif
                @if ($peers !== []) {!! $mono(implode(', ', $peers)) !!}: {{ count($peers) === 1 ? 'that implementation’s' : 'each implementation’s' }} error against the same reference. @endif
            </p>
            @endif
        </section>

        <section id="results" class="{{ $section }}" aria-labelledby="results-heading">
            <h2 id="results-heading" class="{{ $h2 }}">Results</h2>
            <p class="mt-2 text-zinc-700 dark:text-zinc-300">
                {{ number_format(count($cases)) }} {{ Str::plural('comparison', count($cases)) }}@if ($grouped) in {{ number_format(count($groups)) }} {{ Str::plural($byCategory ? 'category' : 'case', count($groups)) }}@endif:
                {{ number_format($counts['pass']) }} pass, {{ number_format($counts['fail']) }} fail, {{ number_format($counts['skipped']) }} skipped{{ $counts['not reported'] ? ', '.number_format($counts['not reported']).' not reported' : '' }}.
            </p>

            {{-- A short, ungrouped table shows each status and reason itself. --}}
            @if ($problems !== [] && ($long || $grouped))
                <h3 class="mt-6 text-base font-semibold text-zinc-950 dark:text-white">Not passed</h3>
                <div class="mt-2 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Comparisons that did not pass</caption>
                        <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                            @php
                                // Columns only for fields some listed case has.
                                $problemImplementation = collect($problems)->contains(fn ($case) => filled($case['implementation'] ?? null));
                                $problemQuantity = collect($problems)->contains(fn ($case) => filled($case['quantity'] ?? null));
                            @endphp
                            <tr><th scope="col" class="py-2 pr-4 font-medium">Case</th>@if ($problemImplementation)<th scope="col" class="py-2 pr-4 font-medium">Implementation</th>@endif @if ($problemQuantity)<th scope="col" class="py-2 pr-4 font-medium">Quantity</th>@endif<th scope="col" class="py-2 pr-4 font-medium">Status</th><th scope="col" class="py-2 font-medium">Reason</th></tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                            @foreach ($problems as $case)
                                <tr>
                                    <td class="py-2 pr-4 align-top">{{ $case['name'] ?? '' }}</td>
                                    @if ($problemImplementation)<td class="py-2 pr-4 align-top">{{ $case['implementation'] ?? '' }}</td>@endif
                                    @if ($problemQuantity)<td class="py-2 pr-4 align-top">{{ $case['quantity'] ?? '' }}</td>@endif
                                    <td @class(['py-2 pr-4 align-top', $statusClass(Results::status($case))])>{{ Results::status($case) }}</td>
                                    <td class="py-2 align-top">{{ $case['reason'] ?? 'No reason recorded.' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($grouped)
                <h3 class="mt-6 text-base font-semibold text-zinc-950 dark:text-white">By {{ $byCategory ? 'category' : 'case' }}</h3>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm tabular-nums">
                        <caption class="sr-only">Results by {{ $byCategory ? 'category' : 'case' }}</caption>
                        <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="py-2 pr-4 font-medium">{{ $byCategory ? 'Category' : 'Case' }}</th>
                                <th scope="col" class="py-2 pr-4 text-right font-medium">Comparisons</th>
                                @if (in_array('max_abs_error', $columns, true))<th scope="col" class="py-2 pr-4 text-right font-medium">Largest abs. error</th>@endif
                                @if (in_array('max_rel_error', $columns, true))<th scope="col" class="py-2 pr-4 text-right font-medium">Largest rel. error</th>@endif
                                <th scope="col" class="py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                            @foreach ($groups as $group)
                                @php
                                    $gc = Results::statusCounts($group['cases']);
                                    $status = collect($gc)->filter()->map(fn ($n, $s) => $n === count($group['cases']) ? $s : "{$n} {$s}")->implode(', ');
                                @endphp
                                <tr>
                                    <th scope="row" class="py-2 pr-4 font-normal">{{ $group['name'] }}</th>
                                    <td class="py-2 pr-4 text-right">{{ count($group['cases']) }}</td>
                                    @if (in_array('max_abs_error', $columns, true))<td class="py-2 pr-4 text-right font-mono text-xs">{{ Results::error($group['worst_abs']) }}</td>@endif
                                    @if (in_array('max_rel_error', $columns, true))<td class="py-2 pr-4 text-right font-mono text-xs">{{ Results::error($group['worst_rel']) }}</td>@endif
                                    <td @class(['py-2', $statusClass($gc['fail'] ? 'fail' : ($gc['pass'] === count($group['cases']) ? 'pass' : 'skipped'))])>{{ $status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @php
                $detail = view('package.partials.verification-cases', compact('cases', 'columns', 'peers', 'hasValues', 'hasRule', 'labels', 'number', 'statusClass', 'byCategory') + ['ruleText' => collect($rules)->filter(fn ($r) => $r['id'] !== null)->mapWithKeys(fn ($r) => [$r['id'] => $r['text']])->all()] + ['hasImplementation' => collect($cases)->contains(fn ($c) => isset($c['implementation']))] + ['showName' => ! $byCategory && $names > 1])->render();
            @endphp
            @if ($long)
                <details class="group/all mt-6 rounded-lg ring-1 ring-zinc-200 dark:ring-white/10">
                    <summary class="cursor-pointer px-4 py-2.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">All {{ number_format(count($cases)) }} comparisons</summary>
                    <div class="border-t border-zinc-200 px-4 pb-3 dark:border-white/10">{!! $detail !!}</div>
                </details>
            @else
                @if ($grouped)
                    <h3 class="mt-8 text-base font-semibold text-zinc-950 dark:text-white">Every comparison</h3>
                @endif
                <div class="mt-3">{!! $detail !!}</div>
            @endif

            @if ($extras)
                @include('package.partials.verification-interfaces', ['record' => $record, 'mono' => $mono])
            @endif
        </section>

        <span id="known-exceptions" class="{{ $alias }}"></span>
        @if ($hasLimitations)
            <section id="limitations" class="{{ $section }}" aria-labelledby="limitations-heading">
                <h2 id="limitations-heading" class="{{ $h2 }}">Limitations</h2>
                @foreach (['exclusions' => 'Exclusions', 'known_differences' => 'Known differences'] as $key => $heading)
                    @if (! empty($record[$key]))
                        <h3 class="mt-4 text-base font-semibold text-zinc-950 dark:text-white">{{ $heading }}</h3>
                        <ul class="mt-1.5 max-w-3xl list-disc space-y-1 pl-5 text-zinc-700 dark:text-zinc-300">
                            @foreach ((array) $record[$key] as $item)
                                <li>{!! $mono($item) !!}</li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            </section>
        @endif

        <section id="reproduction" class="{{ $section }}" aria-labelledby="reproduction-heading">
            <h2 id="reproduction-heading" class="{{ $h2 }}">Reproduction</h2>
            @if ($reproduce !== [])
                <x-command class="mt-3" :code="implode(PHP_EOL, $reproduce)" label="From the repository root" :name="$package->name()" />
            @else
                <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">No reproduction command recorded.</p>
            @endif
            <dl class="{{ $dl }}">
                @foreach ([['Raw record', $raw], ['Full results', $record['source'] ?? null], ['Input data', is_string($record['data'] ?? null) ? $record['data'] : null]] as [$label, $path])
                    @if ($path)
                        @php $url = $package->sourceUrl($path); @endphp
                        <dt class="{{ $dt }}">{{ $label }}</dt>
                        <dd class="{{ $dd }}">@if ($url)<a href="{{ $url }}" class="{{ $link }}">{{ $path }}</a>@else<span class="break-all font-mono text-xs">{{ $path }}</span>@endif</dd>
                    @endif
                @endforeach
            </dl>
        </section>
    </x-sidebar-page>
</x-layout>
