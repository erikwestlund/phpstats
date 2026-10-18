@props(['block', 'key'])

@php $block = \App\Content\Blocks::normalize($block); @endphp
<section>
    @if (! empty($block['title']))
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ $block['title'] }}</h2>
    @endif

    @switch($block['type'])
        @case('text')
            <div class="prose prose-zinc prose-php max-w-3xl dark:prose-invert">{!! \App\Content\Markdown::text($block['markdown']) !!}</div>
            @break
        @case('note')
            <div class="prose prose-zinc prose-php max-w-3xl rounded-xl border-l-4 border-(--l400) bg-(--l50) px-4 py-3 text-sm ring-1 ring-(--l100) dark:prose-invert dark:bg-(--l400)/10 dark:ring-(--l400)/15">{!! \App\Content\Markdown::text($block['markdown']) !!}</div>
            @break
        @case('code')
            @if ($block['chain'] && $block['pipe'])
                <x-code-tabs>
                    <x-slot:chain>{{ $block['chain'] }}</x-slot:chain>
                    <x-slot:pipe>{{ $block['pipe'] }}</x-slot:pipe>
                </x-code-tabs>
            @else
                <x-code :code="$block['chain'] ?? $block['pipe'] ?? $block['code']" />
            @endif
            @break
        @case('output')
            <pre class="overflow-x-auto rounded-xl bg-zinc-50 p-4 font-mono text-[0.8125rem] leading-6 text-zinc-700 ring-1 ring-zinc-200 dark:bg-white/[0.03] dark:text-zinc-300 dark:ring-white/10"><code>{{ $block['content'] }}</code></pre>
            @break
        @case('metrics')
            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($block['items'] as $item)
                    <div class="rounded-2xl bg-gradient-to-br from-(--l50) to-white p-4 ring-1 ring-(--l100) dark:from-(--l400)/10 dark:to-transparent dark:ring-(--l400)/15">
                        <dt class="text-zinc-500">{{ $item['label'] }}</dt>
                        <dd class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 tabular-nums dark:text-white">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
            @break
        @case('table')
        @case('model')
            <div class="overflow-x-auto rounded-xl ring-1 ring-zinc-200 dark:ring-white/10">
                <table class="w-full text-sm tabular-nums">
                    <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wider text-zinc-500 dark:bg-white/[0.03]">
                        <tr class="border-b border-zinc-200 dark:border-white/10">
                            @foreach ($block['columns'] as $c => $column)
                                <th @class(['px-4 py-2.5 font-semibold', 'text-right' => $c > 0])>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($block['rows'] as $row)
                            <tr class="border-b border-zinc-100 last:border-0 dark:border-white/5">
                                @foreach ($row as $c => $cell)
                                    <td @class(['px-4 py-2', 'text-right' => $c > 0, 'font-medium' => $c === 0])>{{ $cell }}</td>
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
            <div class="rounded-2xl bg-white p-4 ring-1 ring-zinc-200 dark:bg-white/[0.03] dark:ring-white/10"><x-phiz :plot="$block['plot']" wire:key="{{ $key }}" class="w-full" /></div>
            @break
    @endswitch
</section>
