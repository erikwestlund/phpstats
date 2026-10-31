@php
    $layer = $package->layer();
    $verified = collect($package->verifications())->flatMap(fn ($v) => $v['cases']);
    $sections = array_filter([
        'docs' => $package->hasDocs() ? ['Docs', 'book-open', count($package->docsNav()).' pages of guides and reference'] : null,
        'vignettes' => $package->vignettes() ? ['Examples', 'play-circle', count($package->vignettes()).' live examples on this server'] : null,
        'benchmarks' => $package->benchmarks() ? ['Benchmarks', 'clock', count($package->benchmarks()).' timing reports'] : null,
        'verification' => $package->verifications() ? ['Verification', 'check-badge', $verified->where('passed', true)->count().' of '.$verified->count().' results agree with the reference'] : null,
    ]);
    $relations = collect($package->relations())->groupBy('kind');
@endphp
<x-layout :bare="true">
    <section class="layer-{{ $layer['color'] }} relative isolate overflow-hidden border-b border-zinc-200/70 bg-gradient-to-b from-(--l50) via-white to-white dark:border-white/5 dark:from-(--l950)/50 dark:via-zinc-950 dark:to-zinc-950">
        <div class="absolute -right-40 -top-40 -z-10 size-[32rem] rounded-full bg-(--l300)/30 blur-3xl dark:bg-(--l500)/10" aria-hidden="true"></div>
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-14 *:min-w-0 sm:px-6 lg:grid-cols-[1.15fr_1fr] lg:px-8 lg:py-20">
            <div>
                <p class="flex items-center gap-2 text-sm font-semibold text-(--l700) dark:text-(--l300)">{{ $layer['title'] }}
                    <span class="font-normal text-zinc-500 dark:text-zinc-400">· {{ $layer['role'] }}</span>
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-4">
                    <h1 class="font-mono text-5xl font-semibold tracking-tight text-zinc-950 dark:text-white">{{ $package->name() }}</h1>
                </div>
                <p class="mt-5 max-w-2xl text-xl leading-8 text-zinc-700 dark:text-zinc-300">{{ $package->tagline() }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($package->hasDocs() || $package->externalDocs())
                        <a href="{{ $package->hasDocs() ? $package->url('docs') : $package->externalDocs() }}" class="inline-flex items-center gap-2 rounded-full bg-(--l600) px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-(--l500)/25 transition hover:bg-(--l500)">
                            <x-heroicon-m-book-open class="size-4" /> Read the docs
                        </a>
                    @endif
                    @if ($package->vignettes())
                        <a href="{{ $package->url('vignettes') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-zinc-900 ring-1 ring-zinc-200 transition hover:ring-(--l300) dark:bg-white/5 dark:text-white dark:ring-white/10">
                            <x-heroicon-m-play-circle class="size-4 text-(--l500)" /> Try an example
                        </a>
                    @endif
                    @if ($package->repository())
                        <a href="{{ $package->repository() }}" class="inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold text-zinc-700 transition hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-white">
                            <x-github-icon class="size-4" /> Source
                        </a>
                    @endif
                </div>
            </div>

            <div class="self-center">
                @if ($package->install())
                    <x-code :code="implode(PHP_EOL, $package->install())" lang="bash" label="Install" />
                    <x-requirements class="mt-4" :package="$package" />
                    @foreach ($package->companions() as $companion)
                        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                            Can't install extensions? <a href="{{ $companion->repository() }}" class="font-medium text-(--l700) underline decoration-(--l300) underline-offset-2 dark:text-(--l300)">{{ $companion->name() }}</a> defines the same functions in plain PHP.
                        </p>
                    @endforeach
                @else
                    @php
                        $links = array_filter([
                            'Documentation' => $package->externalDocs(),
                            ...($package->project['links'] ?? []),
                            'Source on GitHub' => $package->repository(),
                        ]);
                    @endphp
                    <div class="overflow-hidden rounded-xl bg-white/80 ring-1 ring-zinc-200 backdrop-blur dark:bg-white/[0.03] dark:ring-white/10">
                        <p class="border-b border-zinc-100 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-white/5">Links</p>
                        <ul class="divide-y divide-zinc-100 dark:divide-white/5">
                            @foreach ($links as $label => $href)
                                <li>
                                    <a href="{{ $href }}" class="group flex items-center justify-between gap-4 px-5 py-3 text-sm font-medium text-zinc-800 transition hover:bg-(--l50) hover:text-(--l700) dark:text-zinc-200 dark:hover:bg-(--l400)/10 dark:hover:text-(--l300)">
                                        {{ $label }}
                                        <x-heroicon-m-arrow-up-right class="size-4 text-zinc-400 transition group-hover:text-(--l500)" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- A package with no sections, relations, or synced content has an empty
         aside, so the second column is left out rather than shown blank. --}}
    @php $hasAside = $sections !== [] || $relations->isNotEmpty() || $package->sync() !== null; @endphp
    <div @class([
        'layer-'.$layer['color'],
        'mx-auto grid max-w-7xl gap-12 px-4 py-14 sm:px-6 lg:px-8',
        'lg:grid-cols-[minmax(0,1fr)_22rem]' => $hasAside,
    ])>
        <div @class(['min-w-0', 'lg:max-w-4xl' => ! $hasAside])>
            <div class="prose prose-zinc prose-php max-w-none text-[1.0625rem] dark:prose-invert">
                @foreach ($package->summary() as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

            @if ($example = $package->example())
                <h2 class="mt-12 text-lg font-semibold tracking-tight">Example</h2>
                @if ($example['chain'] && $example['pipe'])
                    <x-code-tabs class="mt-4">
                        <x-slot:chain>{{ $example['chain'] }}</x-slot:chain>
                        <x-slot:pipe>{{ $example['pipe'] }}</x-slot:pipe>
                    </x-code-tabs>
                @else
                    <x-code class="mt-4" :code="$example['chain'] ?? $example['pipe']" />
                @endif
                @if ($example['output'])
                    @php [$output, $size] = \App\Content\Package::splitTableHeader($example['output']); @endphp
                    <div class="mt-3 overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:ring-white/10">
                        <p class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-xs font-medium text-zinc-500 dark:border-white/10 dark:bg-white/[0.03]">{{ $size === null ? 'Output' : "Output · {$size}" }}</p>
                        <pre class="overflow-x-auto p-4 font-mono text-[0.8125rem] leading-6 text-zinc-700 dark:text-zinc-300"><code>{{ $output }}</code></pre>
                    </div>
                @endif
            @endif

            @if ($vignettes = array_slice($package->vignettes(), 0, 4))
                <div class="mt-12 flex items-baseline justify-between gap-4">
                    <h2 class="text-lg font-semibold tracking-tight">Run it live</h2>
                    <a href="{{ $package->url('vignettes') }}" class="text-sm font-medium text-(--l700) hover:text-(--l600) dark:text-(--l300)">All vignettes →</a>
                </div>
                <x-explore.vignettes class="mt-4" :items="array_map(fn ($v) => $v['owner'].'/'.$v['slug'], $vignettes)" />
            @endif

            @unless ($package->synced())
                <p class="mt-10 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-600 ring-1 ring-zinc-200 dark:bg-white/[0.03] dark:text-zinc-400 dark:ring-white/10">
                    This package has not published its site content yet.
                    @if ($package->externalDocs())
                        Its documentation is at <a href="{{ $package->externalDocs() }}" class="font-medium text-(--l700) underline underline-offset-2 dark:text-(--l300)">{{ preg_replace('#^https?://#', '', rtrim($package->externalDocs(), '/')) }}</a>.
                    @endif
                </p>
            @endunless
        </div>

        @if ($hasAside)
        <aside class="space-y-8">
            @if ($sections)
                <div class="space-y-3">
                    @foreach ($sections as $section => [$label, $icon, $description])
                        <a href="{{ $package->url($section) }}" class="group flex items-start gap-3 rounded-xl bg-white p-4 ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:shadow-md hover:shadow-(--l500)/10 hover:ring-(--l300) dark:bg-white/[0.03] dark:ring-white/10 dark:hover:ring-(--l400)/40">
                            <span class="rounded-lg bg-(--l50) p-2 text-(--l600) dark:bg-(--l400)/10 dark:text-(--l300)"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" /></span>
                            <span>
                                <span class="flex items-center gap-1 font-semibold text-zinc-900 dark:text-white">{{ $label }} <x-heroicon-m-arrow-right class="size-3.5 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100" /></span>
                                <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($relations->isNotEmpty())
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Connected packages</h2>
                    @foreach (['uses' => 'Uses', 'used_by' => 'Used by'] as $kind => $title)
                        @if ($relations->has($kind))
                            <p class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ $title }}</p>
                            <ul class="mt-2 space-y-3">
                                @foreach ($relations[$kind] as $relation)
                                    <li class="text-sm">
                                        <x-package-chip :package="$relation['package']" />
                                        <p class="mt-1 text-zinc-600 dark:text-zinc-400">{{ $relation['text'] }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($sync = $package->sync())
                <p class="text-xs text-zinc-500">
                    @if ($sync['local'] ?? false)
                        {{-- `site:sync --from` reads a working tree, which has no commit to link. --}}
                        Content from a local checkout of <span class="font-mono">{{ $sync['repo'] }}</span>, not a pinned commit.
                    @else
                        Content from <a href="https://github.com/{{ $sync['repo'] }}/tree/{{ $sync['commit'] }}" class="font-mono underline decoration-zinc-300 underline-offset-2 hover:text-zinc-800 dark:hover:text-zinc-200">{{ $sync['repo'].'@'.substr($sync['commit'], 0, 7) }}</a>.
                    @endif
                </p>
            @endif
        </aside>
        @endif
    </div>
</x-layout>
