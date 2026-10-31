{{--
    A shell command block with a label above it and a Copy button beside it.
    The button copies $code exactly as shown, line breaks and quoting included,
    and says "Copied" for a moment. It sits beside the code, never over it, so
    long commands scroll under nothing on narrow screens. name completes the
    button's accessible name: "Copy install command for phibble".
--}}
@props(['code', 'label', 'name'])
@php $id = 'command-'.\Illuminate\Support\Str::slug($label.' '.$name); @endphp
<div {{ $attributes }}>
    <p id="{{ $id }}" class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
    <div x-data="copyCommand(@js($code))" class="code-ink mt-1.5 flex items-start rounded-lg ring-1 ring-black/5 dark:ring-(--color-ink-line)">
        <pre aria-labelledby="{{ $id }}" class="min-w-0 flex-1 overflow-x-auto px-4 py-3 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($code, 'sh') !!}</code></pre>
        <button type="button" x-on:click="copy"
                aria-label="Copy {{ strtolower($label) }} command for {{ $name }}"
                class="m-2 w-16 shrink-0 rounded-md py-1 text-center text-xs font-medium text-zinc-300 ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-php-400">
            <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
        </button>
        <span class="sr-only" aria-live="polite" x-text="copied ? 'Copied' : ''"></span>
    </div>
</div>
