@props(['title' => null])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "{$title} · PHP Stats" : 'PHP Stats' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <header class="border-b border-zinc-200 dark:border-zinc-800">
        <nav class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-4 text-sm">
            <a href="{{ route('home') }}" class="font-semibold">PHP Stats</a>
            @foreach (config('projects') as $key => $item)
                <a href="{{ route('project', $key) }}"
                   @class(['text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100', 'font-medium text-zinc-900! dark:text-zinc-100!' => request()->is($key)])>
                    {{ $item['name'] }}
                </a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-10">
        {{ $slot }}
    </main>

    @livewireScriptConfig
</body>
</html>
