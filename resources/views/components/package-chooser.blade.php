{{-- Get started's chooser: a task, and the package that does it. --}}
@php
    $tasks = [
        ['Load and inspect typed tables', 'phibble'],
        ['Transform, group, join, and reshape tables', 'phplyr'],
        ['Run columnar and lazy queries on Polars', 'phpolars'],
        ['Fit models and compute statistics', 'phit'],
        ['Build charts', 'phiz'],
        ['Run Stan models', 'cmdstanphp'],
        ['Run PHP in Jupyter notebooks and Quarto documents', 'juphyter'],
    ];
@endphp
<div class="not-prose overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:ring-white/10">
    <table class="w-full text-left text-sm">
        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
            @foreach ($tasks as [$task, $key])
                @php $package = \App\Content\Package::find($key); @endphp
                <tr>
                    <td class="px-4 py-2.5 text-zinc-700 dark:text-zinc-300">{{ $task }}</td>
                    <td class="px-4 py-2.5 text-right">
                        <a href="#{{ $key }}" class="font-mono font-semibold text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">{{ $package->name() }}</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
