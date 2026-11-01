{{--
    A package's Verification section: a table of its saved verification
    records, one row per topic with its reference and how many comparisons
    passed, failed, or were skipped, each linking its own page. A row's id is
    the record's id, and an old /verification#{id} link goes on to that page.
    A package with no records says so in one line, with the gap site.json's
    evidence_gaps names.
--}}
@use('App\Content\Results')
@php
    $records = $package->verifications();
    $gap = $package->evidenceGap('verification');
    $topics = collect($records)->mapWithKeys(fn (array $r) => [$r['id'] => $package->url('verification.topic', ['id' => $r['id']])])->all();
    // Renamed or merged records' old ids go to their replacement too.
    $moved = array_map(fn (string $id) => $package->url('verification.topic', ['id' => $id]), $package->renamedTopics('verification'));
    $counts = collect($records)->mapWithKeys(fn (array $r) => [$r['id'] => Results::statusCounts($r['cases'] ?? [])])->all();
    $skipped = collect($counts)->sum('skipped') > 0;
    $unreported = collect($counts)->sum('not reported') > 0;
@endphp
<x-layout title="Verification" :wide="$records !== []">
    @if ($records === [])
        <article class="mx-auto max-w-3xl py-6">
            <h1 class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">Verification</h1>
            <p class="mt-6 text-zinc-700 dark:text-zinc-300">No verification checks are published yet.</p>
            @if ($gap)
                <p class="mt-3 text-zinc-700 dark:text-zinc-300">{{ $gap }}</p>
            @endif
        </article>
    @else
        <x-sidebar-page title="Verification" :sidebar="Results::sidebar($package, 'verification', null)" :wide="true" menu="Verification">

            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="border-b border-zinc-200 text-zinc-600 dark:border-white/10 dark:text-zinc-400">
                        <tr>
                            <th scope="col" class="py-2 pr-4 font-medium">Topic</th>
                            <th scope="col" class="py-2 pr-4 font-medium">Reference</th>
                            <th scope="col" class="py-2 pr-4 text-right font-medium">Comparisons</th>
                            <th scope="col" class="py-2 pr-4 text-right font-medium">Pass</th>
                            <th scope="col" @class(['py-2 text-right font-medium', 'pr-4' => $skipped || $unreported])>Fail</th>
                            @if ($skipped)
                                <th scope="col" @class(['py-2 text-right font-medium', 'pr-4' => $unreported])>Skipped</th>
                            @endif
                            @if ($unreported)
                                <th scope="col" class="py-2 text-right font-medium">Not reported</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 tabular-nums dark:divide-white/5">
                        @foreach ($records as $record)
                            @php $c = $counts[$record['id']]; @endphp
                            <tr id="{{ $record['id'] }}" class="scroll-mt-28 align-top lg:scroll-mt-20">
                                <th scope="row" class="py-2.5 pr-4 font-normal">
                                    <a href="{{ $topics[$record['id']] }}" class="font-medium text-php-700 hover:text-php-600 dark:text-php-300 dark:hover:text-php-200">{{ Results::label($record) }}</a>
                                </th>
                                <td class="py-2.5 pr-4 text-zinc-700 dark:text-zinc-300">{!! \App\Content\Libraries::mono(e($record['reference'] ?? 'not stated')) !!}</td>
                                <td class="py-2.5 pr-4 text-right">{{ number_format(count($record['cases'] ?? [])) }}</td>
                                <td class="py-2.5 pr-4 text-right">{{ number_format($c['pass']) }}</td>
                                <td @class(['py-2.5 text-right', 'pr-4' => $skipped || $unreported, 'font-semibold text-rose-700 dark:text-rose-400' => $c['fail'] > 0])>{{ number_format($c['fail']) }}</td>
                                @if ($skipped)
                                    <td @class(['py-2.5 text-right', 'pr-4' => $unreported])>{{ number_format($c['skipped']) }}</td>
                                @endif
                                @if ($unreported)
                                    <td class="py-2.5 text-right">{{ number_format($c['not reported']) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($gap)
                <h2 id="not-checked" class="mt-10 scroll-mt-28 text-xl font-semibold tracking-tight lg:scroll-mt-20">Not checked</h2>
                <p class="mt-3 max-w-3xl text-zinc-700 dark:text-zinc-300">{{ $gap }}</p>
            @endif
        </x-sidebar-page>

        <script>
            // Links from before each topic had its own page end in #{id}.
            (function () {
                var topics = @js([...$moved, ...$topics]);
                var id = decodeURIComponent(location.hash.slice(1));
                if (Object.prototype.hasOwnProperty.call(topics, id)) location.replace(topics[id]);
            })();
        </script>
    @endif
</x-layout>
