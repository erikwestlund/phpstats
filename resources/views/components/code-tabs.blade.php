{{--
    Two versions of a phplyr example: the pipe operator (PHP 8.5), shown
    first, and the method chain (PHP 8.3+). The choice is shared by every
    tabset on the page and remembered between pages, like the "syntax" tab
    group in the package docs. Without a chain, the pipe version is shown
    alone. monoLabel sets the label in the monospace font, for package names.
    Slot text may be HTML-escaped; it is decoded before highlighting.
--}}
@props(['pipe', 'chain' => null, 'label' => null, 'monoLabel' => false])

@php
    $code = fn ($slot) => html_entity_decode(trim((string) $slot), ENT_QUOTES | ENT_HTML5);
    $hasChain = $chain !== null && trim((string) $chain) !== '';
@endphp
<div x-data="syntaxTabs" {{ $attributes->merge(['class' => 'code-ink overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)']) }}>
    @if ($label || $hasChain)
    <div class="flex flex-wrap items-center gap-3 border-b border-white/5 px-3 py-2 text-xs">
        @if ($label)
            <span @class(['px-1 font-medium text-php-200', 'font-mono' => $monoLabel])>{{ $label }}</span>
        @endif
        @if ($hasChain)
        <div role="tablist" aria-label="Code syntax" class="ml-auto flex gap-1 rounded-lg bg-white/5 p-0.5">
            @foreach (['pipe' => 'Pipe |>', 'chain' => 'Method chain'] as $key => $title)
                <button type="button" role="tab" x-on:click="choose('{{ $key }}')"
                        x-bind:aria-selected="(syntax === '{{ $key }}').toString()"
                        aria-selected="{{ $key === 'pipe' ? 'true' : 'false' }}"
                        title="{{ $key === 'chain' ? 'PHP 8.3 and later' : 'PHP 8.5 and later' }}"
                        class="rounded-md px-2.5 py-1 text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white">
                    {{ $title }}
                </button>
            @endforeach
        </div>
        @endif
    </div>
    @endif
    @if ($hasChain)
        <pre x-show="syntax === 'pipe'" class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($code($pipe)) !!}</code></pre>
        <pre x-show="syntax === 'chain'" x-cloak class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($code($chain)) !!}</code></pre>
    @else
        <pre class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($code($pipe)) !!}</code></pre>
    @endif
</div>
