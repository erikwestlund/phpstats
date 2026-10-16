<?php

namespace App\Livewire;

use App\Models\SimulatedRental;
use Livewire\Component;
use Phibble\Contracts\Table;

use function Phplyr\{arrange, col, desc, filter, group_by, mean, mutate, n, show_query, summarise};

/**
 * Vignette: one phplyr pipeline run two ways. On a lazy phibble SqlTable the
 * verbs compile to a single SQL query; on an in-memory Frame they run in PHP.
 */
class SqlPipeline extends Component
{
    public const BEDROOMS = [1, 2, 3, 4];

    public int $minBedrooms = 2;

    public function render()
    {
        $min = in_array($this->minBedrooms, self::BEDROOMS, true) ? $this->minBedrooms : 1;

        $sql = SimulatedRental::query()->phibble() |> $this->pipeline($min);
        $memory = SimulatedRental::query()->toFrame() |> $this->pipeline($min);

        $sqlRows = $sql->toRows();
        $memoryRows = $memory->toRows();

        return view('livewire.sql-pipeline', [
            'query' => $sql |> show_query(),
            'bindings' => $sql->bindings(),
            'sqlRows' => $sqlRows,
            'memoryRows' => $memoryRows,
            'maxDifference' => $this->maxDifference($sqlRows, $memoryRows),
        ]);
    }

    /**
     * The pipeline shown on the page. Column expressions use col() rather than
     * PHP closures, so a SqlTable can translate them to SQL.
     */
    private function pipeline(int $minBedrooms): \Closure
    {
        return fn (Table $rentals) => $rentals
            |> filter(col('bedrooms')->ge($minBedrooms))
            |> mutate(rent_per_m2: col('monthly_rent')->div(col('area_m2')))
            |> group_by('neighborhood')
            |> summarise(rentals: n(), mean_rent_per_m2: mean('rent_per_m2'))
            |> arrange(desc('mean_rent_per_m2'));
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
