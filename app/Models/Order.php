<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'placed_on' => 'date:Y-m-d',
            'items' => 'integer',
            'coupon' => 'boolean',
            'total' => 'float',
            'returned' => 'boolean',
        ];
    }
}
