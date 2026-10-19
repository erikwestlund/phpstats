{{--
    The page frame. Package hosts get the package bar; the landing host gets
    the site bar. bare drops the content container, for full-width pages.
--}}
@props(['title' => null, 'wide' => false, 'bare' => false])

@php $current = $currentPackage ?? null; @endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ collect([$title, $current?->name(), 'PHPstats'])->filter()->unique()->implode(' · ') }}</title>
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

    @livewireScriptConfig
</body>
</html>
