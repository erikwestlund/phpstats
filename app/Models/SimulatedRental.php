<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulatedRental extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'area_m2' => 'float',
            'bedrooms' => 'integer',
            'monthly_rent' => 'float',
        ];
    }
}
