{{--
    A highlighted code block on the dark ink background. label puts a small
    header above the code; window adds the three window dots. hanging splits
    the code into lines that wrap with a hanging indent, for narrow columns.
--}}
@props(['code', 'lang' => 'php', 'label' => null, 'window' => false, 'hanging' => false])

<div {{ $attributes->merge(['class' => 'code-ink overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)']) }}>
    @if ($label || $window)
        <div class="flex items-center gap-3 border-b border-white/5 px-4 py-2.5 text-xs">
            @if ($window)
                <span class="flex gap-1.5" aria-hidden="true">
                    <span class="size-2.5 rounded-full bg-white/15"></span><span class="size-2.5 rounded-full bg-white/15"></span><span class="size-2.5 rounded-full bg-white/15"></span>
                </span>
            @endif
            @if ($label)
                <span class="font-medium text-php-200">{{ $label }}</span>
            @endif
        </div>
    @endif
    <pre class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! $hanging ? \App\Content\Code::hanging(\App\Content\Code::highlight($code, $lang)) : \App\Content\Code::highlight($code, $lang) !!}</code></pre>
</div>
