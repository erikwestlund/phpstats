{{--
    One package on the Installation page, all from its synced site.json: its
    name, the commands that install it, its essential requirements, the command
    that checks the install, and one link to its installation guide. An output
    that is a version number is shown as an example, because the command
    prints whichever version is installed.
--}}
@props(['package'])
@php
    $package = \App\Content\Package::find($package);
    $verify = $package->verify();
    $guide = $package->installation();
    $versioned = $verify && preg_match('/^\d+\.\d+(\.\d+)?$/', trim((string) $verify['output']));
    $code = 'font-mono text-[0.8125rem] text-zinc-800 dark:text-zinc-200';
@endphp
<section id="{{ $package->key }}" class="not-prose mt-12 border-t border-zinc-200 pt-12 dark:border-white/10">
    <h2 class="font-mono text-xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $package->name() }}</h2>
    @if ($package->install() !== [])
        <x-code class="mt-4" lang="sh" :code="implode(PHP_EOL, $package->install())" />
        <x-requirements class="mt-4" :package="$package" compact />
        @if ($verify)
            <p class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                <code class="{{ $code }}">{{ $verify['command'] }}</code>
                @if ($versioned)
                    prints the installed version, such as <code class="{{ $code }}">{{ $verify['output'] }}</code>.
                @elseif ($verify['output'])
                    prints <code class="{{ $code }}">{{ $verify['output'] }}</code>@if (! preg_match('/[.!?]$/', trim((string) $verify['output']))).@endif
                @endif
                {{ $verify['note'] }}
            </p>
        @endif
        @if ($guide)
            <p class="mt-4 text-sm"><a href="{{ $guide }}" class="font-medium text-php-700 underline decoration-php-300 underline-offset-4 hover:text-php-800 dark:text-php-300 dark:decoration-php-700 dark:hover:text-php-200"><span class="font-mono">{{ $package->name() }}</span> installation guide</a></p>
        @endif
    @elseif ($package->repository())
        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
            The install steps are in the <a href="{{ $package->repository() }}" class="font-medium text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">repository</a>.
        </p>
    @endif
</section>
