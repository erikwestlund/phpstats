{{--
    The landing page's package explorer: the packages by category on the
    left, and for the selected one, a few points about it, its example code, and the
    example's output on the right. The points are inline Markdown, for links to
    upstream projects and `code`. Code is resources/examples/packages/{key}.php,
    run when the page renders, or, for examples pages can't run, the stored
    result named in config/phpstats.php explorer.
--}}
@php
    $packages = \App\Content\Package::all();
    $explorer = config('phpstats.explorer');
    // Layer order, less the packages with no explorer entry, such as juphyter.
    $keys = array_values(array_intersect(
        array_keys(array_merge(...array_column(config('phpstats.layers'), 'packages'))),
        array_keys($explorer),
    ));
@endphp
<div x-data="{ open: 'phibble', keys: @js($keys), move(step) { const i = (this.keys.indexOf(this.open) + step + this.keys.length) % this.keys.length; this.open = this.keys[i]; this.$nextTick(() => document.getElementById('package-tab-' + this.open).focus()); } }"
     class="overflow-hidden rounded-2xl ring-1 ring-zinc-200 lg:grid lg:grid-cols-[2fr_3fr] dark:ring-white/10">
    <div class="divide-y divide-zinc-200 border-b border-zinc-200 lg:border-b-0 lg:border-r dark:divide-white/10 dark:border-white/10" x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)">
        @foreach (config('phpstats.layers') as $layer)
            @php $tabs = array_values(array_intersect(array_keys($layer['packages']), $keys)); @endphp
            @continue ($tabs === [])
            <div class="layer-{{ $layer['color'] }} pt-5">
                <h3 class="mb-3 flex items-center gap-2.5 px-6 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    <x-dynamic-component :component="'heroicon-o-'.$layer['icon']" class="size-5 text-(--l500) dark:text-(--l300)" />
                    {{ $layer['title'] }}
                </h3>
                <div role="tablist" aria-label="{{ $layer['title'] }}" aria-orientation="vertical">
                    @foreach ($tabs as $key)
                        <button type="button" role="tab" id="package-tab-{{ $key }}" aria-controls="package-code-{{ $key }}"
                                x-on:click="open = '{{ $key }}'" x-bind:aria-selected="(open === '{{ $key }}').toString()" aria-selected="{{ $key === 'phibble' ? 'true' : 'false' }}"
                                class="block w-full border-l-[3px] border-transparent py-3 pl-[calc(1.5rem-3px)] pr-6 text-left transition hover:bg-zinc-50 focus-visible:bg-zinc-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-(--l500) aria-selected:border-(--l500) aria-selected:bg-(--l50) dark:hover:bg-white/[0.03] dark:focus-visible:bg-white/[0.03] dark:aria-selected:border-(--l400) dark:aria-selected:bg-(--l400)/10">
                            <span class="font-mono text-base font-semibold text-zinc-950 dark:text-white">{{ $packages[$key]->name() }}</span>
                            <span class="mt-1.5 block text-[0.9375rem] leading-normal text-zinc-600 dark:text-zinc-400">{{ $explorer[$key]['summary'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="min-w-0">
        @foreach ($keys as $key)
            @php
                $package = $packages[$key];
                // juphyter's example is a Quarto document rather than a
                // PHP script, so an entry may name its own file and language.
                $source = $explorer[$key]['source'] ?? null;
                $code = $source === null
                    ? \App\Content\Example::code("packages/{$key}")
                    : ['pipe' => file_get_contents(resource_path('examples/'.$source['file'])), 'chain' => null];
                $stored = $explorer[$key]['stored'] ?? null;
                $label = $explorer[$key]['output'];
                // Example::output keys its cache on the .php example's mtime,
                // which a source entry has none of, so its own file's mtime
                // goes in the variant. Re-capturing a stored result without
                // touching the document needs `php artisan examples:clear`.
                $variant = 'package-output|'.$label.($source === null ? '' : '|'.filemtime(resource_path('examples/'.$source['file'])));
                try {
                    $output = \App\Content\Example::output(
                        "packages/{$key}",
                        $variant,
                        fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => $label])->render(),
                        $stored ? fn () => $stored() : null,
                    );
                    $failed = false;
                } catch (\Throwable $e) {
                    report($e);
                    $output = null;
                    $failed = true;
                }
            @endphp
            <div role="tabpanel" id="package-code-{{ $key }}" aria-labelledby="package-tab-{{ $key }}" x-show="open === '{{ $key }}'" @unless ($key === 'phibble') x-cloak @endunless class="flex h-full flex-col">
                <section class="border-b border-zinc-200 dark:border-white/10">
                    <h4 class="flex min-h-14 items-center gap-[0.3em] border-b border-zinc-200 px-5 py-3 text-lg font-semibold tracking-tight text-zinc-950 sm:px-7 dark:border-white/10 dark:text-white">About <span class="font-mono">{{ $package->name() }}</span></h4>
                    <ul class="list-disc space-y-1.5 py-5 pl-10 pr-5 text-[0.9375rem] leading-normal text-zinc-700 marker:text-zinc-400 sm:pl-12 sm:pr-7 dark:text-zinc-300 [&_a]:font-medium [&_a]:text-php-600 [&_a]:underline [&_a]:decoration-php-300 [&_a]:underline-offset-2 [&_a]:hover:text-php-500 [&_a.package-link]:font-mono [&_a.package-link]:text-[0.9375em] [&_a.package-link]:font-bold [&_a.package-link]:text-php-700 [&_a.package-link]:no-underline [&_a.package-link]:hover:underline [&_code]:rounded [&_code]:bg-zinc-100 [&_code]:px-1 [&_code]:py-px [&_code]:font-mono [&_code]:text-[0.875em] [&_code]:text-zinc-800 dark:[&_a]:text-php-300 dark:[&_a]:decoration-php-300/40 dark:[&_a]:hover:text-php-200 dark:[&_a.package-link]:text-php-200 dark:[&_code]:bg-white/[0.06] dark:[&_code]:text-zinc-200">
                        @foreach ($explorer[$key]['does'] as $point)
                            <li>{!! \App\Content\Markdown::inline($point) !!}</li>
                        @endforeach
                    </ul>
                </section>
                <x-code-tabs :pipe="$code['pipe']" :chain="$code['chain']" :language="$source['language'] ?? 'php'" :label="$explorer[$key]['example']" :spacious="true" class="rounded-none! shadow-none! ring-0!" />
                <section class="flex flex-1 flex-col bg-zinc-50/70 dark:bg-white/[0.02]">
                    <h4 class="flex min-h-14 items-center border-b border-zinc-200 px-5 py-3 text-base font-semibold sm:px-7 text-zinc-950 dark:border-white/10 dark:text-white">Example output</h4>
                    @if ($failed)
                        <p class="p-5 text-sm text-zinc-500 sm:p-7">This server can't run the example, so its output isn't shown.</p>
                    @else
                        {!! $output !!}
                    @endif
                    <a href="{{ $package->url() }}" class="mt-auto inline-flex items-center gap-1.5 border-t border-zinc-200 px-5 py-4 text-[0.9375rem] sm:px-7 font-medium text-php-600 hover:text-php-500 dark:border-white/10 dark:text-php-300 dark:hover:text-php-200">
                        More about <span class="font-mono">{{ $package->name() }}</span>
                        <x-heroicon-m-arrow-right class="size-4" />
                    </a>
                </section>
            </div>
        @endforeach
    </div>
</div>
