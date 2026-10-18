@props(['block', 'key'])

@php $block = \App\Content\Blocks::normalize($block); @endphp
<section>
    @if (! empty($block['title']))
        <h2 class="mb-3 text-sm font-medium uppercase tracking-wide text-zinc-500">{{ $block['title'] }}</h2>
    @endif

    @switch($block['type'])
        @case('text')
            <div class="prose prose-zinc max-w-3xl dark:prose-invert">{!! \Illuminate\Support\Str::markdown($block['markdown'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
            @break
        @case('note')
            <div class="prose prose-zinc max-w-3xl rounded-lg border-l-4 border-sky-400 bg-sky-50 px-4 py-3 text-sm dark:prose-invert dark:bg-sky-950/40">{!! \Illuminate\Support\Str::markdown($block['markdown'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
            @break
        @case('code')
            @if ($block['chain'] && $block['pipe'])
                <x-code-tabs>
                    <x-slot:chain>{{ $block['chain'] }}</x-slot:chain>
                    <x-slot:pipe>{{ $block['pipe'] }}</x-slot:pipe>
                </x-code-tabs>
            @else
                <pre class="overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>{{ $block['chain'] ?? $block['pipe'] ?? $block['code'] }}</code></pre>
            @endif
            @break
        @case('output')
            <pre class="overflow-x-auto rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-800"><code>{{ $block['content'] }}</code></pre>
            @break
        @case('metrics')
            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($block['items'] as $item)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                        <dt class="text-zinc-500">{{ $item['label'] }}</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
            @break
        @case('table')
        @case('model')
            <div class="overflow-x-auto">
                <table class="w-full text-sm tabular-nums">
                    <thead class="text-left text-zinc-500">
                        <tr class="border-b border-zinc-200 dark:border-zinc-800">
                            @foreach ($block['columns'] as $c => $column)
                                <th @class(['py-2 pr-4 font-medium', 'text-right' => $c > 0])>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($block['rows'] as $row)
                            <tr class="border-b border-zinc-100 dark:border-zinc-900">
                                @foreach ($row as $c => $cell)
                                    <td @class(['py-2 pr-4', 'text-right' => $c > 0])>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (! empty($block['more']))
                <p class="mt-2 text-xs text-zinc-500">{{ number_format($block['more']) }} more rows not shown.</p>
            @endif
            @if (! empty($block['summary']))
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $block['summary'] }}</p>
            @endif
            @break
        @case('chart')
            <x-phiz :plot="$block['plot']" wire:key="{{ $key }}" class="w-full" />
            @break
    @endswitch
</section>
