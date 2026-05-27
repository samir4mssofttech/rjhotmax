<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Helpers\CurrencyHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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
        'proration_start_date', 'proration_end_date', 'prorated_days', 'is_prorated',
    ];

    protected $casts = [
        'status'   => PayoutStatus::class,
        'payout_type' => PayoutType::class,
        'paid_on'  => 'date',
        'proration_start_date' => 'date',
        'proration_end_date' => 'date',
        'is_prorated' => 'boolean',
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

    /**
     * Get the last paid payout for this employee
     */
    public function getLastPaidPayout(Employee $employee, Carbon $currentMonth): ?self
    {
        return self::where('employee_id', $employee->id)
            ->whereIn('status', [PayoutStatus::Approved, PayoutStatus::Paid])
            ->where('paid_on', '!=', null)
            ->where('paid_on', '<', $currentMonth->startOfMonth())
            ->orderByDesc('paid_on')
            ->first();
    }

    /**
     * Calculate eligibility period based on employment and last payment
     * Returns: ['start_date', 'end_date', 'eligible_days', 'is_prorated']
     */
    public static function calculateEligibilityPeriod(
        Employee $employee,
        string $month
    ): array {
        [$year, $mon] = explode('-', $month);
        $currentMonth = Carbon::createFromDate($year, $mon, 1);
        $monthStart = $currentMonth->copy()->startOfMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();

        $startDate = $monthStart->copy();
        $endDate = $monthEnd->copy();
        $isProrated = false;

        // Check if employee is joining during this month
        if ($employee->joining_date && $employee->joining_date > $monthStart) {
            $startDate = $employee->joining_date;
            $isProrated = true;
        }

        // Check if employee is leaving during this month
        if ($employee->leaving_date && $employee->leaving_date < $monthEnd) {
            $endDate = $employee->leaving_date;
            $isProrated = true;
        }

        // Check last paid date - if already paid, start from next day
        $lastPayout = self::where('employee_id', $employee->id)
            ->whereIn('status', [PayoutStatus::Approved, PayoutStatus::Paid])
            ->whereNotNull('paid_on')
            ->orderByDesc('paid_on')
            ->first();

        if ($lastPayout && $lastPayout->paid_on) {
            $lastPaidDate = Carbon::parse($lastPayout->paid_on);
            
            // If already paid in this month, not eligible
            if ($lastPaidDate >= $monthStart && $lastPaidDate <= $monthEnd) {
                return [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'eligible_days' => 0,
                    'is_prorated' => true,
                    'reason' => 'Already paid this month on ' . $lastPaidDate->format('Y-m-d'),
                ];
            }

            // If last paid is before this month, start fresh from 1st
            // This handles the case: paid on 30th, now eligible from 1st
            if ($lastPaidDate < $monthStart) {
                $startDate = $monthStart->copy();
            }
        }

        // Calculate eligible days (inclusive)
        $eligibleDays = $startDate->diffInDays($endDate) + 1;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'eligible_days' => max(0, $eligibleDays),
            'is_prorated' => $isProrated,
            'reason' => null,
        ];
    }

    /**
     * Calculate proration multiplier (eligible_days / total_working_days)
     */
    public static function calculateProrationMultiplier(
        int $eligibleDays,
        int $totalWorkingDays
    ): float {
        if ($totalWorkingDays === 0) {
            return 0;
        }

        return min(1.0, $eligibleDays / $totalWorkingDays);
    }

    // Auto-calculate all salary components from employee + attendance with proration support
    public static function calculateForEmployee(
        Employee $employee,
        string $month,
        bool $forceProration = false
    ): array {
        [$year, $mon] = explode('-', $month);

        // Get eligibility period
        $eligibility = self::calculateEligibilityPeriod($employee, $month);
        
        // If not eligible (already paid), return empty calculation
        if ($eligibility['eligible_days'] === 0 && !$forceProration) {
            return [
                'error' => $eligibility['reason'] ?? 'Employee not eligible for payout in this period',
                'eligible_days' => 0,
                'is_prorated' => true,
            ];
        }

        $startDate = $eligibility['start_date'];
        $endDate = $eligibility['end_date'];
        $eligibleDays = $eligibility['eligible_days'];
        $isProrated = $eligibility['is_prorated'];

        // Standard 26 working days calculation
        $totalWorkingDays = 26;

        // Get attendance within eligible period only
        $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Count only from eligible period
        $presentDays = $attendances->filter(
            fn($a) => in_array($a->status, [
                \App\Enums\Attendance::PRESENT,
                \App\Enums\Attendance::HALF_DAY,
            ])
        )->count();

        $lateDays = $attendances->where('is_late', true)->count();
        $absentDays = max(0, $eligibleDays - $presentDays);
        $overtimeMinutes = $attendances->sum('overtime');

        // Calculate proration multiplier
        $prorateMultiplier = self::calculateProrationMultiplier($eligibleDays, $totalWorkingDays);

        // Skill-based rates in paisa
        $rateMap = [
            'Unskilled'     => 46200,
            'Semi-Skilled'  => 51200,
            'Skilled'       => 56200,
            'Fully-Skilled' => 61200,
        ];

        $basicSalaryPaisa = $employee->basic_salary;
        $hraPaisa = $employee->hra;
        $conveyancePaisa = $employee->conveyance;
        $medicalPaisa = $employee->medical;
        $otherAllowancesPaisa = $employee->other_allowances;

        // Check if employee has a skill type with a defined rate
        if (!empty($employee->skill_type) && isset($rateMap[$employee->skill_type])) {
            $perDayRate = $rateMap[$employee->skill_type];
            // Gross for full 26 days
            $grossFixed = $perDayRate * $totalWorkingDays;
        } else {
            // Per-day salary for deduction calculation based on fixed components
            $grossFixed = $basicSalaryPaisa
                + $hraPaisa
                + $conveyancePaisa
                + $medicalPaisa
                + $otherAllowancesPaisa;

            $perDayRate = $totalWorkingDays > 0 ? $grossFixed / $totalWorkingDays : 0;
        }

        // Apply proration to base salary
        $basicSalaryProrated = round($basicSalaryPaisa * $prorateMultiplier, 2);
        $hraProrated = round($hraPaisa * $prorateMultiplier, 2);
        $conveyanceProrated = round($conveyancePaisa * $prorateMultiplier, 2);
        $medicalProrated = round($medicalPaisa * $prorateMultiplier, 2);
        $otherAllowancesProrated = round($otherAllowancesPaisa * $prorateMultiplier, 2);

        // Recalculate gross for prorated period
        $grossFixedProrated = $basicSalaryProrated
            + $hraProrated
            + $conveyanceProrated
            + $medicalProrated
            + $otherAllowancesProrated;

        // Deductions based on actual eligible days
        $perDayRateProrated = $eligibleDays > 0 ? $grossFixedProrated / $eligibleDays : 0;
        $absentDeduction = round($perDayRateProrated * $absentDays, 2);
        $lateDeduction = round(($perDayRateProrated / 2) * max(0, $lateDays - 3), 2);

        // Overtime is calculated on actual worked days
        $overtimeAmount = round(($perDayRate / 8 / 60) * $overtimeMinutes, 2);

        $grossSalary = $grossFixedProrated + $overtimeAmount;

        // PF & ESI - prorated if applicable
        $pfProrated = round(($employee->pf ?? 0) * $prorateMultiplier, 2);
        $esiProrated = round(($employee->esi ?? 0) * $prorateMultiplier, 2);

        $totalDeductions = $pfProrated + $esiProrated + $absentDeduction + $lateDeduction;
        $netSalary = round($grossSalary - $totalDeductions, 2);

        return [
            'branch_id' => $employee->branch_id,
            'total_working_days' => $totalWorkingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'overtime_minutes' => $overtimeMinutes,
            'basic_salary' => CurrencyHelper::paisaToRupee($basicSalaryProrated),
            'hra' => CurrencyHelper::paisaToRupee($hraProrated),
            'conveyance' => CurrencyHelper::paisaToRupee($conveyanceProrated),
            'medical' => CurrencyHelper::paisaToRupee($medicalProrated),
            'other_allowances' => CurrencyHelper::paisaToRupee($otherAllowancesProrated),
            'overtime_amount' => CurrencyHelper::paisaToRupee($overtimeAmount),
            'gross_salary' => CurrencyHelper::paisaToRupee($grossSalary),
            'pf' => CurrencyHelper::paisaToRupee($pfProrated),
            'esi' => CurrencyHelper::paisaToRupee($esiProrated),
            'absent_deduction' => CurrencyHelper::paisaToRupee($absentDeduction),
            'late_deduction' => CurrencyHelper::paisaToRupee($lateDeduction),
            'other_deductions' => CurrencyHelper::paisaToRupee(0),
            'total_deductions' => CurrencyHelper::paisaToRupee($totalDeductions),
            'net_salary' => CurrencyHelper::paisaToRupee($netSalary),
            'payout_type' => $employee->payout_type,
            'status' => PayoutStatus::Draft,
            'is_prorated' => $isProrated,
            'proration_start_date' => $startDate,
            'proration_end_date' => $endDate,
            'prorated_days' => $eligibleDays,
            'proratio_multiplier' => round($prorateMultiplier, 4),
        ];
    }
}