@props(['title' => null, 'wide' => false])

@php $current = $currentPackage ?? null; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ collect([$title, $current?->name(), 'PHPstats'])->filter()->unique()->implode(' · ') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <header class="border-b border-zinc-200 dark:border-zinc-800">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-4 text-sm">
            <a href="{{ route('home') }}" class="font-semibold">PHPstats</a>
            @foreach (\App\Content\Package::all() as $item)
                <a href="{{ $item->url() }}"
                   @class(['text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100', 'font-medium text-zinc-900! dark:text-zinc-100!' => $current?->key === $item->key])>
                    {{ $item->name() }}
                </a>
            @endforeach
        </nav>
        @if ($current)
            <x-package-tabs :package="$current" />
        @endif
    </header>

    <main @class(['mx-auto px-4 py-10', 'max-w-6xl' => $wide, 'max-w-5xl' => ! $wide])>
        {{ $slot }}
    </main>

    @livewireScriptConfig
</body>
</html>
