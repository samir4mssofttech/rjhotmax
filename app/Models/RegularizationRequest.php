<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularizationRequest extends Model
{
     protected $fillable = [
        'employee_id', 'attendance_id',
        'requested_check_in', 'requested_check_out',
        'reason', 'status', 'reviewed_by',
        'reviewer_comment', 'reviewed_at',
    ];

    protected $casts = [
        // 'status'      => RegularizationStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function branch()
{
    return $this->belongsTo(Branch::class);
}
}
