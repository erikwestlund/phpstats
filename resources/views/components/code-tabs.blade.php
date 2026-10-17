{{--
    Two versions of a phplyr example: the method chain (PHP 8.3+) and the pipe
    operator (PHP 8.5). The choice is shared by every tabset on the page and
    remembered between pages, like the "syntax" tab group in the package docs.
--}}
@props(['chain', 'pipe'])

<div x-data="syntaxTabs" {{ $attributes }}>
    <div role="tablist" aria-label="Code syntax" class="flex gap-1 text-sm">
        @foreach (['chain' => 'Method chain (PHP 8.3+)', 'pipe' => 'Pipe (PHP 8.5)'] as $key => $label)
            <button type="button" role="tab" x-on:click="choose('{{ $key }}')"
                    x-bind:aria-selected="(syntax === '{{ $key }}').toString()"
                    aria-selected="{{ $key === 'chain' ? 'true' : 'false' }}"
                    class="rounded-t-md px-3 py-1.5 text-zinc-600 aria-selected:bg-zinc-100 aria-selected:font-medium aria-selected:text-zinc-900 dark:text-zinc-400 dark:aria-selected:bg-zinc-900 dark:aria-selected:text-zinc-100">
                {{ $label }}
            </button>
        @endforeach
    </div>
    <pre x-show="syntax === 'chain'" class="overflow-x-auto rounded-lg rounded-tl-none bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>{!! trim($chain) !!}</code></pre>
    <pre x-show="syntax === 'pipe'" x-cloak class="overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>{!! trim($pipe) !!}</code></pre>
</div>
