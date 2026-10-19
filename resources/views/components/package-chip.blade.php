{{-- A link to another package's host, colored by its layer. --}}
@props(['package'])

@php $package = is_string($package) ? \App\Content\Package::find($package) : $package; @endphp
@if ($package)
    <a href="{{ $package->url() }}" {{ $attributes->class([
        'layer-'.$package->layer()['color'],
        'inline-flex items-center gap-1.5 rounded-full font-mono bg-(--l50) px-2.5 py-0.5 text-xs font-medium text-(--l700) ring-1 ring-(--l200) ring-inset transition hover:bg-(--l100) dark:bg-(--l400)/10 dark:text-(--l300) dark:ring-(--l400)/20 dark:hover:bg-(--l400)/20',
    ]) }}>{{ $package->name() }}</a>
@endif
