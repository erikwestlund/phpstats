{{--
    A shell command in a pale bordered panel whose header holds its label and
    a copy icon. The button copies $code exactly as shown, line breaks and
    quoting included, and shows a check for a moment. name completes the
    button's accessible name: "Copy install command for phibble".
--}}
@props(['code', 'label', 'name'])
<div x-data="copyCommand(@js($code))" {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-zinc-50 dark:border-white/10 dark:bg-white/[0.03]']) }}>
    <div class="flex items-center justify-between border-b border-zinc-200 py-1 pl-4 pr-1.5 dark:border-white/10">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</span>
        <button type="button" x-on:click="copy"
                aria-label="Copy {{ strtolower($label) }} command for {{ $name }}" title="Copy"
                class="rounded-md p-1.5 text-zinc-500 transition hover:bg-zinc-200/70 hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-php-500 dark:text-zinc-400 dark:hover:bg-white/10 dark:hover:text-white">
            <x-heroicon-m-clipboard-document x-show="! copied" class="size-4" />
            <x-heroicon-m-check x-show="copied" x-cloak class="size-4 text-php-600 dark:text-php-300" />
        </button>
        <span class="sr-only" aria-live="polite" x-text="copied ? 'Copied' : ''"></span>
    </div>
    <pre class="code-panel overflow-x-auto px-4 py-3 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($code, 'sh') !!}</code></pre>
</div>
