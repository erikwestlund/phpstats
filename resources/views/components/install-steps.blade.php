{{--
    How to install each package, read from the package itself: the lines under
    install in its synced site.json, and the requirements under requires. A
    package that has synced nothing has no lines to show, so it points at its
    repository rather than guessing, and the block fills in on its own once
    the package syncs.
--}}
<div class="not-prose space-y-10">
    @foreach (\App\Content\Package::all() as $package)
        @php
            $install = $package->install();
            $requires = $package->requires();
            $usable = ($package->project['status'] ?? null) === 'working';
        @endphp
        <section>
            <h3 class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                <a href="{{ $package->url() }}" class="font-mono text-base font-semibold text-php-700 hover:text-php-800 dark:text-php-300 dark:hover:text-php-200">{{ $package->name() }}</a>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $package->tagline() }}</span>
            </h3>

            @if ($install !== [])
                <x-code class="mt-3" lang="txt" :code="implode(PHP_EOL, $install)" />

                @if ($requires !== [])
                    <ul class="mt-3 space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                        @foreach ($requires as $requirement)
                            <li class="flex gap-2">
                                <span class="select-none text-php-400" aria-hidden="true">&middot;</span>
                                <span>{{ $requirement }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @else
                <p class="mt-3 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
                    @if ($usable)
                        This site does not carry install lines for
                        <span class="font-mono">{{ $package->name() }}</span> yet.
                    @else
                        <span class="font-mono">{{ $package->name() }}</span> has nothing to install yet.
                    @endif

                    @if ($package->repository())
                        Its <a href="{{ $package->repository() }}" class="font-medium text-php-700 underline decoration-php-300 underline-offset-2 hover:text-php-800 dark:text-php-300 dark:decoration-php-400/50 dark:hover:text-php-200">repository</a> has the current instructions.
                    @endif
                </p>
            @endif
        </section>
    @endforeach
</div>
