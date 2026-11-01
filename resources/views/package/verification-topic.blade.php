{{--
    One verification topic, from its saved record: what was checked; the
    reference, its versions, and the inputs; the comparison rule in words and
    numbers, with the error definitions; every result, with its status as
    plain text (a summary by case first when the cases have several
    quantities each, and the full table in one disclosure when it is long);
    failures, skipped cases, exclusions, and known differences; and how to
    reproduce it, with links to the raw record and the full results.
--}}
@use('App\Content\Results')
@php
    $cases = $record['cases'] ?? [];
    $counts = Results::statusCounts($cases);
    // Cases with a category are summarized by category; otherwise by name,
    // when several rows share one.
    $byCategory = collect($cases)->contains(fn ($c) => isset($c['category']));
    $groupKey = $byCategory ? 'category' : 'name';
    $groups = Results::groups(['cases' => $cases], $groupKey);
    $names = count(array_unique(array_map(fn ($c) => $c['name'] ?? '', $cases)));
    $grouped = count($groups) > 1 && count($groups) < count($cases);
    ['columns' => $columns, 'peers' => $peers] = Results::verificationColumns(['cases' => $cases]);
    $hasValues = collect($cases)->contains(fn ($c) => array_key_exists('actual', $c) || array_key_exists('expected', $c));
    $hasRule = collect($cases)->contains(fn ($c) => isset($c['rule']));
    $rules = Results::rules($record);
    $floors = collect($record['comparison'] ?? [])->filter(fn ($r) => is_array($r) && isset($r['rel_floor']))->pluck('rel_floor')->unique()->values();
    $problems = array_values(array_filter($cases, fn ($c) => Results::status($c) !== 'pass'));
    $kinds = [
        'reference' => 'A comparison with a reference implementation on the same inputs.',
        'contract' => "A check of the package's own expected values and invariants.",
        'integration' => 'An end-to-end run of the package with the external program it drives.',
    ];
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
    $children = array_values(array_filter([
        ['id' => 'compared', 'label' => 'What was checked'],
        ['id' => 'reference-and-versions', 'label' => 'Reference and inputs'],
        ['id' => 'metric-and-tolerance', 'label' => 'Comparison rule'],
        ['id' => 'results', 'label' => 'Results'],
        ['id' => 'known-exceptions', 'label' => 'Failures and differences'],
        ['id' => 'reproduction', 'label' => 'Reproduction'],
    ]));
    $section = 'mt-12 scroll-mt-28 lg:scroll-mt-20';
    $h2 = 'text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white';
    $dl = 'mt-4 grid max-w-4xl gap-x-6 gap-y-2 text-sm sm:grid-cols-[10rem_minmax(0,1fr)]';
    $dt = 'font-medium text-zinc-500 dark:text-zinc-400';
    $dd = 'mb-2 text-zinc-800 sm:mb-0 dark:text-zinc-200';
    $link = 'break-all font-mono text-xs underline decoration-zinc-300 underline-offset-2 hover:text-zinc-950 dark:decoration-zinc-600 dark:hover:text-white';
    $mono = fn (?string $text) => \App\Content\Libraries::mono(e((string) $text));
