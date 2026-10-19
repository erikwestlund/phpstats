<?php

namespace Database\Seeders;

use App\Data\Orders;
use App\Data\SimulatedRentals;
use App\Data\Tracks;
use App\Models\HousingRental;
use App\Models\Order;
use App\Models\SimulatedRental;
use App\Models\StudentScore;
use App\Models\Track;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Load the phit example datasets from database/data and the simulated datasets.
     */
    public function run(): void
    {
        HousingRental::query()->delete();
        HousingRental::insert($this->readCsv('housing.csv'));

        StudentScore::query()->delete();
        StudentScore::insert($this->readCsv('schools.csv'));

        SimulatedRental::query()->delete();
        foreach (array_chunk(SimulatedRentals::generate(), 500) as $chunk) {
            SimulatedRental::insert($chunk);
        }

        Order::query()->delete();
        foreach (array_chunk(Orders::generate(), 500) as $chunk) {
            Order::insert($chunk);
        }

        Track::query()->delete();
        foreach (array_chunk(Tracks::generate(), 500) as $chunk) {
            Track::insert($chunk);
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(string $name): array
    {
        $handle = fopen(database_path("data/{$name}"), 'r');
        $header = fgetcsv($handle, escape: '');
        $rows = [];

        while (($values = fgetcsv($handle, escape: '')) !== false) {
            $rows[] = array_combine($header, $values);
        }

        fclose($handle);

        return $rows;
    }
}
