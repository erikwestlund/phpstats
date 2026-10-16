<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentScore extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'study_hours' => 'float',
            'prior_score' => 'float',
            'score' => 'float',
        ];
    }
}
