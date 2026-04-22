<?php

namespace App\Models;

use App\Models\WeeklyOff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'type',
        'start_time',
        'end_time',
        // 'grace_period_minutes',
        // 'half_day_minutes',
        // 'overtime_eligible',
        // 'is_active',
    ];

    // protected $casts = [
    //     'overtime_eligible' => 'boolean',
    //     'is_active'         => 'boolean',
    // ];

    public function weeklyOffs(): HasMany
    {
        return $this->hasMany(WeeklyOff::class);
    }

    public function isWeeklyOff(string $dayOfWeek): bool
    {
        return $this->weeklyOffs->contains('day_of_week', (int) $dayOfWeek);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
