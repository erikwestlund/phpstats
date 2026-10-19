{{--
    A runnable example from resources/examples (see App\Content\Example): its
    code, then the result computed when the page renders. requires lists the
    packages it needs, as in ScriptVignette::available().
--}}
@props(['name', 'requires' => [], 'label' => null])

@php
    $code = \App\Content\Example::code($name);
    $missing = array_values(array_filter((array) $requires, fn (string $package) => ! \App\Livewire\ScriptVignette::available($package)));
    $output = null;
    $failed = false;
    if ($missing === []) {
        try {
            $output = \App\Content\Example::output($name, 'example-result', fn (mixed $value) => view('components.example-result', ['value' => $value])->render());
        } catch (\Throwable $e) {
            report($e);
            $failed = true;
        }
    }
@endphp
<div class="not-prose">
    <x-code-tabs :pipe="$code['pipe']" :chain="$code['chain']" :label="$label" />
    <x-result class="mt-3">
        @if ($missing !== [])
            <p class="text-sm text-zinc-500">This server does not load {{ implode(' or ', $missing) }}, so the result is not shown.</p>
        @elseif ($failed)
            <p class="text-sm text-zinc-500">The example failed to run on this server.</p>
        @else
            {!! $output !!}
        @endif
    </x-result>
</div>
