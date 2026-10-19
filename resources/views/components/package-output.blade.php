{{--
    The result of a package explorer example, in the form the package prints
    it: a chart for a phiz plot, Polars' own table for a phpolars table, a
    tibble for a phibble frame, and an R-style summary for a phit fit.
--}}
@props(['value'])

@php
    $text = match (true) {
        $value instanceof \Phiz\Plot => null,
        is_object($value) && method_exists($value, 'polars') => (string) $value->polars(),
        $value instanceof \Stringable => (string) $value,
        is_array($value) && isset($value['coefficients'], $value['columns']) => \App\Content\Console::fit($value),
        default => print_r($value, true),
    };
@endphp
@if ($text === null)
    <div class="px-4 py-3">
        <x-phiz :plot="$value->width('container')->height(240)" class="w-full" />
    </div>
@else
    <pre class="overflow-x-auto px-4 py-3 font-mono text-xs leading-5 text-zinc-800 dark:text-zinc-200">{{ $text }}</pre>
@endif
