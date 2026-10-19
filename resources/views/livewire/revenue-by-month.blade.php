<div>
    <select wire:model.live="channel" class="rounded-md border border-zinc-300 px-2 py-1 text-sm">
        <option>All</option>
        <option>Web</option>
        <option>App</option>
        <option>Marketplace</option>
    </select>

    <x-phiz :plot="$chart->width('container')->height(260)" />
</div>
