{{--
    A benchmark's code, one tab per implementation, on the dark ink
    background, as the landing page's performance section and the benchmark
    pages show it. code is implementation => source, languages gives each
    one's highlight language (php by default), names gives display names, and
    id keeps the tab ids unique on the page. The tab row's Copy button copies
    the shown tab's source. Lines wrap with a hanging indent, so the code
    never scrolls sideways.
--}}
@props(['code', 'id', 'label', 'languages' => [], 'names' => []])
@php
    $sources = array_map(fn (string $source) => \App\Content\Code::dedent($source), $code);
    $first = array_key_first($sources);
@endphp
<div x-data="{ impl: @js($first) }" {{ $attributes->merge(['class' => 'code-ink min-w-0 overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)']) }}>
    <div class="flex items-start gap-2 border-b border-white/5 px-3 py-2 text-xs">
    <div role="tablist" aria-label="{{ $label }} code" class="flex min-w-0 flex-1 flex-wrap gap-1">
        @foreach ($sources as $impl => $source)
            @php $slug = $id.'-'.\Illuminate\Support\Str::slug($impl); @endphp
            <button type="button" role="tab" id="code-tab-{{ $slug }}" aria-controls="code-{{ $slug }}"
                    x-on:click="impl = @js($impl)" x-bind:aria-selected="(impl === @js($impl)).toString()" aria-selected="{{ $impl === $first ? 'true' : 'false' }}"
                    class="rounded-md px-2.5 py-1 text-zinc-400 transition hover:text-white aria-selected:bg-php-500/30 aria-selected:text-white">{!! \App\Content\Libraries::mono(e($names[$impl] ?? $impl)) !!}</button>
        @endforeach
    </div>
        @foreach ($sources as $impl => $source)
            <div x-data="copyCommand(@js($source))" x-show="impl === @js($impl)" @unless ($impl === $first) x-cloak @endunless class="shrink-0">
                <button type="button" x-on:click="copy" aria-label="Copy the {{ $names[$impl] ?? $impl }} code"
                        class="flex items-center gap-1 rounded-md px-2 py-1 text-zinc-300 transition hover:bg-white/10 hover:text-white">
                    <x-heroicon-m-clipboard-document x-show="! copied" class="size-3.5" />
                    <x-heroicon-m-check x-show="copied" x-cloak class="size-3.5" />
                    <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                </button>
                <span class="sr-only" aria-live="polite" x-text="copied ? 'Copied' : ''"></span>
            </div>
        @endforeach
    </div>
    @foreach ($sources as $impl => $source)
        @php $slug = $id.'-'.\Illuminate\Support\Str::slug($impl); @endphp
        <div role="tabpanel" id="code-{{ $slug }}" aria-labelledby="code-tab-{{ $slug }}" x-show="impl === @js($impl)" @unless ($impl === $first) x-cloak @endunless>
            <pre class="code-wrap p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::hanging(\App\Content\Code::highlight($source, $languages[$impl] ?? 'php')) !!}</code></pre>
        </div>
    @endforeach
</div>
