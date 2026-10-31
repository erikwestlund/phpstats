{{-- The About page's packages: each name linked to its overview, and what it is for. --}}
@php
    $purposes = [
        'phibble' => 'Typed, immutable data frames, read from CSV files or databases.',
        'phplyr' => 'Filtering, grouping, summarizing, joining, and reshaping data frames.',
        'phpolars' => 'Polars data frames and lazy queries, as a PHP extension.',
        'phit' => 'Regression, mixed models, probability distributions, and descriptive statistics.',
        'phiz' => 'Charts built with a grammar of graphics and rendered with Vega-Lite.',
        'cmdstanphp' => 'Bayesian models written in Stan and fit with CmdStan.',
        'juphyter' => 'A PHP kernel for Jupyter notebooks and Quarto documents.',
    ];
@endphp
<div class="not-prose overflow-x-auto rounded-lg border border-zinc-200 dark:border-white/10">
    <table class="w-full text-left text-[0.9375rem]">
        <thead class="border-b border-zinc-200 bg-zinc-50 text-sm text-zinc-600 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-400">
            <tr>
                <th scope="col" class="px-4 py-2 font-medium">Package</th>
                <th scope="col" class="px-4 py-2 font-medium">Purpose</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
            @foreach ($purposes as $key => $purpose)
                @php $package = \App\Content\Package::find($key); @endphp
                <tr>
                    <td class="whitespace-nowrap px-4 py-2.5 align-top"><a href="{{ $package->url() }}" class="font-mono font-medium text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">{{ $package->name() }}</a></td>
                    <td class="px-4 py-2.5 leading-6 text-zinc-700 dark:text-zinc-300">{{ $purpose }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
