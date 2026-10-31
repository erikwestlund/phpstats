{{--
    One package on the Installation page, all from its synced site.json: its
    name, its requirements, the commands that install it, the command that
    checks the install, and one link to its installation guide. A check
    command that is also the last install line, as juphyter doctor is, moves
    to the Verify block. A check whose output is a version number is described
    as printing the installed version, and the note follows in one sentence.
--}}
@props(['package'])
@php
    $package = \App\Content\Package::find($package);
    $verify = $package->verify();
    $guide = $package->installation();
    $install = array_values(array_filter($package->install(), fn (string $line) => $verify === null || trim($line) !== trim($verify['command'])));
    $versioned = $verify && preg_match('/^\d+\.\d+(\.\d+)?$/', trim((string) $verify['output']));
    $note = trim(($versioned ? 'Prints the installed version. ' : '').($verify['note'] ?? ''));
@endphp
<section id="{{ $package->key }}" class="not-prose mt-10 border-t border-zinc-200 pt-10 dark:border-white/10">
    <h2 class="font-mono text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $package->name() }}</h2>
    @if ($install !== [])
        <x-requirements class="mt-2" :package="$package" compact />
        <x-command class="mt-5" label="Install" :name="$package->name()" :code="implode(PHP_EOL, $install)" />
        @if ($verify)
            <x-command class="mt-3" label="Verify" :name="$package->name()" :code="$verify['command']" />
            @if ($note !== '')
                <p class="mt-3 text-[0.9375rem] leading-7 text-zinc-600 dark:text-zinc-400">{{ $note }}</p>
            @endif
        @endif
        @if ($guide)
            <p class="mt-3 text-sm"><a href="{{ $guide }}" class="font-medium text-php-700 underline decoration-php-300 underline-offset-4 hover:text-php-800 dark:text-php-300 dark:decoration-php-700 dark:hover:text-php-200"><span class="font-mono">{{ $package->name() }}</span> installation guide</a></p>
        @endif
    @elseif ($package->repository())
        <p class="mt-3 text-[0.9375rem] text-zinc-600 dark:text-zinc-400">
            The install steps are in the <a href="{{ $package->repository() }}" class="font-medium text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">repository</a>.
        </p>
    @endif
</section>
