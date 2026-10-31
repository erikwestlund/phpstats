{{--
    One package on Get started, all from its synced site.json: its name linked
    to its pages, the commands that install it, its requirements, the command
    that checks the install, and its getting-started page.
--}}
@props(['package'])
@php
    $package = \App\Content\Package::find($package);
    $verify = $package->verify();
    $quickstart = $package->quickstart();
@endphp
<section id="{{ $package->key }}" class="not-prose mt-10 scroll-mt-20 first-of-type:mt-6">
    <h4 class="font-mono text-sm font-semibold">
        <a href="{{ $package->url() }}" class="text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">{{ $package->name() }}</a>
    </h4>
    @if ($package->install() !== [])
        <x-code class="mt-2" lang="sh" :code="implode(PHP_EOL, $package->install())" />
        <x-requirements class="mt-3" :package="$package" />
        @if ($verify)
            <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Check the install with <code class="font-mono text-[0.8125rem] text-zinc-800 dark:text-zinc-200">{{ $verify['command'] }}</code>@if ($verify['output']), which prints <code class="font-mono text-[0.8125rem] text-zinc-800 dark:text-zinc-200">{{ $verify['output'] }}</code>@endif.
            </p>
        @endif
        @if ($quickstart)
            <p class="mt-2 text-sm"><a href="{{ $quickstart }}" class="font-medium text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">Getting started with {{ $package->name() }}</a></p>
        @endif
    @elseif ($package->repository())
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            The install steps are in the <a href="{{ $package->repository() }}" class="font-medium text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">repository</a>.
        </p>
    @endif
</section>
