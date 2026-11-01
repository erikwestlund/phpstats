{{--
    A package's Benchmarks page: every saved workload record, in site.json's
    order and under its group, one after another, each with its Results,
    Methods, and Reproduction tabs (package.partials.benchmark-workload). The
    sidebar lists them as anchors. A record that was renamed or merged, and
    an old /benchmarks/{id} link, lead to the anchor of the record that
    replaced it. A package with no records says so in one line; the gap
    site.json's evidence_gaps names ends the page.
--}}
@use('App\Content\Results')
@php
    $records = $package->benchmarks();
    $gap = $package->evidenceGap('benchmarks');
    $sidebar = Results::sidebar($package, 'benchmarks', null);
    if ($gap && $records !== []) {
        $sidebar[] = ['title' => null, 'items' => [['id' => 'not-measured', 'label' => 'Not measured']]];
    }
    $groups = collect($records)->groupBy(fn (array $r) => is_string($r['group'] ?? null) ? $r['group'] : '', preserveKeys: false);
@endphp
<x-layout title="Benchmarks" :wide="$records !== []">
    @if ($records === [])
        <article class="mx-auto max-w-3xl py-6">
            <h1 class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">Benchmarks</h1>
            <p class="mt-6 text-zinc-700 dark:text-zinc-300">No benchmark measurements are published yet.</p>
            @if ($gap)
                <p class="mt-3 text-zinc-700 dark:text-zinc-300">{{ $gap }}</p>
            @endif
        </article>
    @else
        <x-sidebar-page title="Benchmarks" :sidebar="$sidebar" :wide="true" menu="Workloads">
            @foreach ($groups as $group => $members)
                @if ($group !== '')
                    <p @class(['text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400', 'mt-24' => ! $loop->first, 'mt-8' => $loop->first])>{{ $group }}</p>
                @endif
                @foreach ($members as $record)
                    {{-- Workloads are set well apart, more than the parts within one. --}}
                    <div @class([
                        'border-t border-zinc-200 pt-16 sm:pt-20 dark:border-white/10' => ! ($loop->first && $loop->parent->first),
                        'mt-20 sm:mt-24' => ! $loop->first,
                        'mt-4' => $loop->first && $group !== '',
                        'mt-8' => $loop->first && $group === '',
                    ])>
                        @include('package.partials.benchmark-workload', ['record' => $record])
                    </div>
                @endforeach
            @endforeach

            @if ($gap)
                <div class="mt-20 border-t border-zinc-200 pt-16 sm:mt-24 sm:pt-20 dark:border-white/10">
                    <h2 id="not-measured" class="scroll-mt-10 text-xl font-semibold tracking-tight lg:scroll-mt-0">Not measured</h2>
                    <p class="mt-3 max-w-3xl text-zinc-700 dark:text-zinc-300">{{ $gap }}</p>
                </div>
            @endif
        </x-sidebar-page>

        <script>
            // A renamed or merged record's old #id goes to the record that replaced it.
            (function () {
                var moved = @js($package->renamedTopics('benchmarks'));
                var id = decodeURIComponent(location.hash.slice(1));
                if (Object.prototype.hasOwnProperty.call(moved, id)) location.replace('#' + moved[id]);
            })();
        </script>
    @endif
</x-layout>
