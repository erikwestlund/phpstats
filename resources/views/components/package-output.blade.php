{{--
    The result of a package explorer example, in the form the package prints
    it: a chart for a phiz plot, Polars' table for a phpolars table, phibble's
    printed frame, and an R-style summary for a phit fit. phibble's printer
    heads a frame with "# A tibble: n × m"; that line is left out, and the
    size goes in the label instead.
--}}
@props(['value', 'label'])

@php
    $size = null;
    $text = match (true) {
        $value instanceof \Phiz\Plot => null,
        is_object($value) && method_exists($value, 'polars') => (string) $value->polars(),
        $value instanceof \Stringable => (string) $value,
        is_array($value) && isset($value['coefficients'], $value['columns']) => \App\Content\Console::fit($value),
        default => print_r($value, true),
    };
    if ($text !== null && preg_match('/\A# A tibble: ([^\n]+)\n/u', $text, $m)) {
        $size = $m[1];
        $text = substr($text, strlen($m[0]));
    }
@endphp
<p class="px-4 pt-2.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}@if ($size) · {{ $size }}@endif</p>
@if ($text === null)
    <div class="px-4 py-2">
        <x-phiz :plot="$value->width('container')->height(200)" class="w-full" />
    </div>
@else
    <pre class="max-h-56 overflow-auto px-4 pb-3 pt-1.5 font-mono text-xs leading-5 text-zinc-800 dark:text-zinc-200">{{ $text }}</pre>
@endif
