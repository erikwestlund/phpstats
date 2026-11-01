{{-- One verification panel's table: phit's values and each library's, rounded alike, with a signed difference only where the rounded values differ. Rendered by landing.verified and cached with the example. --}}
<div class="overflow-x-auto">
    <table class="w-full text-left text-[0.9375rem] tabular-nums">
        <caption class="sr-only">{{ $panel['label'] }}: phit's values and each library's values</caption>
        <thead class="border-b border-zinc-200 text-sm text-zinc-600 dark:border-white/10 dark:text-zinc-400">
            <tr>
                <th scope="col" class="py-2 pr-4 font-medium"><span class="sr-only">Quantity</span></th>
                <th scope="col" class="py-2 pr-4 text-right font-mono font-medium">phit</th>
                @foreach ($panel['libraries'] as $library)
                    <th scope="col" class="whitespace-nowrap py-2 pl-4 text-right font-medium">{{ $library }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
            @foreach ($rows as [$label, $mine, $theirs])
                <tr>
                    <th scope="row" @class(['whitespace-nowrap py-2.5 pr-4 font-normal', 'font-mono text-[0.875rem]' => $key !== 'distributions' && preg_match('/^[\w()]+$/', $label) && $label !== 'Log-likelihood'])>{{ $label }}</th>
                    @php $shown = $value($mine, $panel['relative']); @endphp
                    <td class="whitespace-nowrap py-2.5 pr-4 text-right">{!! $shown !!}</td>
                    @foreach (array_keys($panel['libraries']) as $library)
                        @php
                            $theirsShown = $value($theirs[$library], $panel['relative']);
                            $d = $theirs[$library] - $mine;
                            [$text, $class] = $diff($panel['relative'] && $mine != 0.0 ? $d / abs($mine) : $d);
                        @endphp
                        <td class="whitespace-nowrap py-2.5 pl-4 text-right">
                            {!! $theirsShown !!}
                            @if ($theirsShown !== $shown)
                                <span class="ml-1.5 text-xs {{ $class }}">{!! $text !!}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
