<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyVisit extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'visited_on' => 'date:Y-m-d',
            'visits' => 'integer',
        ];
    }
}
