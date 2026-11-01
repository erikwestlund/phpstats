{{--
    The landing page's benchmarks, one workload at a time: each workload in
    config/phpstats.php benchmarks names a package's synced benchmark record,
    which holds the saved results, each implementation's code, and the
    environment. A selector chooses the workload; within it, two phiz bar
    charts, elapsed time and memory, sit side by side with equal weight (with
    the same numbers in a table for screen readers), then the code of each
    implementation in tabs, then a short summary of how it was measured and a
    link to the full record on the package's Benchmarks page. The page never
    runs a benchmark, and a workload whose record has not synced is left out.
--}}
@php
    $workloads = collect(config('phpstats.benchmarks'))->map(function (array $workload) {
        $package = \App\Content\Package::find($workload['package']);
        $record = $package ? collect($package->benchmarks())->firstWhere('id', $workload['id']) : null;

        return $record ? [...$workload, 'record' => $record, 'url' => $package->benchmarkUrl($workload['id']), 'name' => $package->name()] : null;
    })->filter()->values();
    $time = fn (float $ms) => $ms < 1000 ? number_format($ms, $ms < 10 ? 1 : 0).' ms' : number_format($ms / 1000, 1).' s';
    $memory = fn (float $mib) => $mib >= 1024 ? number_format($mib / 1024, 1).' GiB' : number_format($mib).' MiB';
    $dedent = \App\Content\Code::dedent(...);
@endphp
@if ($workloads->isNotEmpty())
<section id="performance" aria-labelledby="performance-heading" class="scroll-mt-16 border-t border-zinc-200 py-16 sm:py-20 dark:border-white/10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h2 id="performance-heading" class="text-center text-3xl font-semibold tracking-tight sm:text-4xl">Performant, powerful, and flexible</h2>

        <div x-data="{ workload: @js($workloads[0]['id']) }" class="mt-10 sm:mt-12">
            <div role="tablist" aria-label="Workload" class="mx-auto flex w-fit max-w-full flex-wrap justify-center gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-white/5">
                @foreach ($workloads as $w)
                    <button type="button" role="tab" id="workload-tab-{{ $w['id'] }}" aria-controls="workload-{{ $w['id'] }}"
                            x-on:click="workload = '{{ $w['id'] }}'" x-bind:aria-selected="(workload === '{{ $w['id'] }}').toString()" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-zinc-600 transition hover:text-zinc-900 aria-selected:bg-white aria-selected:text-zinc-900 aria-selected:shadow-sm dark:text-zinc-400 dark:hover:text-white dark:aria-selected:bg-white/10 dark:aria-selected:text-white">
                        {{ $w['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach ($workloads as $w)
                @php
                    $record = $w['record'];
                    $rows = $record['rows'];
                    $env = $record['environment'];
                    $code = $record['code'] ?? [];
                    $languages = $record['languages'] ?? [];
                @endphp
                <div role="tabpanel" id="workload-{{ $w['id'] }}" aria-labelledby="workload-tab-{{ $w['id'] }}" x-show="workload === '{{ $w['id'] }}'" @unless ($loop->first) x-cloak @endunless class="mt-12">
                    @php
                        $data = $record['dataset'] ?? null;
                        $caption = $record['workload'] ?? ($data ? number_format($data['rows']).' rows, '.number_format($data['bytes'] / 1e9, 2).' GB. '.($w['task'] ?? '') : $record['title']);
                    @endphp

                    @php
                        // A record may measure the workload more than one way, as cases
                        // under subtabs; its code is then keyed by case as well.
                        $cases = collect($rows)->groupBy('case')->map(fn ($r) => $r->values()->all())->all();
                        $nested = $code !== [] && is_array(reset($code));
                    @endphp
                    @if (count($cases) > 1)
                        @php $firstCase = array_key_first($cases); @endphp
                        <div x-data="{ c: @js($firstCase) }">
                            <div role="tablist" aria-label="{{ $w['label'] }} cases" class="mx-auto mb-8 flex w-fit gap-1 rounded-lg border border-zinc-200 p-0.5 text-sm dark:border-white/10">
                                @foreach (array_keys($cases) as $case)
                                    @php $cid = $w['id'].'-'.\Illuminate\Support\Str::slug($case); @endphp
                                    <button type="button" role="tab" id="case-tab-{{ $cid }}" aria-controls="case-{{ $cid }}"
                                            x-on:click="c = @js($case)" x-bind:aria-selected="(c === @js($case)).toString()" aria-selected="{{ $case === $firstCase ? 'true' : 'false' }}"
                                            class="rounded-md px-3 py-1 font-medium text-zinc-600 transition hover:text-zinc-900 aria-selected:bg-zinc-100 aria-selected:text-zinc-900 dark:text-zinc-400 dark:hover:text-white dark:aria-selected:bg-white/10 dark:aria-selected:text-white">{{ $case }}</button>
                                @endforeach
                            </div>
                            @foreach ($cases as $case => $caseRows)
                                @php $cid = $w['id'].'-'.\Illuminate\Support\Str::slug($case); @endphp
                                <div role="tabpanel" id="case-{{ $cid }}" aria-labelledby="case-tab-{{ $cid }}" x-show="c === @js($case)" @unless ($case === $firstCase) x-cloak @endunless>
                                    <x-landing.benchmark-case :w="$w" :record="$record" :rows="$caseRows" :code="$nested ? ($code[$case] ?? []) : $code" :id="$cid" :time="$time" :memory="$memory" :dedent="$dedent" :title="trim($caption)" />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-landing.benchmark-case :w="$w" :record="$record" :rows="$rows" :code="$nested ? reset($code) : $code" :id="$w['id']" :time="$time" :memory="$memory" :dedent="$dedent" :title="trim($caption)" />
                    @endif



                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
