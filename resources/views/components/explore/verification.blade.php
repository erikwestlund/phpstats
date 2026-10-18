{{-- Pass counts for each of a package's verification reports. --}}
@props(['package'])

@php $package = \App\Content\Package::find($package); @endphp
<div class="not-prose space-y-2.5">
    @foreach ($package->verifications() as $verification)
        @php
            $total = count($verification['cases']);
            $passed = count(array_filter($verification['cases'], fn ($case) => $case['passed'] ?? false));
        @endphp
        <a href="{{ $package->url('verification') }}#{{ $verification['id'] }}" class="group grid grid-cols-[minmax(0,17rem)_1fr_auto] items-center gap-3 text-sm">
            <span class="truncate text-zinc-700 group-hover:text-php-700 dark:text-zinc-300 dark:group-hover:text-php-300">{{ $verification['title'] }}</span>
            <span class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/5">
                <span class="block h-full rounded-full bg-gradient-to-r from-php-400 to-violet-500" style="width: {{ $total ? round(100 * $passed / $total) : 0 }}%"></span>
            </span>
            <span class="font-mono text-xs tabular-nums text-zinc-500">{{ $passed }}/{{ $total }}</span>
        </a>
    @endforeach
</div>
