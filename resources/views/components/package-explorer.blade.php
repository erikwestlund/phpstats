{{--
    The landing page's package explorer: the packages by category on the
    left, and the selected package's code and its output on the right. Code
    is resources/examples/packages/{key}.php, run when the page renders, or,
    for examples pages can't run, the stored result named in
    config/phpstats.php explorer.
--}}
@php
    $packages = \App\Content\Package::all();
    $explorer = config('phpstats.explorer');
    $keys = array_keys(array_merge(...array_column(config('phpstats.layers'), 'packages')));
@endphp
<div x-data="{ open: 'phibble', keys: @js($keys), move(step) { const i = (this.keys.indexOf(this.open) + step + this.keys.length) % this.keys.length; this.open = this.keys[i]; this.$nextTick(() => document.getElementById('package-tab-' + this.open).focus()); } }"
     class="overflow-hidden rounded-2xl ring-1 ring-zinc-200 lg:grid lg:grid-cols-[45fr_55fr] dark:ring-white/10">
    <div class="border-b border-zinc-200 p-3 sm:p-4 lg:border-b-0 lg:border-r dark:border-white/10" x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)">
        @foreach (config('phpstats.layers') as $layer)
            <div class="layer-{{ $layer['color'] }} @unless ($loop->first) mt-4 @endunless">
                <h2 class="flex items-center gap-2 px-3 pb-1.5 pt-1 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    <x-dynamic-component :component="'heroicon-o-'.$layer['icon']" class="size-4 text-(--l500) dark:text-(--l300)" />
                    {{ $layer['title'] }}
                </h2>
                <div role="tablist" aria-label="{{ $layer['title'] }}" aria-orientation="vertical" class="space-y-0.5">
                    @foreach (array_keys($layer['packages']) as $key)
                        <button type="button" role="tab" id="package-tab-{{ $key }}" aria-controls="package-code-{{ $key }}"
                                x-on:click="open = '{{ $key }}'" x-bind:aria-selected="(open === '{{ $key }}').toString()" aria-selected="{{ $key === 'phibble' ? 'true' : 'false' }}"
                                class="block w-full rounded-lg border-l-2 border-transparent px-3 py-2 text-left transition hover:bg-zinc-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-(--l500) aria-selected:border-(--l500) aria-selected:bg-(--l50) dark:hover:bg-white/[0.03] dark:aria-selected:border-(--l400) dark:aria-selected:bg-(--l400)/10">
                            <span class="font-mono text-sm font-semibold text-zinc-950 dark:text-white">{{ $packages[$key]->name() }}</span>
                            <span class="mt-0.5 block text-sm leading-[1.375rem] text-zinc-600 dark:text-zinc-400">{{ $explorer[$key]['description'] }}</span>
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
                $code = \App\Content\Example::code("packages/{$key}");
                $stored = $explorer[$key]['stored'] ?? null;
                try {
                    $result = $stored ? $stored() : \App\Content\Example::run("packages/{$key}");
                    $failed = false;
                } catch (\Throwable $e) {
                    report($e);
                    $result = null;
                    $failed = true;
                }
            @endphp
            <div role="tabpanel" id="package-code-{{ $key }}" aria-labelledby="package-tab-{{ $key }}" x-show="open === '{{ $key }}'" @unless ($key === 'phibble') x-cloak @endunless class="flex h-full flex-col">
                <x-code-tabs :pipe="$code['pipe']" :chain="$code['chain']" :label="$package->name()" :mono-label="true" class="rounded-none! shadow-none! ring-0!" />
                <div class="flex flex-1 flex-col bg-zinc-50/70 dark:bg-white/[0.02]">
                    <p class="border-b border-zinc-200 px-4 py-1.5 text-[0.7rem] font-semibold uppercase tracking-wider text-zinc-400 dark:border-white/10">{{ $explorer[$key]['output'] }}</p>
                    @if ($failed)
                        <p class="px-4 py-3 text-sm text-zinc-500">This server can't run the example, so its output isn't shown.</p>
                    @else
                        <x-package-output :value="$result" />
                    @endif
                    @isset ($explorer[$key]['note'])
                        <p class="px-4 pb-2 text-xs text-zinc-500">{{ $explorer[$key]['note'] }}</p>
                    @endisset
                    <a href="{{ $package->url() }}" class="mt-auto inline-flex items-center gap-1 border-t border-zinc-200 px-4 py-2.5 text-sm font-medium text-php-600 hover:text-php-500 dark:border-white/10 dark:text-php-300 dark:hover:text-php-200">
                        More about <span class="font-mono">{{ $package->name() }}</span>
                        <x-heroicon-m-arrow-right class="size-4" />
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
