<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Helpers\CurrencyHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payout extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'branch_id', 'payout_month',
        'total_working_days', 'present_days', 'absent_days',
        'late_days', 'overtime_minutes',
        'basic_salary', 'hra', 'conveyance', 'medical',
        'other_allowances', 'overtime_amount', 'gross_salary',
        'pf', 'esi', 'absent_deduction', 'late_deduction',
        'other_deductions', 'total_deductions',
        'net_salary', 'payout_type', 'status',
        'paid_on', 'remarks', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'status'   => PayoutStatus::class,
        'payout_type' => PayoutType::class,
        'paid_on'  => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Auto-calculate all salary components from employee + attendance
    public static function calculateForEmployee(Employee $employee, string $month): array
    {
        [$year, $mon] = explode('-', $month);

        $startDate = \Carbon\Carbon::createFromDate($year, $mon, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        // Total working days (Mon–Sat, excluding Sundays — adjust as needed)
        $totalWorkingDays = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->dayOfWeek !== \Carbon\Carbon::SUNDAY) {
                $totalWorkingDays++;
            }
            $current->addDay();
        }

        // Attendance stats
        $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
    // ✅ Compare using Enum cases directly
    $presentDays = $attendances->filter(
        fn ($a) => in_array($a->status, [
            \App\Enums\Attendance::PRESENT,
            \App\Enums\Attendance::HALF_DAY,
        ])
    )->count();

    // ✅ is_late is cast to boolean so this works fine
    $lateDays = $attendances->where('is_late', true)->count();
        // $presentDays     = $attendances->whereIn('status', ['present', 'half_day'])->count();
        // $lateDays        = $attendances->where('is_late', true)->count();
        $absentDays      = max(0, $totalWorkingDays - $presentDays);
        $overtimeMinutes = $attendances->sum('overtime');

        // Per-day salary for deduction calculation
        $grossFixed = $employee->basic_salary
            + $employee->hra
            + $employee->conveyance
            + $employee->medical
            + $employee->other_allowances;

        $perDayRate       = $totalWorkingDays > 0 ? $grossFixed / $totalWorkingDays : 0;
        $absentDeduction  = round($perDayRate * $absentDays, 2);
        $lateDeduction    = round(($perDayRate / 2) * ($lateDays > 3 ? $lateDays - 3 : 0), 2); // grace 3 lates
        $overtimeAmount   = round(($perDayRate / 8 / 60) * $overtimeMinutes, 2); // hourly OT rate

        $grossSalary     = $grossFixed + $overtimeAmount;
        $pf              = round($employee->pf ?? 0, 2);
        $esi             = round($employee->esi ?? 0, 2);
        $totalDeductions = $pf + $esi + $absentDeduction + $lateDeduction;
        $netSalary       = round($grossSalary - $totalDeductions, 2);

        return [
            'branch_id'          => $employee->branch_id,
            'total_working_days' => $totalWorkingDays,
            'present_days'       => $presentDays,
            'absent_days'        => $absentDays,
            'late_days'          => $lateDays,
            'overtime_minutes'   => $overtimeMinutes,
            'basic_salary'       => CurrencyHelper::paisaToRupee($employee->basic_salary),
            'hra'                => CurrencyHelper::paisaToRupee($employee->hra),
            'conveyance'         => CurrencyHelper::paisaToRupee($employee->conveyance),
            'medical'            => CurrencyHelper::paisaToRupee($employee->medical),
            'other_allowances'   => CurrencyHelper::paisaToRupee($employee->other_allowances),
            'overtime_amount'    => CurrencyHelper::paisaToRupee($overtimeAmount),
            'gross_salary'       => CurrencyHelper::paisaToRupee($grossSalary),
            'pf'                 => CurrencyHelper::paisaToRupee($pf),
            'esi'                => CurrencyHelper::paisaToRupee($esi),
            'absent_deduction'   => CurrencyHelper::paisaToRupee($absentDeduction),
            'late_deduction'     => CurrencyHelper::paisaToRupee($lateDeduction),
            'other_deductions'   => CurrencyHelper::paisaToRupee(0),
            'total_deductions'   => CurrencyHelper::paisaToRupee($totalDeductions),
            'net_salary'         => CurrencyHelper::paisaToRupee($netSalary),
            'payout_type'        => $employee->payout_type,
            'status'             => PayoutStatus::Draft,
        ];
    }
}