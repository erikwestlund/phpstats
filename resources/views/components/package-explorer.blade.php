{{--
    The landing page's package explorer: the packages by category on the
    left, each with its one-line summary, and for the selected one, its
    example code, the example's output, and a footer row linking
    the fuller example in its docs, on the right. Code is resources/examples/packages/{key}.php,
    run when the page renders, and its result is the output. An entry in
    config/phpstats.php explorer may instead name a source file and the
    language to highlight it as, for an example that is not a PHP script, such
    as juphyter's Quarto document; and a stored result, for output a page
    cannot compute, such as a Stan fit or a cell a kernel rendered.
--}}
@php
    $packages = \App\Content\Package::all();
    $explorer = config('phpstats.explorer');
    // Layer order, less any package with no explorer entry, which gets no panel.
    $keys = array_values(array_intersect(
        array_merge(...array_column(config('phpstats.layers'), 'packages')),
        array_keys($explorer),
    ));
@endphp
<div x-data="{ open: 'phibble', keys: @js($keys), move(step) { const i = (this.keys.indexOf(this.open) + step + this.keys.length) % this.keys.length; this.open = this.keys[i]; this.$nextTick(() => document.getElementById('package-tab-' + this.open).focus()); } }"
     class="overflow-hidden rounded-2xl ring-1 ring-zinc-200 lg:grid lg:grid-cols-[2fr_3fr] dark:ring-white/10">
    <div class="divide-y divide-zinc-200 border-b border-zinc-200 lg:border-b-0 lg:border-r dark:divide-white/10 dark:border-white/10" x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)">
        @foreach (config('phpstats.layers') as $layer)
            @php $tabs = array_values(array_intersect($layer['packages'], $keys)); @endphp
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
                            <span class="mt-1.5 block text-[0.9375rem] leading-normal text-zinc-600 dark:text-zinc-400">{{ $explorer[$key]['tasks'] ?? $packages[$key]->tagline() }}</span>
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
                // The files behind the output, besides the .php example that
                // Example::output already follows: a document rendered in
                // place of a script, and what a stored result is read from.
                // Their mtimes go in the key, so re-capturing one is served
                // without `php artisan examples:clear`.
                $files = array_values(array_filter([
                    $source === null ? null : resource_path('examples/'.$source['file']),
                    ...($stored['files'] ?? []),
                ]));
                try {
                    $output = \App\Content\Example::output(
                        "packages/{$key}",
                        'package-output|'.$label,
                        fn (mixed $value) => view('components.package-output', ['value' => $value, 'label' => $label])->render(),
                        $stored === null ? null : fn () => ($stored['result'])(),
                        $files,
                    );
                    $failed = false;
                } catch (\Throwable $e) {
                    report($e);
                    $output = null;
                    $failed = true;
                }
            @endphp
            <div role="tabpanel" id="package-code-{{ $key }}" aria-labelledby="package-tab-{{ $key }}" x-show="open === '{{ $key }}'" @unless ($key === 'phibble') x-cloak @endunless class="flex h-full flex-col">
                <x-code-tabs :pipe="$code['pipe']" :chain="$code['chain']" :language="$source['language'] ?? 'php'" :label="$explorer[$key]['example']" :spacious="true" :hanging="true" class="rounded-none! shadow-none! ring-0!" />
                <section class="flex flex-1 flex-col bg-zinc-50/70 dark:bg-white/[0.02]">
                    <h4 class="flex min-h-14 items-center border-b border-zinc-200 px-5 py-3 text-base font-semibold sm:px-7 text-zinc-950 dark:border-white/10 dark:text-white">Example output</h4>
                    @if ($failed)
                        <p class="p-5 text-sm text-zinc-500 sm:p-7">This server can't run the example, so its output isn't shown.</p>
                    @else
                        {!! $output !!}
                    @endif
                </section>
                @php
                    // A docs path, with a #fragment when the fuller example is one section of a page.
                    [$path, $fragment] = array_pad(explode('#', $explorer[$key]['link'] ?? '', 2), 2, null);
                    $full = in_array($path, $package->docsPaths(), true);
                    $text = $full ? 'View full example' : 'View documentation';
                @endphp
                <div class="border-t border-zinc-200 bg-zinc-50/70 px-5 py-3 sm:px-7 dark:border-white/10 dark:bg-white/[0.02]">
                    <a href="{{ $full ? $package->url('docs', ['path' => $path]).($fragment ? "#{$fragment}" : '') : $package->url('docs') }}" aria-label="{{ $text }} for {{ $package->name() }}" class="inline-flex items-center gap-1.5 text-[0.9375rem] font-medium text-php-600 hover:text-php-500 dark:text-php-300 dark:hover:text-php-200">
                        {{ $text }}
                        <x-heroicon-m-arrow-right class="size-4" />
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
