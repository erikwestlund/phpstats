{{-- One verification panel's table: phit's values and each library's, rounded alike, with a signed difference only where the rounded values differ, and a dash where a library has no equivalent. Rendered by landing.verified and cached with the example. --}}
{{-- Below 640px each quantity is a short list of its values, so nothing is wider than the screen. --}}
<dl class="divide-y divide-zinc-100 border-y border-zinc-200 text-sm sm:hidden dark:divide-white/5 dark:border-white/10">
    @foreach ($rows as [$label, $mine, $theirs])
        @php $shown = $value($mine, $panel['relative']); @endphp
        <div class="py-2.5">
            <dt class="font-medium text-zinc-900 dark:text-zinc-100">{{ $label }}</dt>
            <dd class="mt-1 grid grid-cols-2 gap-x-4 gap-y-0.5 text-xs">
                <span class="flex justify-between gap-2"><span class="font-mono text-zinc-500">{{ $panel['primary'] ?? 'phit' }}</span><span class="font-mono">{!! $shown !!}</span></span>
                @foreach ($panel['libraries'] as $library => $libraryLabel)
                    <span class="flex justify-between gap-2">
                        <span class="text-zinc-500">{!! \App\Content\Libraries::mono(e($libraryLabel)) !!}</span>
                        <span class="font-mono">{!! ($theirs[$library] ?? null) === null ? '—' : $value($theirs[$library], $panel['relative']) !!}</span>
                    </span>
                @endforeach
            </dd>
        </div>
    @endforeach
</dl>
<div class="hidden sm:block">
    <table class="w-full text-left text-sm sm:text-[0.9375rem]">
        <caption class="sr-only">{{ $panel['label'] }}: {{ $panel['primary'] ?? 'phit' }}'s values and each library's values</caption>
        <thead class="border-b border-zinc-200 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-400">
            <tr>
                <th scope="col" class="py-2 pr-2 font-medium sm:pr-4"><span class="sr-only">Quantity</span></th>
                <th scope="col" class="py-2 pr-2 text-right font-mono font-medium sm:pr-4">{{ $panel['primary'] ?? 'phit' }}</th>
                @foreach ($panel['libraries'] as $library)
                    <th scope="col" class="py-2 pl-2 text-right align-bottom font-medium sm:pl-4">{!! \App\Content\Libraries::mono(e($library)) !!}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
            @foreach ($rows as [$label, $mine, $theirs, $term])
                <tr>
                    <th scope="row" class="py-2.5 pr-2 font-normal sm:pr-4">{{ $label }}</th>
                    @php $shown = $value($mine, $panel['relative']); @endphp
                    <td class="py-2.5 pr-2 text-right font-mono text-xs sm:pr-4 sm:text-[0.875rem]">{!! $shown !!}</td>
                    @foreach (array_keys($panel['libraries']) as $library)
                        @if (($theirs[$library] ?? null) === null)
                            <td class="py-2.5 pl-2 text-right text-zinc-400 sm:pl-4 dark:text-zinc-500"><span aria-label="no equivalent">—</span></td>
                        @else
                            @php
                                $theirsShown = $value($theirs[$library], $panel['relative']);
                                $d = $theirs[$library] - $mine;
                                [$text, $class] = $diff($panel['relative'] && $mine != 0.0 ? $d / abs($mine) : $d);
                            @endphp
                            <td class="py-2.5 pl-2 text-right font-mono text-xs sm:pl-4 sm:text-[0.875rem]">
                                {!! $theirsShown !!}
                                @if ($theirsShown !== $shown)
                                    <span class="ml-1.5 text-xs {{ $class }}">{!! $text !!}</span>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
