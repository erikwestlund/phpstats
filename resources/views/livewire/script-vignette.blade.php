<div>
    @if ($inputs)
        <div class="flex flex-wrap items-end gap-6 text-sm">
            @foreach ($inputs as $name => $spec)
                @switch($spec['type'] ?? 'text')
                    @case('select')
                        <label class="flex flex-col gap-2">
                            <span class="font-medium">{{ $spec['label'] ?? $name }}</span>
                            <select wire:model.live="input.{{ $name }}" class="rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
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
                                        <input type="radio" name="{{ $name }}" value="{{ $value }}" wire:model.live="input.{{ $name }}"> {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        @break
                    @case('checkbox')
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="input.{{ $name }}"> {{ $spec['label'] ?? $name }}
                        </label>
                        @break
                    @case('number')
                        <label class="flex flex-col gap-2">
                            <span class="font-medium">{{ $spec['label'] ?? $name }}</span>
                            <input type="number" wire:model.live.debounce.400ms="input.{{ $name }}"
                                   @isset($spec['min']) min="{{ $spec['min'] }}" @endisset
                                   @isset($spec['max']) max="{{ $spec['max'] }}" @endisset
                                   @isset($spec['step']) step="{{ $spec['step'] }}" @endisset
                                   class="w-28 rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-900">
                        </label>
                        @break
                @endswitch
            @endforeach
            <span class="text-zinc-500" wire:loading>Running…</span>
        </div>
    @endif

    @if ($missing)
        <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-200">
            This vignette needs {{ implode(' and ', $missing) }}, which this server does not have, so it cannot run here.
        </p>
    @elseif ($error)
        <p class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-900 dark:bg-red-950 dark:text-red-200">{{ $error }}</p>
    @endif

    <div class="mt-6 space-y-6">
        @foreach ($blocks as $i => $block)
            <x-vignette-block :block="$block" :key="$slug.'-'.$i" />
        @endforeach
    </div>
</div>
