@props(['project'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
    'bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20' => in_array($project['status'], ['working', 'exists']),
    'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20' => $project['status'] === 'in-progress',
    'bg-zinc-100 text-zinc-700 ring-zinc-500/15 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700' => $project['status'] === 'experimental',
]) }}><span @class([
    'size-1.5 rounded-full',
    'bg-emerald-500' => in_array($project['status'], ['working', 'exists']),
    'bg-amber-500' => $project['status'] === 'in-progress',
    'bg-zinc-400' => $project['status'] === 'experimental',
])></span>{{ $project['status_label'] }}</span>
