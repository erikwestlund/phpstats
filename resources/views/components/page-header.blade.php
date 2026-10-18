{{-- The heading block of a package section page: eyebrow, title, and lead. --}}
@props(['eyebrow' => null, 'title'])

<div {{ $attributes->class('max-w-3xl') }}>
    @if ($eyebrow)
        <p class="text-sm font-semibold text-(--l600) dark:text-(--l300)">{{ $eyebrow }}</p>
    @endif
    <h1 class="mt-2 text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $title }}</h1>
    @if ($slot->isNotEmpty())
        <div class="mt-4 text-lg leading-8 text-zinc-600 dark:text-zinc-400">{{ $slot }}</div>
    @endif
</div>
