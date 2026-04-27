<?php

namespace App\Models;

use App\Enums\Attendance as EnumsAttendance;
use App\Models\RegularizationRequest;
use App\Models\Shift;
use Carbon\Carbon;
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
        'worked_hours',
        'overtime',
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

    /**
     * Helper to calculate actual minutes worked
     */
    // public static function getWorkedMinutesAttribute($in, $out): int
    // {
    //     if (!$in || !$out) return 0;

    //     $startTime = Carbon::parse($in);
    //     $endTime = Carbon::parse($out);

    //     if ($endTime->lt($startTime)) {
    //         $endTime->addDay();
    //     }

    //     return (int) $startTime->diffInMinutes($endTime);
    // }

    /**
     * Helper to calculate (Worked Minutes) - (Shift Required Minutes)
     */
    // public static function getOvertimeMinutes($in, $out, $shiftId): int
    // {
    //     if (!$in || !$out || !$shiftId) return 0;

    //     $actualMinutes = self::getWorkedMinutesAttribute($in, $out);

    //     $shift = Shift::find($shiftId);
    //     if (!$shift) return 0;

    //     $shiftStart = Carbon::parse($shift->start_time);
    //     $shiftEnd = Carbon::parse($shift->end_time);

    //     if ($shiftEnd->lt($shiftStart)) {
    //         $shiftEnd->addDay();
    //     }

    //     $requiredMinutes = $shiftStart->diffInMinutes($shiftEnd);

    //     return $actualMinutes - $requiredMinutes;
    // }
}
