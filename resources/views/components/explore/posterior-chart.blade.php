{{--
    Posterior medians and 80% intervals of the school intercepts from the
    committed CmdStan fit in database/data/stan/school-intercepts.json.
--}}
@php
    $fit = json_decode(file_get_contents(database_path('data/stan/school-intercepts.json')), true);
    $quantile = function (array $draws, float $p): float {
        sort($draws);
        $h = (count($draws) - 1) * $p;

        return $draws[(int) floor($h)] + ($h - floor($h)) * ($draws[(int) ceil($h)] - $draws[(int) floor($h)]);
    };
    $rows = [];
    foreach ($fit['draws'] as $name => $draws) {
        if (preg_match('/^school_intercept\[(\d+)\]$/', $name, $m)) {
            $rows[] = ['school' => sprintf('School %02d', $m[1]), 'median' => $quantile($draws, 0.5), 'lower' => $quantile($draws, 0.1), 'upper' => $quantile($draws, 0.9)];
        }
    }
    usort($rows, fn ($a, $b) => $a['median'] <=> $b['median']);
    $plot = \Phiz\Plot::of($rows)
        ->aes(x: 'median', y: 'school', xmin: 'lower', xmax: 'upper')
        ->pointrange(color: '#a855f7', linewidth: 2)
        ->scaleY(domain: array_column($rows, 'school'))
        ->labs(x: 'School intercept (posterior median and 80% interval)', y: null)
        ->width('container')
        ->height(max(160, 16 * count($rows)));
@endphp
<div class="not-prose rounded-xl bg-white p-4 ring-1 ring-zinc-200 dark:bg-white/[0.03] dark:ring-white/10">
    <x-phiz :plot="$plot" class="w-full" />
    <p class="mt-2 text-xs text-zinc-500">{{ $fit['meta']['chains'] }} chains, CmdStan {{ $fit['meta']['cmdstan'] }}, model <code class="font-mono">{{ $fit['meta']['formula'] }}</code>.</p>
</div>
