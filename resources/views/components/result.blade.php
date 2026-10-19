{{-- The output panel under an example's code. --}}
<div {{ $attributes->merge(['class' => 'not-prose overflow-hidden rounded-xl bg-white ring-1 ring-zinc-200 dark:bg-white/[0.03] dark:ring-white/10']) }}>
    <p class="border-b border-zinc-100 px-4 py-1.5 text-[0.7rem] font-semibold uppercase tracking-wider text-zinc-400 dark:border-white/5">Result</p>
    <div class="p-4">{{ $slot }}</div>
</div>
