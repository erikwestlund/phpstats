<?php

namespace App\Livewire;

use App\Models\SimulatedRental;
use Closure;
use Livewire\Component;
use Phibble\Contracts\Table;
use Phibble\Frame;

use function Phplyr\{arrange, col, desc, filter, group_by, mean, mutate, n, summarise};

/**
 * Vignette: one phplyr pipeline run on an in-memory phibble Frame and on a
 * Polars-backed PolarsTable. The Polars side needs the php_polars extension
 * and a phibble release with Phibble\Polars; without them the page runs the
 * Frame side only.
 */
class PolarsPipeline extends Component
{
    /** Copies of the 3,000 simulated rentals stacked into one table. */
    public const COPIES = [1, 10, 100];

    public const POLARS_TABLE = 'Phibble\Polars\PolarsTable';

    public int $copies = 10;

    public int $minBedrooms = 2;

    public static function polarsAvailable(): bool
    {
        return extension_loaded('php_polars') && class_exists(self::POLARS_TABLE);
    }

    public function render()
    {
        $copies = in_array($this->copies, self::COPIES, true) ? $this->copies : 1;
        $min = in_array($this->minBedrooms, SqlPipeline::BEDROOMS, true) ? $this->minBedrooms : 1;

        $base = SimulatedRental::query()->select(['neighborhood', 'area_m2', 'bedrooms', 'monthly_rent'])->toFrame();
        $frame = $copies === 1 ? $base : Frame::concat(array_fill(0, $copies, $base));
        $pipeline = $this->pipeline($min);

        [$frameRows, $frameSeconds] = $this->timed(fn () => ($frame |> $pipeline)->toRows());

        $polars = null;
        if (self::polarsAvailable()) {
            [$table, $loadSeconds] = $this->timed(fn () => (self::POLARS_TABLE)::fromTable($frame));
            [$rows, $seconds] = $this->timed(fn () => ($table |> $pipeline)->toRows());
            $polars = [
                'rows' => $rows,
                'load_seconds' => $loadSeconds,
                'seconds' => $seconds,
                'max_difference' => $this->maxDifference($frameRows, $rows),
                'version' => phpversion('php_polars'),
            ];
        }

        return view('livewire.polars-pipeline', [
            'available' => self::polarsAvailable(),
            'extensionLoaded' => extension_loaded('php_polars'),
            'rowsIn' => $frame->nrow(),
            'frameRows' => $frameRows,
            'frameSeconds' => $frameSeconds,
            'polars' => $polars,
        ]);
    }

    /** The pipeline from the SQL vignette, written with col() expressions that Polars can run. */
    private function pipeline(int $minBedrooms): Closure
    {
        return fn (Table $rentals) => $rentals
            |> filter(col('bedrooms')->ge($minBedrooms))
            |> mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2')))
            |> group_by('neighborhood')
            |> summarise(rentals: n(), mean_rent_per_m2: mean('rent_per_m2'))
            |> arrange(desc('mean_rent_per_m2'));
    }

    /** @return array{0: mixed, 1: float} The result and the elapsed seconds. */
    private function timed(Closure $work): array
    {
        $start = hrtime(true);
        $result = $work();

        return [$result, (hrtime(true) - $start) / 1e9];
    }

    /**
     * Largest absolute difference between the two results, or null when the
     * rows or neighborhoods do not line up.
     */
    private function maxDifference(array $a, array $b): ?float
    {
        if (count($a) !== count($b)) {
            return null;
        }

        $max = 0.0;
        foreach ($a as $i => $row) {
            if ($row['neighborhood'] !== $b[$i]['neighborhood'] || $row['rentals'] !== $b[$i]['rentals']) {
                return null;
            }
            $max = max($max, abs($row['mean_rent_per_m2'] - $b[$i]['mean_rent_per_m2']));
        }

        return $max;
    }
}
