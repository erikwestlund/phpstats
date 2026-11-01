{{--
    A package's Benchmarks section: a table of its saved benchmark records,
    one row per workload, each linking its own page. A row's id is the
    record's id, and an old /benchmarks#{id} link goes on to that page. A
    package with no records says so in one line, with the gap site.json's
    evidence_gaps names.
--}}
@use('App\Content\Results')
@php
    $records = $package->benchmarks();
    $gap = $package->evidenceGap('benchmarks');
    $topics = collect($records)->mapWithKeys(fn (array $r) => [$r['id'] => $package->url('benchmark', ['id' => $r['id']])])->all();
    // Renamed or merged records' old ids go to their replacement too.
    $moved = array_map(fn (string $id) => $package->url('benchmark', ['id' => $id]), $package->renamedTopics('benchmarks'));
@endphp
<x-layout title="Benchmarks" :wide="$records !== []">
    @if ($records === [])
        <article class="mx-auto max-w-3xl py-6">
            <p class="font-mono text-sm font-semibold text-(--l600) dark:text-(--l300)">{{ $package->name() }}</p>
            <h1 class="mt-2 text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">Benchmarks</h1>
            <p class="mt-6 text-zinc-700 dark:text-zinc-300">No benchmark measurements are published for <span class="font-mono">{{ $package->name() }}</span> yet.</p>
            @if ($gap)
                <p class="mt-3 text-zinc-700 dark:text-zinc-300">{{ $gap }}</p>
            @endif
        </article>
    @else
        <x-sidebar-page title="Benchmarks" :eyebrow="$package->name()" :sidebar="Results::sidebar($package, 'benchmarks', null)" :wide="true" menu="Benchmarks">
            <p class="mt-4 max-w-3xl text-zinc-700 dark:text-zinc-300">Saved measurements from the <span class="font-mono">{{ $package->name() }}</span> repository, one page per workload. Each page gives the machine, the versions, how time and memory were measured, and the code each implementation ran.</p>

            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="py-2 pr-4 font-medium">Workload</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Implementations</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Measured</th>
                            <th scope="col" class="py-2 font-medium">Recorded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                        @foreach ($records as $record)
                            @php
                                $implementations = array_values(array_unique(array_column($record['rows'] ?? [], 'implementation')));
                                $measured = [Str::ucfirst($record['measure'] ?? 'time'), ...array_column(Results::memoryMetrics($record), 'label')];
                                $date = isset($record['environment']['date']) ? Str::before($record['environment']['date'], 'T') : null;
                            @endphp
                            <tr id="{{ $record['id'] }}" class="scroll-mt-28 align-top lg:scroll-mt-20">
                                <th scope="row" class="py-2.5 pr-4 font-normal">
                                    <a href="{{ $topics[$record['id']] }}" class="font-medium text-php-700 hover:text-php-600 dark:text-php-300 dark:hover:text-php-200">{{ Results::label($record) }}</a>
                                    @if (! empty($record['workload']))
                                        <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">{{ $record['workload'] }}</span>
                                    @endif
                                </th>
                                <td class="py-2.5 pr-4 text-zinc-700 dark:text-zinc-300">{!! \App\Content\Libraries::mono(e(implode(', ', $implementations))) !!}</td>
                                <td class="py-2.5 pr-4 text-zinc-700 dark:text-zinc-300">{{ implode('; ', $measured) }}</td>
                                <td class="whitespace-nowrap py-2.5 tabular-nums text-zinc-700 dark:text-zinc-300">{{ $date ?? 'not stated' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($gap)
                <h2 id="not-measured" class="mt-12 scroll-mt-28 text-xl font-semibold tracking-tight lg:scroll-mt-20">Not measured yet</h2>
                <p class="mt-3 max-w-3xl text-zinc-700 dark:text-zinc-300">{{ $gap }}</p>
            @endif
        </x-sidebar-page>

        <script>
            // Links from before each workload had its own page end in #{id}.
            (function () {
                var topics = @js([...$moved, ...$topics]);
                var id = decodeURIComponent(location.hash.slice(1));
                if (Object.prototype.hasOwnProperty.call(topics, id)) location.replace(topics[id]);
            })();
        </script>
    @endif
</x-layout>
