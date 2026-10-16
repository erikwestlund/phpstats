@props(['project'])

<span @class([
    'inline-block rounded-full px-2 py-0.5 text-xs font-medium',
    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => in_array($project['status'], ['working', 'exists']),
    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' => $project['status'] === 'in-progress',
    'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $project['status'] === 'experimental',
])>{{ $project['status_label'] }}</span>
