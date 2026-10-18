<div>
    @if ($inputs)
        <div class="flex flex-wrap items-end gap-6 rounded-2xl bg-zinc-50 p-5 text-sm ring-1 ring-zinc-200 dark:bg-white/[0.03] dark:ring-white/10">
            <p class="flex w-full items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-500"><x-heroicon-m-adjustments-horizontal class="size-4 text-(--l500)" /> Inputs</p>
            @foreach ($inputs as $name => $spec)
                @switch($spec['type'] ?? 'text')
                    @case('select')
                        <label class="flex flex-col gap-2">
                            <span class="font-medium">{{ $spec['label'] ?? $name }}</span>
                            <select wire:model.live="input.{{ $name }}" class="rounded-lg border-0 bg-white px-3 py-1.5 shadow-sm ring-1 ring-zinc-300 focus:ring-2 focus:ring-(--l500) focus:outline-none dark:bg-zinc-900 dark:ring-white/10">
                                @foreach ($spec['options'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        @break
                    @case('radio')
                        <fieldset class="flex flex-col gap-2">
                            <legend class="mb-2 font-medium">{{ $spec['label'] ?? $name }}</legend>
                            <div class="flex flex-wrap gap-4">
                                @foreach ($spec['options'] as $value => $label)
                                    <label class="flex items-center gap-2">
                                        <input type="radio" class="accent-(--l600)" name="{{ $name }}" value="{{ $value }}" wire:model.live="input.{{ $name }}"> {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @break
                    @case('checkbox')
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="accent-(--l600)" wire:model.live="input.{{ $name }}"> {{ $spec['label'] ?? $name }}
                        </label>
                        @break
                    @case('number')
                        <label class="flex flex-col gap-2">
                            <span class="font-medium">{{ $spec['label'] ?? $name }}</span>
                            <input type="number" wire:model.live.debounce.400ms="input.{{ $name }}"
                                   @isset($spec['min']) min="{{ $spec['min'] }}" @endisset
                                   @isset($spec['max']) max="{{ $spec['max'] }}" @endisset
                                   @isset($spec['step']) step="{{ $spec['step'] }}" @endisset
                                   class="w-28 rounded-lg border-0 bg-white px-3 py-1.5 shadow-sm ring-1 ring-zinc-300 focus:ring-2 focus:ring-(--l500) focus:outline-none dark:bg-zinc-900 dark:ring-white/10">
                        </label>
                        @break
                @endswitch
            @endforeach
            <span class="ml-auto flex items-center gap-2 text-(--l600) dark:text-(--l300)" wire:loading><x-heroicon-m-arrow-path class="size-4 animate-spin" /> Running</span>
        </div>
    @endif

    @if ($missing)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This vignette needs {{ implode(' and ', $missing) }}, which this server does not have, so it cannot run here.
        </p>
    @elseif ($error)
        <p class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-900 dark:bg-red-950 dark:text-red-200">{{ $error }}</p>
    @endif

    <div class="mt-8 space-y-8">
        @foreach ($blocks as $i => $block)
            <x-vignette-block :block="$block" :key="$slug.'-'.$i" />
        @endforeach
    </div>
</div>
