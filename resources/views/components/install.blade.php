{{--
    One package on the Installation page, all from its synced site.json: its
    name, its requirements, the commands that install it, the command that
    checks the install, and one link to its installation guide. A check
    command that is also the last install line, as juphyter doctor is, moves
    to the Verify block. An output that is a version number is shown as an
    example, because the command prints whichever version is installed.
--}}
@props(['package'])
@php
    $package = \App\Content\Package::find($package);
    $verify = $package->verify();
    $guide = $package->installation();
    $install = array_values(array_filter($package->install(), fn (string $line) => $verify === null || trim($line) !== trim($verify['command'])));
    $output = $verify ? trim((string) $verify['output']) : '';
    $versioned = preg_match('/^\d+\.\d+(\.\d+)?$/', $output);
    $code = 'font-mono text-[0.8125rem] text-zinc-800 dark:text-zinc-200';
@endphp
<section id="{{ $package->key }}" class="not-prose mt-8 border-t border-zinc-200 pt-8 dark:border-white/10">
    <h2 class="font-mono text-xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $package->name() }}</h2>
    @if ($install !== [])
        <x-requirements class="mt-4" :package="$package" compact />
        <x-command class="mt-5" label="Install" :name="$package->name()" :code="implode(PHP_EOL, $install)" />
        @if ($verify)
            <x-command class="mt-4" label="Verify" :name="$package->name()" :code="$verify['command']" />
            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                @if ($versioned)
                    Prints the installed version, such as <code class="{{ $code }}">{{ $output }}</code>.
                @elseif ($output !== '')
                    Prints <code class="{{ $code }}">{{ $output }}</code>@if (! preg_match('/[.!?]$/', $output)).@endif
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
