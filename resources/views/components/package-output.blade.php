{{--
    The result of a package explorer example, in the form the package prints
    it: a chart for a phiz plot, Polars' table for a phpolars table, phibble's
    printed frame or its glimpse() text, and an R-style summary for a phit
    fit, given alone or as ['fit' => $fit, 'table' => $frame] with a phibble
    frame computed from it shown below. phibble's printer heads a frame with
    "# A tibble: n × m"; that line is left out, and the size goes in the
    label instead.
--}}
@props(['value', 'label'])

@php
    $size = null;
    $text = match (true) {
        $value instanceof \Phiz\Plot => null,
        is_object($value) && method_exists($value, 'polars') => (string) $value->polars(),
        $value instanceof \Stringable => (string) $value,
        is_string($value) => $value,
        is_array($value) && isset($value['fit']['coefficients']) => \App\Content\Console::fit($value['fit'])."\n\n".preg_replace('/\A# A tibble: [^\n]+\n/u', '', (string) $value['table']),
        is_array($value) && isset($value['coefficients'], $value['columns']) => \App\Content\Console::fit($value),
        default => print_r($value, true),
    };
    if ($text !== null && preg_match('/\A# A tibble: ([^\n]+)\n/u', $text, $m)) {
        $size = $m[1];
        $text = substr($text, strlen($m[0]));
    }
@endphp
<p class="px-5 pt-5 text-sm sm:px-7 text-zinc-500 dark:text-zinc-400">{{ $label }}@if ($size) · {{ $size }}@endif</p>
@if ($text === null)
    <div class="px-5 pb-6 pt-3 sm:px-7">
        {{-- A plot that sets its own size, such as a faceted one sized per panel, keeps it. --}}
        <x-phiz :plot="$value->width === null ? $value->width('container')->height(300) : $value" class="w-full overflow-x-auto" />
    </div>
@else
    <pre class="overflow-x-auto px-5 pb-6 pt-3 font-mono sm:px-7 text-[0.8125rem] leading-relaxed text-zinc-800 dark:text-zinc-200">{{ $text }}</pre>
@endif
