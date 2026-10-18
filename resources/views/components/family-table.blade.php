{{-- Every package with its layer and the R packages it corresponds to. --}}
<div class="not-prose overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:ring-white/10">
    <table class="w-full text-left text-sm">
        <thead class="bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:bg-white/[0.03] dark:text-zinc-400">
            <tr>
                <th class="px-4 py-2.5 font-semibold">Package</th>
                <th class="px-4 py-2.5 font-semibold">Does</th>
                <th class="hidden px-4 py-2.5 font-semibold sm:table-cell">In R</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
            @foreach (config('phpstats.layers') as $layer)
                @foreach ($layer['packages'] as $key => $description)
                    @php $package = \App\Content\Package::find($key); @endphp
                    <tr>
                        <td class="px-4 py-3 align-top"><x-package-chip :package="$package" /></td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $description }}</td>
                        <td class="hidden px-4 py-3 align-top font-mono text-xs text-zinc-500 sm:table-cell dark:text-zinc-400">{{ $package->project['from_r'] ?? '' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
