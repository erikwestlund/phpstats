{{-- A PHPstats package's name, in the monospace font, linked to its overview on the current host. --}}
@props(['package'])
@php $package = \App\Content\Package::find($package); @endphp
<a href="{{ $package->url() }}" {{ $attributes->merge(['class' => 'font-mono']) }}>{{ $package->name() }}</a>
