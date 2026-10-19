{{-- A file from this app, shown without its opening tag, namespace, and imports. --}}
@props(['path', 'label' => null, 'language' => 'php'])

@php
    $source = file_get_contents(base_path($path));
    if ($language === 'php') {
        $source = preg_replace(['/^<\?php\s*/', '/^namespace [^;]+;\n/m', '/^use [^;]+;\n/m'], '', $source);
    }
    $source = trim(preg_replace("/\n{3,}/", "\n\n", $source))."\n";
@endphp
<div {{ $attributes->merge(['class' => 'code-ink not-prose overflow-hidden rounded-xl shadow-lg shadow-php-950/10 ring-1 ring-black/5 dark:ring-(--color-ink-line)']) }}>
    <p class="border-b border-white/5 px-4 py-2 text-xs font-medium text-php-200">{{ $label ?? basename($path) }}</p>
    <pre class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6"><code>{!! \App\Content\Code::highlight($source, $language) !!}</code></pre>
</div>
