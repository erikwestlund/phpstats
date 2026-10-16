<x-layout :title="$project['name']">
    <article class="max-w-3xl">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $project['name'] }}</h1>
            <x-status :project="$project" />
        </div>
        <p class="mt-4 text-lg text-zinc-700 dark:text-zinc-300">{{ $project['summary'] }}</p>

        @foreach ($project['overview'] as $paragraph)
            <p class="mt-4 text-zinc-700 dark:text-zinc-300">{{ $paragraph }}</p>
        @endforeach

        @if ($project['repo'] || $project['docs'])
            <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                @if ($project['repo'])
                    <li><a href="{{ $project['repo'] }}" class="underline">Repository</a></li>
                @endif
                @if ($project['docs'])
                    <li><a href="{{ $project['docs'] }}" class="underline">Documentation</a></li>
                @endif
                @foreach ($project['links'] ?? [] as $label => $url)
                    <li><a href="{{ $url }}" class="underline">{{ $label }}</a></li>
                @endforeach
            </ul>
        @else
            <p class="mt-8 text-sm text-zinc-500">The repository is not public yet.</p>
        @endif
    </article>
</x-layout>
