{{--
    A package's Examples tab: its curated examples and those of other packages
    that list it, each linked to its docs page, then the live pages this
    package runs on the site.
--}}
@php
    $examples = $package->listedExamples();
    $live = array_values(array_filter($package->vignettes(), fn (array $vignette) => $vignette['owner'] === $package->key));
@endphp
<x-layout title="Examples">
    <div class="mx-auto max-w-3xl py-6">
        <h1 class="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">Examples</h1>
        @if ($examples === [] && $live === [])
            <p class="mt-8 text-zinc-600 dark:text-zinc-400"><span class="font-mono">{{ $package->name() }}</span> has no examples yet.</p>
        @endif
        <x-example-list class="mt-8" :examples="$examples" :package="$package" />
        @if ($live !== [])
            <h2 class="mt-12 border-t border-zinc-200 pt-10 text-xl font-semibold tracking-tight text-zinc-950 dark:border-white/10 dark:text-white">Interactive</h2>
            <ul class="mt-4 space-y-4">
                @foreach ($live as $vignette)
                    <li>
                        <a href="{{ $package->url('vignette', ['slug' => $vignette['slug']]) }}" class="text-[0.9375rem] font-semibold text-(--l700) hover:text-(--l600) dark:text-(--l300)">{{ $vignette['title'] }}</a>
                        <p class="mt-0.5 text-[0.9375rem] leading-7 text-zinc-600 dark:text-zinc-400">{{ $vignette['summary'] }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layout>
