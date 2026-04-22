<?php

namespace App\Models;

use App\Enums\Attendance as EnumsAttendance;
use App\Models\RegularizationRequest;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'branch_id',
        'shift_id',
        'date',
        'check_in_time',
        'check_out_time',
        'worked_minutes',
        'overtime_minutes',
        'is_late',
        'status',
        'remarks',
        'entered_by'
    ];

    protected $casts = [
        'date' => 'date',
        'is_late' => 'boolean',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'status' => EnumsAttendance::class,
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function regularizationRequest(): HasOne
    {
        return $this->hasOne(RegularizationRequest::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
