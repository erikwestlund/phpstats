<x-layout title="Vignettes">
    <h1 class="text-3xl font-semibold tracking-tight">Vignettes</h1>

    @php $vignettes = $package->vignettes(); @endphp
    @if ($vignettes === [])
        <p class="mt-4 text-zinc-600 dark:text-zinc-400">{{ $package->name() }} has no vignettes yet.</p>
    @else
        <p class="mt-4 max-w-3xl text-zinc-700 dark:text-zinc-300">
            Each vignette runs on this server when the page loads or an input changes.
        </p>
        <ul class="mt-8 grid gap-4 sm:grid-cols-2">
            @foreach ($vignettes as $vignette)
                @php $owner = \App\Content\Package::find($vignette['owner']); @endphp
                <li>
                    <a href="{{ $owner->url('vignette', ['slug' => $vignette['slug']]) }}"
                       class="block h-full rounded-lg border border-zinc-200 p-5 hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                        <span class="font-semibold">{{ $vignette['title'] }}</span>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $vignette['summary'] }}</p>
                        <p class="mt-3 text-xs text-zinc-500">
                            Uses {{ implode(', ', $vignette['uses']) }}@if ($owner->key !== $package->key) · on the {{ $owner->name() }} site @endif
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layout>
