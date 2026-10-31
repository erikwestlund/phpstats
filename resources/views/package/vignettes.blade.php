<x-layout title="Examples" :wide="true">
    <x-page-header :eyebrow="$package->name()" title="Examples">
        Each example runs on this server when the page loads and again when an input changes.
    </x-page-header>

    @php $vignettes = $package->vignettes(); @endphp
    @if ($vignettes === [])
        <p class="mt-8 text-zinc-600 dark:text-zinc-400">{{ $package->name() }} has no examples yet.</p>
    @else
        <ul class="mt-10 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($vignettes as $vignette)
                @php $owner = \App\Content\Package::find($vignette['owner']); @endphp
                <li class="group relative flex flex-col rounded-2xl bg-white p-6 ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-(--l500)/10 hover:ring-(--l300) dark:bg-white/[0.03] dark:ring-white/10 dark:hover:ring-(--l400)/40">
                    <span class="flex items-center gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                        <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                        Live
                        @if ($owner->key !== $package->key)
                            <span class="text-zinc-500">· on the {{ $owner->name() }} site</span>
                        @endif
                    </span>
                    <a href="{{ $owner->url('vignette', ['slug' => $vignette['slug']]) }}" class="mt-3 text-lg font-semibold tracking-tight text-zinc-950 after:absolute after:inset-0 group-hover:text-(--l700) dark:text-white dark:group-hover:text-(--l300)">{{ $vignette['title'] }}</a>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $vignette['summary'] }}</p>
                    <div class="relative z-10 mt-auto flex flex-wrap gap-1.5 pt-5">
                        @foreach ($vignette['uses'] as $use)
                            <x-package-chip :package="$use === 'php_polars' ? 'phpolars' : $use" />
                        @endforeach
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layout>
