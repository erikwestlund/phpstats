{{--
    One package on Get started: its name, linked to its pages, the command
    that installs it, and its requirements.
--}}
@props(['package', 'command'])
@php
    $package = \App\Content\Package::find($package);
@endphp
<section class="not-prose mt-10 first-of-type:mt-6">
    <h4 class="font-mono text-sm font-semibold">
        <a href="{{ $package->url() }}" class="text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">{{ $package->name() }}</a>
    </h4>
    <x-code class="mt-2" lang="sh" :code="$command" />
    <x-requirements class="mt-3" :package="$package" />
</section>
