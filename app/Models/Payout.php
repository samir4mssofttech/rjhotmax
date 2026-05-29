<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
use App\Helpers\CurrencyHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
        'status'              => PayoutStatus::class,
        'payout_type'         => PayoutType::class,
        'paid_on'             => 'date',
        'proration_start_date' => 'date',
        'proration_end_date'  => 'date',
        'is_prorated'         => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────

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

    // ── Holiday / Working-Day Helpers ──────────────────────────────

    /**
     * Collect all holiday dates (Sundays + DB holidays) within a date range.
     * Returns a Collection of date strings: ['2026-05-03', '2026-05-10', ...]
     */
    public static function getHolidayDatesInRange(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        // 1. All Sundays in the range
        $sundays = collect();
        $period  = CarbonPeriod::create($start, $end);
        foreach ($period as $day) {
            if ($day->isSunday()) {
                $sundays->push($day->toDateString());
            }
        }

        // 2. DB holidays in the range
        $dbHolidays = Holiday::whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString());

        return $sundays->merge($dbHolidays)->unique()->values();
    }

    /**
     * Count working days in a date range:
     * total calendar days  – Sundays – public holidays
     */
    public static function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $totalDays    = $start->diffInDays($end) + 1;
        $holidayCount = self::getHolidayDatesInRange($start, $end)->count();

        return max(0, $totalDays - $holidayCount);
    }

    // ── Eligibility ────────────────────────────────────────────────

    /**
     * Calculate eligibility period based on employment dates and last payment.
     * Returns: ['start_date', 'end_date', 'eligible_days', 'is_prorated', 'reason']
     */
    public static function calculateEligibilityPeriod(Employee $employee, string $month): array
    {
        [$year, $mon] = explode('-', $month);
        $monthStart   = Carbon::createFromDate($year, $mon, 1)->startOfMonth();
        $monthEnd     = Carbon::createFromDate($year, $mon, 1)->endOfMonth();

        $startDate  = $monthStart->copy();
        $endDate    = $monthEnd->copy();
        $isProrated = false;

        // Joining mid-month
        if ($employee->joining_date && $employee->joining_date > $monthStart) {
            $startDate  = Carbon::parse($employee->joining_date);
            $isProrated = true;
        }

        // Leaving mid-month
        if ($employee->leaving_date && $employee->leaving_date < $monthEnd) {
            $endDate    = Carbon::parse($employee->leaving_date);
            $isProrated = true;
        }

        // Already paid this month?
        $lastPayout = self::where('employee_id', $employee->id)
            ->whereIn('status', [PayoutStatus::Approved, PayoutStatus::Paid])
            ->whereNotNull('paid_on')
            ->orderByDesc('paid_on')
            ->first();

        if ($lastPayout && $lastPayout->paid_on) {
            $lastPaidDate = Carbon::parse($lastPayout->paid_on);

            if ($lastPaidDate >= $monthStart && $lastPaidDate <= $monthEnd) {
                return [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'eligible_days' => 0,
                    'is_prorated'   => true,
                    'reason'        => 'Already paid this month on ' . $lastPaidDate->format('Y-m-d'),
                ];
            }
        }

        // Eligible working days within the period (excludes Sundays + holidays)
        $eligibleDays = self::countWorkingDays($startDate, $endDate);

        return [
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'eligible_days' => $eligibleDays,
            'is_prorated'   => $isProrated,
            'reason'        => null,
        ];
    }

    // ── Proration ─────────────────────────────────────────────────

    /**
     * Proration multiplier = eligible_working_days / total_working_days_in_month
     */
    public static function calculateProrationMultiplier(int $eligibleDays, int $totalWorkingDays): float
    {
        if ($totalWorkingDays === 0) {
            return 0.0;
        }

        return min(1.0, $eligibleDays / $totalWorkingDays);
    }

    // ── Main Calculation ───────────────────────────────────────────

    /**
     * Core salary calculation.
     *
     * Formula:
     *   per_day_rate        = monthly_salary / total_calendar_days_in_month
     *   absent_deduction    = absent_working_days * per_day_rate
     *   late_deduction      = max(0, late_days - 3) * (per_day_rate / 2)
     *   overtime_amount     = overtime_minutes * (per_day_rate / 8 / 60)
     *   gross               = full_prorated_salary + overtime_amount
     *   net                 = gross - absent_deduction - late_deduction - pf - esi
     *
     * Sundays and public holidays are NEVER counted as absent.
     */
    public static function calculateForEmployee(
        Employee $employee,
        string $month,
        bool $forceProration = false
    ): array {
        [$year, $mon] = explode('-', $month);

        // ── Calendar facts ──────────────────────────────────────────
        $monthStart        = Carbon::createFromDate($year, $mon, 1)->startOfMonth();
        $monthEnd          = Carbon::createFromDate($year, $mon, 1)->endOfMonth();
        $calendarDays      = (int) $monthStart->daysInMonth; // e.g. 30, 31, 28

        // Total working days for the whole month (excl. Sundays + holidays)
        $totalWorkingDays  = self::countWorkingDays($monthStart, $monthEnd);

        // ── Eligibility ─────────────────────────────────────────────
        $eligibility = self::calculateEligibilityPeriod($employee, $month);

        if ($eligibility['eligible_days'] === 0 && !$forceProration) {
            return [
                'error'         => $eligibility['reason'] ?? 'Employee not eligible for payout in this period',
                'eligible_days' => 0,
                'is_prorated'   => true,
            ];
        }

        $startDate    = $eligibility['start_date'];
        $endDate      = $eligibility['end_date'];
        $isProrated   = $eligibility['is_prorated'];

        // Working days the employee is eligible for (their actual period)
        $eligibleWorkingDays = self::countWorkingDays($startDate, $endDate);

        // ── Holiday dates in eligible period ────────────────────────
        $holidayDates = self::getHolidayDatesInRange($startDate, $endDate);

        // ── Attendance ──────────────────────────────────────────────
        $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $presentDays = $attendances->filter(
            fn($a) => in_array($a->status, [
                \App\Enums\Attendance::PRESENT,
                \App\Enums\Attendance::HALF_DAY,
            ])
        )->count();

        $lateDays       = $attendances->where('is_late', true)->count();
        $overtimeMinutes = $attendances->sum('overtime');

        // Absent = eligible working days the employee neither showed up nor was on holiday
        // (Holidays/Sundays are already excluded from eligibleWorkingDays via countWorkingDays)
        $absentDays = max(0, $eligibleWorkingDays - $presentDays);

        // ── Salary components (stored in paisa) ─────────────────────
        $basicSalaryPaisa       = $employee->basic_salary      ?? 0;
        $hraPaisa               = $employee->hra               ?? 0;
        $conveyancePaisa        = $employee->conveyance        ?? 0;
        $medicalPaisa           = $employee->medical           ?? 0;
        $otherAllowancesPaisa   = $employee->other_allowances  ?? 0;

        // Full monthly gross (before any deduction)
        $fullMonthlySalaryPaisa = $basicSalaryPaisa
            + $hraPaisa
            + $conveyancePaisa
            + $medicalPaisa
            + $otherAllowancesPaisa;

        // ── Per-day rate: salary / calendar days ────────────────────
        // e.g. 30,000 / 30 = 1,000 per day
        $perDayRatePaisa = $calendarDays > 0
            ? $fullMonthlySalaryPaisa / $calendarDays
            : 0;

        // ── Proration (for joining / leaving mid-month) ─────────────
        // If prorated, scale each component proportionally
        $prorateMultiplier = self::calculateProrationMultiplier($eligibleWorkingDays, $totalWorkingDays);

        $basicSalaryProrated     = round($basicSalaryPaisa     * $prorateMultiplier, 2);
        $hraProrated             = round($hraPaisa             * $prorateMultiplier, 2);
        $conveyanceProrated      = round($conveyancePaisa      * $prorateMultiplier, 2);
        $medicalProrated         = round($medicalPaisa         * $prorateMultiplier, 2);
        $otherAllowancesProrated = round($otherAllowancesPaisa * $prorateMultiplier, 2);

        $grossBeforeDeductions = $basicSalaryProrated
            + $hraProrated
            + $conveyanceProrated
            + $medicalProrated
            + $otherAllowancesProrated;

        // ── Deductions ──────────────────────────────────────────────

        // Absent deduction: absent_days * per_day_rate (on full monthly rate, not prorated)
        $absentDeduction = round($perDayRatePaisa * $absentDays, 2);

        // Late deduction: first 3 late days are free; beyond that half a day's pay each
        $lateDeduction = round(($perDayRatePaisa / 2) * max(0, $lateDays - 3), 2);

        // Overtime: per_day_rate / 8 hours / 60 minutes
        $overtimeAmount = round(($perDayRatePaisa / 8 / 60) * $overtimeMinutes, 2);

        // PF & ESI prorated
        $pfProrated  = round(($employee->pf  ?? 0) * $prorateMultiplier, 2);
        $esiProrated = round(($employee->esi ?? 0) * $prorateMultiplier, 2);

        $grossSalary    = $grossBeforeDeductions + $overtimeAmount;
        $totalDeductions = $absentDeduction + $lateDeduction + $pfProrated + $esiProrated;
        $netSalary      = round($grossSalary - $totalDeductions, 2);

        return [
            'branch_id'            => $employee->branch_id,

            // Working day counts
            'total_working_days'   => $totalWorkingDays,       // full month working days
            'present_days'         => $presentDays,
            'absent_days'          => $absentDays,
            'late_days'            => $lateDays,
            'overtime_minutes'     => $overtimeMinutes,

            // Earnings (converted to rupees for display)
            'basic_salary'         => CurrencyHelper::paisaToRupee($basicSalaryProrated),
            'hra'                  => CurrencyHelper::paisaToRupee($hraProrated),
            'conveyance'           => CurrencyHelper::paisaToRupee($conveyanceProrated),
            'medical'              => CurrencyHelper::paisaToRupee($medicalProrated),
            'other_allowances'     => CurrencyHelper::paisaToRupee($otherAllowancesProrated),
            'overtime_amount'      => CurrencyHelper::paisaToRupee($overtimeAmount),
            'gross_salary'         => CurrencyHelper::paisaToRupee($grossSalary),

            // Deductions
            'pf'                   => CurrencyHelper::paisaToRupee($pfProrated),
            'esi'                  => CurrencyHelper::paisaToRupee($esiProrated),
            'absent_deduction'     => CurrencyHelper::paisaToRupee($absentDeduction),
            'late_deduction'       => CurrencyHelper::paisaToRupee($lateDeduction),
            'other_deductions'     => CurrencyHelper::paisaToRupee(0),
            'total_deductions'     => CurrencyHelper::paisaToRupee($totalDeductions),
            'net_salary'           => CurrencyHelper::paisaToRupee($netSalary),

            // Meta
            'payout_type'          => $employee->payout_type,
            'status'               => PayoutStatus::Draft,
            'is_prorated'          => $isProrated,
            'proration_start_date' => $startDate,
            'proration_end_date'   => $endDate,
            'prorated_days'        => $eligibleWorkingDays,
            'proratio_multiplier'  => round($prorateMultiplier, 4),

            // Informational (not stored)
            '_calendar_days'       => $calendarDays,
            '_per_day_rate_rupee'  => CurrencyHelper::paisaToRupee($perDayRatePaisa),
            '_holiday_count'       => $holidayDates->count(),
        ];
    }
}