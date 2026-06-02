<?php

namespace App\Models;

use App\Enums\PayoutStatus;

use App\Helpers\CurrencyHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory; // ← add this

class Payout extends Model
{
    use SoftDeletes,HasFactory;

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
        'status'               => PayoutStatus::class,
        // 'payout_type'          => PayoutType::class,
        'paid_on'              => 'date',
        'proration_start_date' => 'date',
        'proration_end_date'   => 'date',
        'is_prorated'          => 'boolean',
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
        $sundays = collect();
        $period  = CarbonPeriod::create($start, $end);
        foreach ($period as $day) {
            if ($day->isSunday()) {
                $sundays->push($day->toDateString());
            }
        }

        $dbHolidays = Holiday::whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString());

        return $sundays->merge($dbHolidays)->unique()->values();
    }

    /**
     * Count working days in a date range: calendar days – Sundays – public holidays.
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
     *
     * For DAILY workers:
     *   - eligible_days = calendar days from start_date to end_date (inclusive).
     *   - Sundays/holidays are NOT subtracted here — a daily worker CAN work any
     *     day; what they actually earned is driven by attendance records alone.
     *   - e.g. joining on the 15th → start=15, end=31, eligible_days=17.
     *
     * For MONTHLY workers:
     *   - eligible_days = working days (calendar days − Sundays − holidays).
     *
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

        // Joining mid-month → start from joining date
        if ($employee->joining_date && Carbon::parse($employee->joining_date)->gt($monthStart)) {
            $startDate  = Carbon::parse($employee->joining_date);
            $isProrated = true;
        }

        // Leaving mid-month → end at leaving date
        if ($employee->leaving_date && Carbon::parse($employee->leaving_date)->lt($monthEnd)) {
            $endDate    = Carbon::parse($employee->leaving_date);
            $isProrated = true;
        }

        // Already paid this month → not eligible
        $lastPayout = self::where('employee_id', $employee->id)
            ->whereIn('status', [PayoutStatus::Approved, PayoutStatus::Paid])
            ->whereNotNull('paid_on')
            ->orderByDesc('paid_on')
            ->first();

        if ($lastPayout && $lastPayout->paid_on) {
            $lastPaidDate = Carbon::parse($lastPayout->paid_on);

            if ($lastPaidDate->gte($monthStart) && $lastPaidDate->lte($monthEnd)) {
                return [
                    'start_date'    => $startDate,
                    'end_date'      => $endDate,
                    'eligible_days' => 0,
                    'is_prorated'   => true,
                    'reason'        => 'Already paid this month on ' . $lastPaidDate->format('Y-m-d'),
                ];
            }
        }

        $isDaily = ($employee->payout_type === 'day_worker');

        // Daily workers: use raw calendar days (joining_date–month_end inclusive).
        // Monthly workers: use working days (excl. Sundays + holidays).
        $eligibleDays = $isDaily
            ? $startDate->diffInDays($endDate) + 1
            : self::countWorkingDays($startDate, $endDate);

        return [
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'eligible_days' => max(0, $eligibleDays),
            'is_prorated'   => $isProrated,
            'reason'        => null,
        ];
    }

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

    public static function calculateForEmployee(
        Employee $employee,
        string $month,
        bool $forceProration = false
    ): array {
        // Route to the correct calculator based on payout_type
        if ($employee->payout_type === 'day_worker') {
            return self::calculateDailyWorker($employee, $month);
        }

        return self::calculateMonthlyWorker($employee, $month, $forceProration);
    }

    // ══════════════════════════════════════════════════════════════
    // MONTHLY WORKER
    // ──────────────────────────────────────────────────────────────
    // • Gets paid full salary for all working days (excl. Sundays + holidays).
    // • Absent deduction = absent_working_days × (monthly_salary / calendar_days).
    // • Holidays and Sundays are NEVER counted as absent.
    // • Proration applies when joining / leaving mid-month.
    // ══════════════════════════════════════════════════════════════

    protected static function calculateMonthlyWorker(
        Employee $employee,
        string $month,
        bool $forceProration = false
    ): array {
        [$year, $mon] = explode('-', $month);

        $monthStart   = Carbon::createFromDate($year, $mon, 1)->startOfMonth();
        $monthEnd     = Carbon::createFromDate($year, $mon, 1)->endOfMonth();
        $calendarDays = (int) $monthStart->daysInMonth;

        $totalWorkingDays = self::countWorkingDays($monthStart, $monthEnd);

        $eligibility = self::calculateEligibilityPeriod($employee, $month);

        if ($eligibility['eligible_days'] === 0 && !$forceProration) {
            return [
                'error'         => $eligibility['reason'] ?? 'Employee not eligible for payout in this period',
                'eligible_days' => 0,
                'is_prorated'   => true,
            ];
        }

        $startDate           = $eligibility['start_date'];
        $endDate             = $eligibility['end_date'];
        $isProrated          = $eligibility['is_prorated'];
        $eligibleWorkingDays = self::countWorkingDays($startDate, $endDate);
        $holidayDates        = self::getHolidayDatesInRange($startDate, $endDate);

        $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $presentDays     = $attendances->filter(
            fn($a) => in_array($a->status, [\App\Enums\Attendance::PRESENT, \App\Enums\Attendance::HALF_DAY])
        )->count();
        $lateDays        = $attendances->where('is_late', true)->count();
        $overtimeMinutes = $attendances->sum('overtime');

        // Absent = eligible working days minus present (holidays already excluded from eligible)
        $absentDays = max(0, $eligibleWorkingDays - $presentDays);

        // Salary components in paisa
        $basicPaisa     = $employee->basic_salary     ?? 0;
        $hraPaisa       = $employee->hra              ?? 0;
        $convPaisa      = $employee->conveyance       ?? 0;
        $medPaisa       = $employee->medical          ?? 0;
        $otherPaisa     = $employee->other_allowances ?? 0;
        $fullSalary     = $basicPaisa + $hraPaisa + $convPaisa + $medPaisa + $otherPaisa;

        // Per-day rate based on calendar days (e.g. 30000/30 = 1000)
        $perDayRate = $calendarDays > 0 ? $fullSalary / $calendarDays : 0;

        // Proration for mid-month joining/leaving
        $prorateMultiplier       = self::calculateProrationMultiplier($eligibleWorkingDays, $totalWorkingDays);
        $basicProrated           = round($basicPaisa  * $prorateMultiplier, 2);
        $hraProrated             = round($hraPaisa    * $prorateMultiplier, 2);
        $convProrated            = round($convPaisa   * $prorateMultiplier, 2);
        $medProrated             = round($medPaisa    * $prorateMultiplier, 2);
        $otherProrated           = round($otherPaisa  * $prorateMultiplier, 2);
        $grossBeforeDeductions   = $basicProrated + $hraProrated + $convProrated + $medProrated + $otherProrated;

        // Deductions use the full per-day rate (not prorated rate)
        $absentDeduction = round($perDayRate * $absentDays, 2);
        $lateDeduction   = round(($perDayRate / 2) * max(0, $lateDays - 3), 2);
        $overtimeAmount  = round(($perDayRate / 8 / 60) * $overtimeMinutes, 2);
        $pfProrated      = round(($employee->pf  ?? 0) * $prorateMultiplier, 2);
        $esiProrated     = round(($employee->esi ?? 0) * $prorateMultiplier, 2);

        $grossSalary     = $grossBeforeDeductions + $overtimeAmount;
        $totalDeductions = $absentDeduction + $lateDeduction + $pfProrated + $esiProrated;
        $netSalary       = round($grossSalary - $totalDeductions, 2);

        return [
            'branch_id'            => $employee->branch_id,
            'total_working_days'   => $totalWorkingDays,
            'present_days'         => $presentDays,
            'absent_days'          => $absentDays,
            'late_days'            => $lateDays,
            'overtime_minutes'     => $overtimeMinutes,
            'basic_salary'         => CurrencyHelper::paisaToRupee($basicProrated),
            'hra'                  => CurrencyHelper::paisaToRupee($hraProrated),
            'conveyance'           => CurrencyHelper::paisaToRupee($convProrated),
            'medical'              => CurrencyHelper::paisaToRupee($medProrated),
            'other_allowances'     => CurrencyHelper::paisaToRupee($otherProrated),
            'overtime_amount'      => CurrencyHelper::paisaToRupee($overtimeAmount),
            'gross_salary'         => CurrencyHelper::paisaToRupee($grossSalary),
            'pf'                   => CurrencyHelper::paisaToRupee($pfProrated),
            'esi'                  => CurrencyHelper::paisaToRupee($esiProrated),
            'absent_deduction'     => CurrencyHelper::paisaToRupee($absentDeduction),
            'late_deduction'       => CurrencyHelper::paisaToRupee($lateDeduction),
            'other_deductions'     => CurrencyHelper::paisaToRupee(0),
            'total_deductions'     => CurrencyHelper::paisaToRupee($totalDeductions),
            'net_salary'           => CurrencyHelper::paisaToRupee($netSalary),
            'payout_type'          => $employee->payout_type,
            'status'               => PayoutStatus::Draft,
            'is_prorated'          => $isProrated,
            'proration_start_date' => $startDate,
            'proration_end_date'   => $endDate,
            'prorated_days'        => $eligibleWorkingDays,
            'proratio_multiplier'  => round($prorateMultiplier, 4),
            '_calendar_days'       => $calendarDays,
            '_per_day_rate_rupee'  => CurrencyHelper::paisaToRupee($perDayRate),
            '_holiday_count'       => $holidayDates->count(),
        ];
    }

    // ══════════════════════════════════════════════════════════════
    // DAILY WORKER
    // ──────────────────────────────────────────────────────────────
    // • No guaranteed base salary — paid strictly per day worked.
    // • Earn = daily_wage (basic_salary field) × present_days.
    // • Holidays and Sundays = no work = no pay (they don't earn on those days).
    // • If absent on a holiday it makes no difference — they weren't going to
    //   be paid that day anyway.
    // • Overtime = daily_wage / 8 hours / 60 min × overtime_minutes.
    // • Late deduction = (daily_wage / 2) × max(0, late_days - 3).
    // • PF & ESI calculated on actual earnings (present_days × daily_wage).
    // • No proration concept — they simply earn what they work.
    // ══════════════════════════════════════════════════════════════

    protected static function calculateDailyWorker(Employee $employee, string $month): array
    {
        [$year, $mon] = explode('-', $month);

        $monthStart   = Carbon::createFromDate($year, $mon, 1)->startOfMonth();
        $monthEnd     = Carbon::createFromDate($year, $mon, 1)->endOfMonth();

        // For daily workers, total_working_days = full calendar days in month (e.g. 31).
        // They can potentially work every day; attendance records decide what they actually earned.
        $totalWorkingDays = (int) $monthStart->daysInMonth;

        // Check eligibility (joining / leaving / already paid)
        $eligibility = self::calculateEligibilityPeriod($employee, $month);

        if ($eligibility['eligible_days'] === 0) {
            return [
                'error'         => $eligibility['reason'] ?? 'Employee not eligible for payout in this period',
                'eligible_days' => 0,
                'is_prorated'   => false,
            ];
        }

        $startDate = $eligibility['start_date'];
        $endDate   = $eligibility['end_date'];

        // Attendance within the eligible period
        $attendances = \App\Models\Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $presentDays     = $attendances->filter(
            fn($a) => in_array($a->status, [\App\Enums\Attendance::PRESENT, \App\Enums\Attendance::HALF_DAY])
        )->count();
        $lateDays        = $attendances->where('is_late', true)->count();
        $overtimeMinutes = $attendances->sum('overtime');

        // For daily workers, eligible period = calendar days from start to end.
        // absent = calendar days in period minus days they actually showed up.
        // (Sundays/holidays are simply days with no attendance record → no pay → not "absent".)
       $eligibleCalendarDays = (int) ($startDate->diffInDays($endDate) + 1);
$absentDays           = max(0, $eligibleCalendarDays - $presentDays);

        // All salary components stored as MONTHLY amounts in paisa on the employee record.
        // For daily workers we derive the per-day rate by dividing by calendar days in month.
        // e.g. monthly basic = ₹20,000 (2000000 paisa), May = 31 days → ₹645.16/day
        $monthlyBasicPaisa   = $employee->basic_salary     ?? 0;
        $monthlyHraPaisa     = $employee->hra              ?? 0;
        $monthlyConvPaisa    = $employee->conveyance       ?? 0;
        $monthlyMedPaisa     = $employee->medical          ?? 0;
        $monthlyOtherPaisa   = $employee->other_allowances ?? 0;

        // Per-day rates (paisa)
        $dailyBasicPaisa = $totalWorkingDays > 0 ? $monthlyBasicPaisa / $totalWorkingDays : 0;
        $dailyHraPaisa   = $totalWorkingDays > 0 ? $monthlyHraPaisa   / $totalWorkingDays : 0;
        $dailyConvPaisa  = $totalWorkingDays > 0 ? $monthlyConvPaisa  / $totalWorkingDays : 0;
        $dailyMedPaisa   = $totalWorkingDays > 0 ? $monthlyMedPaisa   / $totalWorkingDays : 0;
        $dailyOtherPaisa = $totalWorkingDays > 0 ? $monthlyOtherPaisa / $totalWorkingDays : 0;

        // Combined daily wage (used for overtime + late deduction rate)
        $dailyWagePaisa = $dailyBasicPaisa + $dailyHraPaisa + $dailyConvPaisa + $dailyMedPaisa + $dailyOtherPaisa;

        // Earnings = The full monthly amounts shown exactly as they are in the employee table
        $basicEarned = $monthlyBasicPaisa;
        $hraEarned   = $monthlyHraPaisa;
        $convEarned  = $monthlyConvPaisa;
        $medEarned   = $monthlyMedPaisa;
        $otherEarned = $monthlyOtherPaisa;

        $grossBeforeDeductions = $basicEarned + $hraEarned + $convEarned + $medEarned + $otherEarned;

        // Overtime based on daily wage ÷ 8 hours ÷ 60 minutes
        $overtimeAmount = round(($dailyWagePaisa / 8 / 60) * $overtimeMinutes, 2);

        // Late deduction: first 3 late days free, half day's wage each after that
        $lateDeduction = round(($dailyWagePaisa / 2) * max(0, $lateDays - 3), 2);

        // Absent deduction for daily workers — full days not present are deducted
        $absentDeduction = round($dailyWagePaisa * $absentDays, 2);

        // PF & ESI: full monthly amount so it matches employee table
        $pfEarned     = $employee->pf ?? 0;
        $esiEarned    = $employee->esi ?? 0;

        $grossSalary     = $grossBeforeDeductions + $overtimeAmount;
        $totalDeductions = $absentDeduction + $lateDeduction + $pfEarned + $esiEarned;
        $netSalary       = round($grossSalary - $totalDeductions, 2);

        return [
            'branch_id'            => $employee->branch_id,
            'total_working_days'   => $totalWorkingDays,
            'present_days'         => $presentDays,
            'absent_days'          => $absentDays,
            'late_days'            => $lateDays,
            'overtime_minutes'     => $overtimeMinutes,
            'basic_salary'         => CurrencyHelper::paisaToRupee($basicEarned),
            'hra'                  => CurrencyHelper::paisaToRupee($hraEarned),
            'conveyance'           => CurrencyHelper::paisaToRupee($convEarned),
            'medical'              => CurrencyHelper::paisaToRupee($medEarned),
            'other_allowances'     => CurrencyHelper::paisaToRupee($otherEarned),
            'overtime_amount'      => CurrencyHelper::paisaToRupee($overtimeAmount),
            'gross_salary'         => CurrencyHelper::paisaToRupee($grossSalary),
            'pf'                   => CurrencyHelper::paisaToRupee($pfEarned),
            'esi'                  => CurrencyHelper::paisaToRupee($esiEarned),
            'absent_deduction'     => CurrencyHelper::paisaToRupee($absentDeduction),
            'late_deduction'       => CurrencyHelper::paisaToRupee($lateDeduction),
            'other_deductions'     => CurrencyHelper::paisaToRupee(0),
            'total_deductions'     => CurrencyHelper::paisaToRupee($totalDeductions),
            'net_salary'           => CurrencyHelper::paisaToRupee($netSalary),
            'payout_type'          => $employee->payout_type,
            'status'               => PayoutStatus::Draft,
            'is_prorated'          => false,   // daily workers don't use proration
            'proration_start_date' => $startDate,
            'proration_end_date'   => $endDate,
            'prorated_days'        => $eligibleCalendarDays,  // calendar days in eligible period
            'proratio_multiplier'  => 1.0,
            '_daily_wage_rupee'    => CurrencyHelper::paisaToRupee($dailyWagePaisa),
            '_holiday_count'       => self::getHolidayDatesInRange($startDate, $endDate)->count(),
        ];
    }
}