@endphp
<x-layout :title="Results::label($record).' verification'" :wide="true">
    <x-sidebar-page :title="$record['title']" :eyebrow="$package->name().' verification'" :sidebar="Results::sidebar($package, 'verification', $record['id'], $children)" :wide="true" menu="Verification">
        @if (! empty($record['checked']) && ! empty($record['description']))
            <p class="mt-4 max-w-3xl leading-relaxed text-zinc-700 dark:text-zinc-300">{!! $mono($record['description']) !!}</p>
        @endif

        <section id="compared" class="{{ $section }}" aria-labelledby="compared-heading">
            <h2 id="compared-heading" class="{{ $h2 }}">What was checked</h2>
            <p class="mt-3 max-w-3xl leading-relaxed text-zinc-700 dark:text-zinc-300">{!! $mono($record['checked'] ?? $record['description'] ?? 'The record does not describe what was checked.') !!}</p>
            @if (isset($kinds[$record['kind'] ?? '']))
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $kinds[$record['kind']] }}</p>
            @endif
        </section>

        <section id="reference-and-versions" class="{{ $section }}" aria-labelledby="reference-heading">
            <h2 id="reference-heading" class="{{ $h2 }}">Reference and inputs</h2>
            <dl class="{{ $dl }}">
                <dt class="{{ $dt }}">Reference</dt>
                <dd class="{{ $dd }}">{!! $mono($record['reference'] ?? 'Not stated.') !!}</dd>
                @if ($versions !== [])
                    <dt class="{{ $dt }}">Versions</dt>
                    <dd class="{{ $dd }}">{!! $mono(implode(', ', array_map(fn ($k, $v) => "{$k} ".preg_replace('/^.*?\bversion\s+/', '', (string) $v), array_keys($versions), $versions))) !!}</dd>
                @endif
                <dt class="{{ $dt }}">Inputs</dt>
                <dd class="{{ $dd }}">
                    @if (is_string($inputs))
                        {!! $mono($inputs) !!}
                    @elseif ($inputs === null && is_array($record['data'] ?? null))
                        {{-- The input values themselves, as the Stan data of an interface comparison. --}}
                        <code class="break-all font-mono text-xs">{{ json_encode($record['data'], JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) }}</code>
                    @elseif (is_array($inputs) && $inputs !== [])
                        <ul class="space-y-1">
                            @foreach ($inputs as $input)
                                @php $url = $package->sourceUrl($input['url'] ?? null); @endphp
                                <li><span class="font-mono">{{ $input['name'] ?? '' }}</span>@if (! empty($input['detail'])): {{ $input['detail'] }}@endif @if ($url)(<a href="{{ $url }}" class="underline decoration-zinc-300 underline-offset-2 dark:decoration-zinc-600">data</a>)@endif</li>
                            @endforeach
                        </ul>
                    @else
                        {{ number_format($names) }} {{ Str::plural('case', $names) }}, listed under Results. The record does not describe the input data further.
                    @endif
                </dd>
                @if (is_array($record['arguments'] ?? null))
                    <dt class="{{ $dt }}">Arguments</dt>
                    <dd class="{{ $dd }}">{{ implode('; ', array_map(fn ($k, $v) => str_replace('_', ' ', $k).' '.(is_array($v) ? implode(', ', $v) : $v), array_keys($record['arguments']), $record['arguments'])) }}</dd>
                @endif
                @if ($recorded !== '')
                    <dt class="{{ $dt }}">Recorded</dt>
                    <dd class="{{ $dd }}">{{ $recorded }}</dd>
                @endif
            </dl>
            @if (is_string($record['model'] ?? null))
                <x-code class="mt-6" :code="$record['model']" lang="txt" label="Model" />
            @endif
        </section>

        <section id="metric-and-tolerance" class="{{ $section }}" aria-labelledby="rule-heading">
            <h2 id="rule-heading" class="{{ $h2 }}">Comparison rule</h2>
            @if ($rules === [])
                <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">The record does not state a comparison rule.</p>
            @else
                <ul class="mt-3 max-w-3xl space-y-2 text-zinc-700 dark:text-zinc-300">
                    @foreach ($rules as $rule)
                        <li>@if ($rule['applies_to'])<span class="font-medium text-zinc-900 dark:text-zinc-100">{{ Str::ucfirst($rule['applies_to']) }}{{ $hasRule && $rule['id'] ? ' ('.$rule['id'].')' : '' }}.</span> @endif{!! $mono($rule['text']) !!}</li>
                    @endforeach
                </ul>
            @endif
            <ul class="mt-4 max-w-3xl space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                @if (in_array('max_abs_error', $columns, true))
                    <li>Absolute error is |x − r|, with x the package's value and r the reference's. Max abs. error is the largest over the values a row compares.</li>
                @endif
                @if (in_array('max_rel_error', $columns, true))
                    <li>Relative error is |x − r| divided by
                        @if ($floors->isNotEmpty())
                            max(|r|, {{ $floors->map(fn ($f) => Results::error($f))->implode(' or ') }}), so a zero or near-zero reference does not divide by zero.
                        @else
                            |r|. The record does not state the denominator used when r is zero or near zero.
                        @endif
                    </li>
                @endif
                @if ($peers !== [])
                    <li>The {!! $mono(implode(', ', $peers)) !!} {{ count($peers) === 1 ? 'column gives that implementation’s' : 'columns give those implementations’' }} error against the same reference, for comparison.</li>
                @endif
                @if (! empty($record['note']))
                    <li>{!! $mono($record['note']) !!}</li>
                @endif
            </ul>
        </section>

        <section id="results" class="{{ $section }}" aria-labelledby="results-heading">
            <h2 id="results-heading" class="{{ $h2 }}">Results</h2>
            <p class="mt-3 text-zinc-700 dark:text-zinc-300">
                {{ number_format(count($cases)) }} {{ Str::plural('comparison', count($cases)) }}@if ($grouped) in {{ number_format(count($groups)) }} {{ Str::plural($byCategory ? 'category' : 'case', count($groups)) }}@endif:
                {{ number_format($counts['pass']) }} pass, {{ number_format($counts['fail']) }} fail, {{ number_format($counts['skipped']) }} skipped{{ $counts['not reported'] ? ', '.number_format($counts['not reported']).' not reported' : '' }}.
            </p>

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
                $detail = view('package.partials.verification-cases', compact('cases', 'columns', 'peers', 'hasValues', 'hasRule', 'labels', 'number', 'statusClass', 'byCategory') + ['showName' => ! $byCategory && $names > 1])->render();
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

        <section id="known-exceptions" class="{{ $section }}" aria-labelledby="exceptions-heading">
            <h2 id="exceptions-heading" class="{{ $h2 }}">Failures, exclusions, and known differences</h2>
            @if ($problems === [])
                <p class="mt-3 text-zinc-700 dark:text-zinc-300">No comparison failed, and none was skipped.</p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Comparisons that did not pass</caption>
                        <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                            <tr><th scope="col" class="py-2 pr-4 font-medium">Case</th><th scope="col" class="py-2 pr-4 font-medium">Quantity</th><th scope="col" class="py-2 pr-4 font-medium">Status</th><th scope="col" class="py-2 font-medium">Reason</th></tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                            @foreach ($problems as $case)
                                <tr>
                                    <td class="py-2 pr-4">{{ $case['name'] ?? '' }}</td>
                                    <td class="py-2 pr-4">{{ $case['quantity'] ?? '' }}</td>
                                    <td @class(['py-2 pr-4', $statusClass(Results::status($case))])>{{ Results::status($case) }}</td>
                                    <td class="py-2">{{ $case['reason'] ?? 'The record gives no reason.' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @foreach (['exclusions' => 'Exclusions', 'known_differences' => 'Known differences'] as $key => $heading)
                @if (! empty($record[$key]))
                    <h3 class="mt-6 text-base font-semibold text-zinc-950 dark:text-white">{{ $heading }}</h3>
                    <ul class="mt-2 max-w-3xl list-disc space-y-1 pl-5 text-zinc-700 dark:text-zinc-300">
                        @foreach ((array) $record[$key] as $item)
                            <li>{!! $mono($item) !!}</li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
            @if (empty($record['exclusions']) && empty($record['known_differences']))
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">The record lists no exclusions or known differences.</p>
            @endif
        </section>

        <section id="reproduction" class="{{ $section }}" aria-labelledby="reproduction-heading">
            <h2 id="reproduction-heading" class="{{ $h2 }}">Reproduction</h2>
            @if ($reproduce !== [])
                <x-command class="mt-4" :code="implode(PHP_EOL, $reproduce)" label="Reproduce" :name="$package->name()" />
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Run from the root of the <span class="font-mono">{{ $package->name() }}</span> repository.</p>
            @else
                <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">The record gives no reproduction command.</p>
            @endif
            <ul class="mt-4 space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                @foreach ([['Raw record', $raw], ['Full results', $record['source'] ?? null], ['Input data', is_string($record['data'] ?? null) ? $record['data'] : null]] as [$label, $path])
                    @if ($path)
                        @php $url = $package->sourceUrl($path); @endphp
                        <li>{{ $label }}: @if ($url)<a href="{{ $url }}" class="{{ $link }}">{{ $path }}</a>@else<span class="break-all font-mono text-xs">{{ $path }}</span>@endif</li>
                    @endif
                @endforeach
            </ul>
        </section>
    </x-sidebar-page>
</x-layout>
