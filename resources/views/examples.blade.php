{{--
    The landing host's index of the curated examples, from
    App\Http\Controllers\ExampleIndexController: one list per owning package,
    each entry a title linked to its docs page and one sentence.
--}}
@php
    $sidebar = [['items' => array_map(fn (array $group) => ['id' => $group['package']->key, 'label' => $group['package']->name(), 'mono' => true], $groups)]];
@endphp
<x-layout title="Examples" description="Short, runnable examples of each PHPstats package, from reading a CSV file to fitting a mixed model.">
    <x-sidebar-page title="Examples" :sidebar="$sidebar">
        @foreach ($groups as $group)
            @php $package = $group['package']; @endphp
            <section id="{{ $package->key }}" @class(['mt-10', 'border-t border-zinc-200 pt-10 dark:border-white/10' => ! $loop->first])>
                <h2 class="font-mono text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $package->name() }}</h2>
                <x-example-list class="mt-4" :examples="$group['examples']" />
            </section>
        @endforeach
    </x-sidebar-page>
</x-layout>
