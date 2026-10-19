{{-- An example's return value: a data frame, a phiz plot, a phit fit, or anything else printed. --}}
@props(['value'])

@if ($value instanceof \Phiz\Plot)
    <x-phiz :plot="$value->width('container')->height(280)" class="w-full" />
@elseif (is_object($value) && method_exists($value, 'types') && method_exists($value, 'toRows'))
    <x-frame-table :frame="$value" class="-m-4" />
@elseif (is_array($value) && isset($value['coefficients'], $value['columns']))
    <x-fit-table :fit="$value" class="-m-4" />
@else
    <pre class="overflow-x-auto font-mono text-xs">{{ is_scalar($value) ? $value : print_r($value, true) }}</pre>
@endif
