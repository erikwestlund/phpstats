{{--
    The page frame. Package hosts get the package bar; the landing host gets
    the site bar. bare drops the content container, for full-width pages.
--}}
@props(['title' => null, 'description' => null, 'wide' => false, 'bare' => false])

@php
    $current = $currentPackage ?? null;
    $heading = collect([$title, $current?->name(), 'PHPstats'])->filter()->unique()->implode(' · ');

    // The description: the one the page gave, or, on a package host, its
    // tagline, with the page title in front of it on docs and the sections.
    $sentence = fn (string $text) => rtrim($text, ' .').'.';
    $summary = $description
        ?? ($current === null
            ? 'A family of PHP libraries for data frames, pipelines, charts, and statistical models, run inside the application that holds the data.'
            : trim(($title ? $sentence("{$title} in {$current->name()}") : '').' '.$sentence($current->tagline())));
    $summary = \Illuminate\Support\Str::limit($summary, 160, preserveWords: true);

    // Search engines and social cards want one address per page, without the query string.
    $canonical = url()->current();
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
    <meta name="description" content="{{ $summary }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="PHPstats">
    <meta property="og:title" content="{{ $heading }}">
    <meta property="og:description" content="{{ $summary }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $heading }}">
    <meta name="twitter:description" content="{{ $summary }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script>
        // Set the color scheme before the page paints: the stored choice, or the system setting.
        (function () {
            var choice = null;
            try { choice = localStorage.getItem('phpstats-theme'); } catch (e) {}
            var dark = choice ? choice === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400,500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body @class(['flex min-h-screen flex-col bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100', 'layer-'.$current?->layer()['color'] => $current])>
    @if ($current)
        <x-package-header :package="$current" />
    @else
        <x-site-header />
    @endif

    <main @class(['flex-1', 'mx-auto w-full px-4 py-10 sm:px-6 lg:px-8' => ! $bare, 'max-w-[88rem]' => $wide && ! $bare, 'max-w-5xl' => ! $wide && ! $bare])>
        {{ $slot }}
    </main>

    <x-site-footer />

    {{-- phiz's loader, which draws every [data-phiz] container: the charts in
         <x-phiz>, and those in the packages' synced docs, whose own loader the
         importer strips. config/phiz.php turns <x-phiz>'s injection off so this
         is the only copy, and assets is 'none' because resources/js/app.js
         bundles vega and wraps vegaEmbed for dark mode. --}}
    @phizScripts

    @livewireScriptConfig
</body>
</html>
