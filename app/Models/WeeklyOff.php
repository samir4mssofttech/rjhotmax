<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyOff extends Model
{
    protected $fillable = [
        'shift_id', 'day_of_week', 
    ];
}
