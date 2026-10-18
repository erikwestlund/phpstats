<x-layout>
    <article class="max-w-3xl">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $package->name() }}</h1>
            <x-status :project="$package->project" />
        </div>
        <p class="mt-3 text-xl text-zinc-700 dark:text-zinc-300">{{ $package->tagline() }}</p>

        @foreach ($package->summary() as $paragraph)
            <p class="mt-4 text-zinc-700 dark:text-zinc-300">{{ $paragraph }}</p>
        @endforeach

        @if ($package->install())
            <h2 class="mt-10 text-sm font-medium uppercase tracking-wide text-zinc-500">Install</h2>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>{{ implode("\n", $package->install()) }}</code></pre>
            @if ($package->requires())
                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-zinc-600 dark:text-zinc-400">
                    @foreach ($package->requires() as $requirement)
                        <li>{{ $requirement }}</li>
                    @endforeach
                </ul>
            @endif
        @endif

        @if ($example = $package->example())
            <h2 class="mt-10 text-sm font-medium uppercase tracking-wide text-zinc-500">Example</h2>
            @if ($example['chain'] && $example['pipe'])
                <x-code-tabs class="mt-3">
                    <x-slot:chain>{{ $example['chain'] }}</x-slot:chain>
                    <x-slot:pipe>{{ $example['pipe'] }}</x-slot:pipe>
                </x-code-tabs>
            @else
                <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>{{ $example['chain'] ?? $example['pipe'] }}</code></pre>
            @endif
            @if ($example['output'])
                <pre class="mt-2 overflow-x-auto rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-800"><code>{{ $example['output'] }}</code></pre>
            @endif
        @endif
    </article>

    @php
        $sections = array_filter([
            'docs' => $package->hasDocs() ? ['Docs', count($package->docsNav()).' guides and references, with the math and code from the package documentation.'] : null,
            'vignettes' => $package->vignettes() ? ['Vignettes', count($package->vignettes()).' live examples that run on this server.'] : null,
            'benchmarks' => $package->benchmarks() ? ['Benchmarks', count($package->benchmarks()).' timing reports against reference implementations.'] : null,
            'verification' => $package->verifications() ? ['Verification', count($package->verifications()).' reports comparing results with reference implementations.'] : null,
        ]);
    @endphp
    @if ($sections)
        <div class="mt-12 grid gap-4 sm:grid-cols-2">
            @foreach ($sections as $section => [$label, $description])
                <a href="{{ $package->url($section) }}" class="block rounded-lg border border-zinc-200 p-5 hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                    <span class="font-semibold">{{ $label }}</span>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $description }}</p>
                </a>
            @endforeach
        </div>
    @endif

    @unless ($package->synced())
        <p class="mt-10 text-sm text-zinc-500">
            This package has not published its site content yet.
            @if ($package->externalDocs())
                Its documentation is at <a href="{{ $package->externalDocs() }}" class="underline">{{ preg_replace('#^https?://#', '', rtrim($package->externalDocs(), '/')) }}</a>.
            @endif
        </p>
    @endunless

    @if ($sync = $package->sync())
        <p class="mt-10 text-xs text-zinc-500">
            Content from <a href="https://github.com/{{ $sync['repo'] }}/tree/{{ $sync['commit'] }}" class="underline">{{ $sync['repo'].'@'.substr($sync['commit'], 0, 7) }}</a>.
        </p>
    @endif
</x-layout>
