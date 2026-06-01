<?php

namespace Tests\Unit\Payouts;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Payout;
use App\Models\Holiday;
use App\Models\Attendance;
use App\Models\Branch;
use App\Enums\PayoutStatus;

/**
 * PayoutCalculationTest
 * =====================
 * Covers every meaningful edge case for both salaried and daily-worker payouts.
 *
 * Run a single test:
 *   php artisan test --filter PayoutCalculationTest::test_daily_worker_full_month_present
 *
 * Run all payout tests:
 *   php artisan test --filter PayoutCalculationTest
 *
 * See detailed logs:
 *   tail -f storage/logs/laravel.log | grep "PAYOUT_TEST"
 */
class PayoutCalculationTest extends TestCase
{
    use RefreshDatabase;

    // ── Shared fixtures ────────────────────────────────────────────

    private Branch   $branch;
    private Employee $salariedEmployee;   // payout_type = salaried
    private Employee $dailyEmployee;      // payout_type = day_worker

    /**
     * Monthly salary: ₹30,000  (3,000,000 paisa)
     * HRA:            ₹5,000   (500,000 paisa)
     * Conveyance:     ₹2,000   (200,000 paisa)
     * Medical:        ₹1,000   (100,000 paisa)
     * Other:          ₹0
     * PF:             ₹1,800   (180,000 paisa)
     * ESI:            ₹243     (24,300 paisa)
     *
     * Total CTC gross: ₹38,000
     */
    private array $salaryPackage = [
        'basic_salary'    => 3_000_000,
        'hra'             => 500_000,
        'conveyance'      => 200_000,
        'medical'         => 100_000,
        'other_allowances' => 0,
        'pf'              => 180_000,
        'esi'             => 24_300,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create(['name' => 'Test Branch']);

        $this->salariedEmployee = Employee::factory()->create(array_merge(
            $this->salaryPackage,
            [
                'branch_id'    => $this->branch->id,
                'payout_type'  => 'salaried',
                'join_date'    => '2025-01-01',  // Changed from joining_date to join_date
                'designation'  => 'Manager', // <--- ADD THIS LINE
                'employee_status' => 'active',
                'is_active'    => true,
                'skill_type'   => null,
            ]
        ));

        $this->dailyEmployee = Employee::factory()->create(array_merge(
            $this->salaryPackage,
            [
                'branch_id'    => $this->branch->id,
                'payout_type'  => 'day_worker',
                'join_date'    => '2025-01-01', // Changed from joining_date to join_date
                'designation'  => 'service_boy',   // <--- ADD THIS LINE
                'employee_status' => 'active',
                'is_active'    => true,
                'skill_type'   => null,
            ]
        ));
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════

    /**
     * Log a full before/after snapshot of every calculation input and output.
     */
    private function logPayoutTest(
        string    $testName,
        Employee  $employee,
        string    $month,
        array     $attendanceDates,   // ['2026-05-01' => 'present', '2026-05-02' => 'absent', ...]
        array     $result,
        array     $expectedValues = []
    ): void {
        $separator = str_repeat('─', 60);

        Log::channel('single')->info("PAYOUT_TEST ┌ {$separator}");
        Log::channel('single')->info("PAYOUT_TEST │ TEST: {$testName}");
        Log::channel('single')->info("PAYOUT_TEST │ {$separator}");

        // ── Employee profile ──────────────────────────────────────
        Log::channel('single')->info('PAYOUT_TEST │ [EMPLOYEE]', [
            'id'           => $employee->id,
            'name'         => $employee->name,
            'payout_type'  => $employee->payout_type,
            'join_date'    => $employee->join_date, // Changed from joining_date
            // 'leaving_date' => $employee->leaving_date,
            'month'        => $month,
        ]);

        // ── Salary package (converted from paisa) ─────────────────
        Log::channel('single')->info('PAYOUT_TEST │ [SALARY PACKAGE — monthly]', [
            'basic'      => '₹' . number_format($employee->basic_salary / 100, 2),
            'hra'        => '₹' . number_format($employee->hra / 100, 2),
            'conveyance' => '₹' . number_format($employee->conveyance / 100, 2),
            'medical'    => '₹' . number_format($employee->medical / 100, 2),
            'other'      => '₹' . number_format($employee->other_allowances / 100, 2),
            'pf'         => '₹' . number_format($employee->pf / 100, 2),
            'esi'        => '₹' . number_format($employee->esi / 100, 2),
            'total_gross' => '₹' . number_format(
                ($employee->basic_salary + $employee->hra + $employee->conveyance
                    + $employee->medical + $employee->other_allowances) / 100,
                2
            ),
        ]);

        // ── Attendance summary ────────────────────────────────────
        $presentCount = collect($attendanceDates)->filter(fn($s) => in_array($s, ['present', 'half_day']))->count();
        $absentCount  = collect($attendanceDates)->filter(fn($s) => $s === 'absent')->count();
        $lateCount    = collect($attendanceDates)->filter(fn($s) => $s === 'late')->count();

        Log::channel('single')->info('PAYOUT_TEST │ [ATTENDANCE INPUT]', [
            'total_marked' => count($attendanceDates),
            'present'      => $presentCount,
            'absent'       => $absentCount,
            'late'         => $lateCount,
            'dates'        => $attendanceDates,
        ]);

        // ── Eligibility ───────────────────────────────────────────
        $eligibility = Payout::calculateEligibilityPeriod($employee, $month);
        Log::channel('single')->info('PAYOUT_TEST │ [ELIGIBILITY]', [
            'start_date'    => $eligibility['start_date']->toDateString(),
            'end_date'      => $eligibility['end_date']->toDateString(),
            'eligible_days' => $eligibility['eligible_days'],
            'is_prorated'   => $eligibility['is_prorated'],
            'reason'        => $eligibility['reason'],
        ]);

        // ── Calendar facts ────────────────────────────────────────
        [$y, $m] = explode('-', $month);
        $ms = Carbon::createFromDate($y, $m, 1)->startOfMonth();
        $me = Carbon::createFromDate($y, $m, 1)->endOfMonth();
        Log::channel('single')->info('PAYOUT_TEST │ [CALENDAR]', [
            'calendar_days'    => $ms->daysInMonth,
            'total_working'    => Payout::countWorkingDays($ms, $me),
            'holidays+sundays' => Payout::getHolidayDatesInRange($ms, $me)->toArray(),
        ]);

        // ── Calculation result ────────────────────────────────────
        if (isset($result['error'])) {
            Log::channel('single')->warning('PAYOUT_TEST │ [RESULT — ERROR]', ['error' => $result['error']]);
        } else {
            Log::channel('single')->info('PAYOUT_TEST │ [RESULT — ATTENDANCE]', [
                'total_working_days' => $result['total_working_days'],
                'present_days'       => $result['present_days'],
                'absent_days'        => $result['absent_days'],
                'late_days'          => $result['late_days'],
                'overtime_minutes'   => $result['overtime_minutes'],
            ]);
            Log::channel('single')->info('PAYOUT_TEST │ [RESULT — EARNINGS ₹]', [
                'basic_salary'    => $result['basic_salary'],
                'hra'             => $result['hra'],
                'conveyance'      => $result['conveyance'],
                'medical'         => $result['medical'],
                'other_allowances' => $result['other_allowances'],
                'overtime_amount' => $result['overtime_amount'],
                'gross_salary'    => $result['gross_salary'],
            ]);
            Log::channel('single')->info('PAYOUT_TEST │ [RESULT — DEDUCTIONS ₹]', [
                'pf'               => $result['pf'],
                'esi'              => $result['esi'],
                'absent_deduction' => $result['absent_deduction'],
                'late_deduction'   => $result['late_deduction'],
                'other_deductions' => $result['other_deductions'],
                'total_deductions' => $result['total_deductions'],
            ]);
            Log::channel('single')->info('PAYOUT_TEST │ [RESULT — NET PAY ₹]', [
                'net_salary'       => $result['net_salary'],
                'is_prorated'      => $result['is_prorated'],
                'prorated_days'    => $result['prorated_days'],
                'proration_start'  => $result['proration_start_date']?->toDateString(),
                'proration_end'    => $result['proration_end_date']?->toDateString(),
            ]);
        }

        // ── Assertion summary ─────────────────────────────────────
        if (!empty($expectedValues)) {
            Log::channel('single')->info('PAYOUT_TEST │ [ASSERTIONS]', $expectedValues);
        }

        Log::channel('single')->info("PAYOUT_TEST └ {$separator}");
    }

    /**
     * Create attendance records for an employee.
     * $days = ['2026-05-01' => 'present', '2026-05-03' => 'absent', ...]
     * status values: 'present', 'absent', 'half_day'
     * Add 'late' => true or 'overtime' => 120 as extra keys per date via array value.
     */
    private function makeAttendance(Employee $employee, array $days): void
    {
        foreach ($days as $date => $status) {
            $isLate  = false;
            $overtime = 0;

            if (is_array($status)) {
                $isLate   = $status['late']     ?? false;
                $overtime = $status['overtime'] ?? 0;
                $status   = $status['status'];
            }

            $attStatus = match ($status) {
                'present'  => \App\Enums\Attendance::PRESENT,
                'half_day' => \App\Enums\Attendance::HALF_DAY,
                default    => \App\Enums\Attendance::ABSENT,
            };

            Attendance::factory()->create([
                'employee_id' => $employee->id,
                'date'        => $date,
                'status'      => $attStatus,
                'is_late'     => $isLate,
                'overtime'    => $overtime,
            ]);
        }
    }

    /**
     * Create an already-paid payout to simulate the "already paid" scenario.
     */
    private function makePaidPayout(Employee $employee, string $paidOn): void
    {
        Payout::factory()->create([
            'employee_id' => $employee->id,
            'branch_id'   => $employee->branch_id,
            'status'      => PayoutStatus::Paid,
            'paid_on'     => $paidOn,
            'payout_month' => substr($paidOn, 0, 7),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // ░░  SALARIED WORKER TESTS  ░░
    // ══════════════════════════════════════════════════════════════

    // ── TC-S01 ─────────────────────────────────────────────────────
    /** Full month, perfect attendance — net = gross - pf - esi */
    public function test_salaried_full_month_perfect_attendance(): void
    {
        $month = '2026-05';   // May 2026: 31 calendar days

        // Mark present Mon–Sat every week (skip Sundays which are auto holidays)
        // We'll mark present on all non-Sunday days
        $days = [];
        $period = \Carbon\CarbonPeriod::create('2026-05-01', '2026-05-31');
        foreach ($period as $day) {
            if (!$day->isSunday()) {
                $days[$day->toDateString()] = 'present';
            }
        }
        $this->makeAttendance($this->salariedEmployee, $days);

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        $this->logPayoutTest(
            'TC-S01 | Salaried | Full month perfect attendance',
            $this->salariedEmployee,
            $month,
            $days,
            $result,
            ['expected_absent_days' => 0, 'expected_absent_deduction' => 0.0]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(0, $result['absent_days']);
        $this->assertEquals(0.0, $result['absent_deduction']);
        // Net = gross - pf - esi (no absent deduction)
        $expectedNet = round($result['gross_salary'] - $result['pf'] - $result['esi'], 2);
        $this->assertEquals($expectedNet, $result['net_salary']);
    }

    // ── TC-S02 ─────────────────────────────────────────────────────
    /** 2 absent days — deduction = 2 × (monthly_salary / 31) */
    public function test_salaried_two_absent_days_deduction(): void
    {
        $month = '2026-05';

        $days = [];
        $period = \Carbon\CarbonPeriod::create('2026-05-01', '2026-05-31');
        foreach ($period as $day) {
            if (!$day->isSunday()) {
                // mark 5th and 12th as absent
                $status = in_array($day->day, [5, 12]) ? 'absent' : 'present';
                $days[$day->toDateString()] = $status;
            }
        }
        $this->makeAttendance($this->salariedEmployee, $days);

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        // Expected per-day rate: total_monthly_paisa / 31 calendar days
        $totalMonthlyPaisa = array_sum([
            $this->salaryPackage['basic_salary'],
            $this->salaryPackage['hra'],
            $this->salaryPackage['conveyance'],
            $this->salaryPackage['medical'],
        ]);
        $perDayRupee       = ($totalMonthlyPaisa / 100) / 31;
        $expectedDeduction = round($perDayRupee * 2, 2);

        $this->logPayoutTest(
            'TC-S02 | Salaried | 2 absent days deduction',
            $this->salariedEmployee,
            $month,
            $days,
            $result,
            [
                'expected_absent_days'      => 2,
                'expected_per_day_rate'     => round($perDayRupee, 4),
                'expected_absent_deduction' => $expectedDeduction,
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(2, $result['absent_days']);
        $this->assertEqualsWithDelta($expectedDeduction, $result['absent_deduction'], 1.0);
    }

    // ── TC-S03 ─────────────────────────────────────────────────────
    /** Holiday falls on a working day — employee absent that day, should NOT be counted as absent */
    public function test_salaried_absent_on_holiday_not_deducted(): void
    {
        $month = '2026-05';

        // Create a DB holiday on May 20 (Wednesday)
        Holiday::factory()->create(['holiday_date' => '2026-05-20', 'title' => 'Test Holiday']);

        $days = [];
        $period = \Carbon\CarbonPeriod::create('2026-05-01', '2026-05-31');
        foreach ($period as $day) {
            if (!$day->isSunday() && $day->toDateString() !== '2026-05-20') {
                $days[$day->toDateString()] = 'present';
            }
            // May 20 intentionally NOT marked — simulate absent on holiday
        }
        $this->makeAttendance($this->salariedEmployee, $days);

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        $this->logPayoutTest(
            'TC-S03 | Salaried | Absent on holiday — no deduction',
            $this->salariedEmployee,
            $month,
            $days,
            $result,
            ['expected_absent_days' => 0, 'note' => 'May 20 is holiday so not counted as absent']
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(0, $result['absent_days']);
        $this->assertEquals(0.0, $result['absent_deduction']);
    }

    // ── TC-S04 ─────────────────────────────────────────────────────
    /** Joining mid-month (May 15) — prorated salary */
    public function test_salaried_joining_mid_month_prorated(): void
    {
        $month = '2026-05';

        $this->salariedEmployee->update(['join_date' => '2026-05-15']);

        $days = [];
        $period = \Carbon\CarbonPeriod::create('2026-05-15', '2026-05-31');
        foreach ($period as $day) {
            if (!$day->isSunday()) {
                $days[$day->toDateString()] = 'present';
            }
        }
        $this->makeAttendance($this->salariedEmployee, $days);

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        $this->logPayoutTest(
            'TC-S04 | Salaried | Joining mid-month May 15 — prorated',
            $this->salariedEmployee,
            $month,
            $days,
            $result,
            [
                'expected_is_prorated'         => true,
                'expected_proration_start_date' => '2026-05-15',
                'expected_proration_end_date'   => '2026-05-31',
                'note' => 'Multiplier = working_days(15→31) / working_days(1→31)',
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertTrue($result['is_prorated']);
        $this->assertEquals('2026-05-15', $result['proration_start_date']->toDateString());
        $this->assertEquals('2026-05-31', $result['proration_end_date']->toDateString());
        // Net salary must be less than full month
        $fullMonthGross = ($this->salaryPackage['basic_salary'] + $this->salaryPackage['hra']
            + $this->salaryPackage['conveyance'] + $this->salaryPackage['medical']) / 100;
        $this->assertLessThan($fullMonthGross, $result['gross_salary']);
    }

    // ── TC-S05 ─────────────────────────────────────────────────────
    /** Leaving mid-month (May 20) — prorated salary */
    public function test_salaried_leaving_mid_month_prorated(): void
    {
        $month = '2026-05';

        $this->salariedEmployee->update(['employee_status' => 'exit']);

        $days = [];
        $period = \Carbon\CarbonPeriod::create('2026-05-01', '2026-05-20');
        foreach ($period as $day) {
            if (!$day->isSunday()) {
                $days[$day->toDateString()] = 'present';
            }
        }
        $this->makeAttendance($this->salariedEmployee, $days);

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        $this->logPayoutTest(
            'TC-S05 | Salaried | Leaving mid-month May 20 — prorated',
            $this->salariedEmployee,
            $month,
            $days,
            $result,
            [
                'expected_is_prorated'       => true,
                'expected_proration_end_date' => '2026-05-20',
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertTrue($result['is_prorated']);
        $this->assertEquals('2026-05-20', $result['proration_end_date']->toDateString());
    }

    // ── TC-S06 ─────────────────────────────────────────────────────
    /** Late deduction: first 3 late days free, 4th onwards = half day per late */
    public function test_salaried_late_deduction_threshold(): void
    {
        $month = '2026-05';

        $days = [];
        $period = \Carbon\CarbonPeriod::create('2026-05-01', '2026-05-31');
        $lateMarked = 0;
        foreach ($period as $day) {
            if (!$day->isSunday()) {
                // first 5 working days: mark as late
                if ($lateMarked < 5) {
                    $days[$day->toDateString()] = ['status' => 'present', 'late' => true];
                    $lateMarked++;
                } else {
                    $days[$day->toDateString()] = 'present';
                }
            }
        }
        $this->makeAttendance($this->salariedEmployee, $days);

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        // 5 late days: 3 free, 2 charged at half day per day
        $totalMonthlyPaisa = $this->salaryPackage['basic_salary'] + $this->salaryPackage['hra']
            + $this->salaryPackage['conveyance'] + $this->salaryPackage['medical'];
        $perDayRupee        = ($totalMonthlyPaisa / 100) / 31;
        $expectedLateDed    = round(($perDayRupee / 2) * 2, 2); // 2 chargeable late days

        $this->logPayoutTest(
            'TC-S06 | Salaried | 5 late days — 2 charged (3 free threshold)',
            $this->salariedEmployee,
            $month,
            $days,
            $result,
            [
                'late_days_total'        => 5,
                'late_days_free'         => 3,
                'late_days_charged'      => 2,
                'expected_late_deduction' => $expectedLateDed,
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(5, $result['late_days']);
        $this->assertEqualsWithDelta($expectedLateDed, $result['late_deduction'], 1.0);
    }

    // ── TC-S07 ─────────────────────────────────────────────────────
    /** Overtime calculation */
    public function test_salaried_overtime_calculation(): void
    {
        $month = '2026-05';
        $overtimeMinutes = 120; // 2 hours

        $days = ['2026-05-05' => ['status' => 'present', 'overtime' => $overtimeMinutes]];
        $this->makeAttendance($this->salariedEmployee, $days);

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        $totalMonthlyPaisa = $this->salaryPackage['basic_salary'] + $this->salaryPackage['hra']
            + $this->salaryPackage['conveyance'] + $this->salaryPackage['medical'];
        $perDayRupee     = ($totalMonthlyPaisa / 100) / 31;
        $expectedOT      = round(($perDayRupee / 8 / 60) * $overtimeMinutes, 2);

        $this->logPayoutTest(
            'TC-S07 | Salaried | Overtime 120 min calculation',
            $this->salariedEmployee,
            $month,
            $days,
            $result,
            [
                'overtime_minutes'      => $overtimeMinutes,
                'per_day_rate'          => round($perDayRupee, 4),
                'expected_overtime_amt' => $expectedOT,
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEqualsWithDelta($expectedOT, $result['overtime_amount'], 0.5);
    }

    // ── TC-S08 ─────────────────────────────────────────────────────
    /** Already paid this month — blocked */
    public function test_salaried_already_paid_this_month_blocked(): void
    {
        $month = '2026-05';

        $this->makePaidPayout($this->salariedEmployee, '2026-05-10');

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        $this->logPayoutTest(
            'TC-S08 | Salaried | Already paid May — blocked',
            $this->salariedEmployee,
            $month,
            [],
            $result,
            ['expected' => 'error with already paid message']
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Already paid', $result['error']);
    }

    // ── TC-S09 ─────────────────────────────────────────────────────
    /** Paid previous month — current month is eligible */
    public function test_salaried_paid_previous_month_current_eligible(): void
    {
        $month = '2026-05';

        // Paid in April — should NOT block May
        $this->makePaidPayout($this->salariedEmployee, '2026-04-30');

        $days = ['2026-05-04' => 'present', '2026-05-05' => 'present'];
        $this->makeAttendance($this->salariedEmployee, $days);

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        $this->logPayoutTest(
            'TC-S09 | Salaried | Paid April — May still eligible',
            $this->salariedEmployee,
            $month,
            $days,
            $result,
            ['expected' => 'no error, May payout proceeds normally']
        );

        $this->assertArrayNotHasKey('error', $result);
    }

    // ── TC-S10 ─────────────────────────────────────────────────────
    /** Zero attendance — full absent deduction, net could be negative */
    public function test_salaried_zero_attendance_full_absent_deduction(): void
    {
        $month = '2026-05';
        // No attendance records created at all

        $result = Payout::calculateForEmployee($this->salariedEmployee, $month);

        $this->logPayoutTest(
            'TC-S10 | Salaried | Zero attendance — all absent',
            $this->salariedEmployee,
            $month,
            [],
            $result,
            ['expected' => 'gross = full salary, absent_deduction = full salary, net <= 0']
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(0, $result['present_days']);
        // Absent deduction >= gross (full deduction)
        $this->assertGreaterThanOrEqual($result['gross_salary'], $result['absent_deduction']);
    }

    // ══════════════════════════════════════════════════════════════
    // ░░  DAILY WORKER TESTS  ░░
    // ══════════════════════════════════════════════════════════════

    // ── TC-D01 ─────────────────────────────────────────────────────
    /** Full month present — gross = sum of all daily components × 31 days (minus Sundays attendance) */
    public function test_daily_worker_full_month_present(): void
    {
        $month = '2026-05'; // 31 days

        $days = [];
        $period = \Carbon\CarbonPeriod::create('2026-05-01', '2026-05-31');
        foreach ($period as $day) {
            if (!$day->isSunday()) {
                $days[$day->toDateString()] = 'present';
            }
        }
        $this->makeAttendance($this->dailyEmployee, $days);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        // Expected: daily_basic = 3000000/31 paisa per day, present = 26 days (31 - 5 sundays)
        $sundays     = 5; // May 2026 has 5 Sundays: 3,10,17,24,31
        $presentDays = 31 - $sundays;
        $dailyBasic  = (3_000_000 / 31 / 100);   // rupees per day
        $dailyHra    = (500_000  / 31 / 100);
        $dailyConv   = (200_000  / 31 / 100);
        $dailyMed    = (100_000  / 31 / 100);
        $expectedGross = round(($dailyBasic + $dailyHra + $dailyConv + $dailyMed) * $presentDays, 2);

        $this->logPayoutTest(
            'TC-D01 | Daily | Full month present (all non-Sunday)',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            [
                'present_days_expected' => $presentDays,
                'daily_basic_rupee'     => round($dailyBasic, 4),
                'expected_gross'        => $expectedGross,
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals($presentDays, $result['present_days']);
        $this->assertEqualsWithDelta($expectedGross, $result['gross_salary'], 1.0);
    }

    // ── TC-D02 ─────────────────────────────────────────────────────
    /** Zero attendance — everything should be 0 */
    public function test_daily_worker_zero_attendance_zero_pay(): void
    {
        $month = '2026-05';
        // No attendance at all

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $this->logPayoutTest(
            'TC-D02 | Daily | Zero attendance — zero pay',
            $this->dailyEmployee,
            $month,
            [],
            $result,
            ['expected' => 'all financial fields = 0']
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(0, $result['present_days']);
        $this->assertEquals(0.0, $result['gross_salary']);
        $this->assertEquals(0.0, $result['net_salary']);
        $this->assertEquals(0.0, $result['pf']);
        $this->assertEquals(0.0, $result['esi']);
        $this->assertEquals(0.0, $result['total_deductions']);
    }

    // ── TC-D03 ─────────────────────────────────────────────────────
    /** Joining mid-month (May 19) — only attendance from 19th onwards counts */
    public function test_daily_worker_joining_mid_month_may_19(): void
    {
        $month = '2026-05';

        $this->dailyEmployee->update(['join_date' => '2026-05-19']);

        // Mark present on 20th and 21st (2 days)
        $days = [
            '2026-05-20' => 'present',
            '2026-05-21' => 'present',
        ];
        $this->makeAttendance($this->dailyEmployee, $days);

        // Also add a record before joining — should NOT be counted
        Attendance::factory()->create([
            'employee_id' => $this->dailyEmployee->id,
            'date'        => '2026-05-10',
            'status'      => \App\Enums\Attendance::PRESENT,
        ]);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $dailyRate     = (3_000_000 + 500_000 + 200_000 + 100_000) / 31 / 100;
        $expectedGross = round($dailyRate * 2, 2); // only 2 days (19th record before join is excluded)

        $this->logPayoutTest(
            'TC-D03 | Daily | Joining May 19 — only 2 days from 20th+21st count',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            [
                'joining_date'    => '2026-05-19',
                'eligible_start'  => '2026-05-19',
                'eligible_end'    => '2026-05-31',
                'present_expected' => 2,
                'expected_gross'  => $expectedGross,
                'note'            => 'May 10 attendance before joining must be excluded',
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(2, $result['present_days']);
        $this->assertEqualsWithDelta($expectedGross, $result['gross_salary'], 1.0);
    }

    // ── TC-D04 ─────────────────────────────────────────────────────
    /** 1 present day in month — pay = 1 × (monthly_salary / calendar_days) */
    public function test_daily_worker_single_day_present(): void
    {
        $month = '2026-05';

        $days = ['2026-05-15' => 'present'];
        $this->makeAttendance($this->dailyEmployee, $days);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $totalMonthlySalaryRupee = (3_000_000 + 500_000 + 200_000 + 100_000) / 100;
        $perDayRupee             = $totalMonthlySalaryRupee / 31;
        $expectedGross           = round($perDayRupee * 1, 2);
        $expectedPf              = round(($this->salaryPackage['pf'] / 100) * (1 / 31), 2);
        $expectedEsi             = round(($this->salaryPackage['esi'] / 100) * (1 / 31), 2);
        $expectedNet             = round($expectedGross - $expectedPf - $expectedEsi, 2);

        $this->logPayoutTest(
            'TC-D04 | Daily | 1 day present — exact pay check',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            [
                'present_days'         => 1,
                'daily_rate_rupee'     => round($perDayRupee, 4),
                'expected_gross'       => $expectedGross,
                'expected_pf'          => $expectedPf,
                'expected_esi'         => $expectedEsi,
                'expected_net'         => $expectedNet,
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(1, $result['present_days']);
        $this->assertEqualsWithDelta($expectedGross, $result['gross_salary'], 0.5);
        $this->assertEqualsWithDelta($expectedNet,   $result['net_salary'],   0.5);
    }

    // ── TC-D05 ─────────────────────────────────────────────────────
    /** Present on a Sunday — attendance marked, but Sunday shouldn't add to standard count
     *  (daily workers CAN work on Sunday and get paid for it) */
    public function test_daily_worker_present_on_sunday_gets_paid(): void
    {
        $month = '2026-05';

        // May 3 is a Sunday
        $days = ['2026-05-03' => 'present', '2026-05-05' => 'present'];
        $this->makeAttendance($this->dailyEmployee, $days);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $this->logPayoutTest(
            'TC-D05 | Daily | Present on Sunday May 3 — should earn pay',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            [
                'note'            => 'Daily worker present on Sunday still gets paid (attendance-driven)',
                'present_expected' => 2,
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        // Both days count as present and earn pay
        $this->assertEquals(2, $result['present_days']);
        $this->assertGreaterThan(0, $result['gross_salary']);
    }

    // ── TC-D06 ─────────────────────────────────────────────────────
    /** Already paid this month — blocked */
    public function test_daily_worker_already_paid_blocked(): void
    {
        $month = '2026-05';
        $this->makePaidPayout($this->dailyEmployee, '2026-05-15');

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $this->logPayoutTest(
            'TC-D06 | Daily | Already paid May — blocked',
            $this->dailyEmployee,
            $month,
            [],
            $result,
            ['expected' => 'error']
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Already paid', $result['error']);
    }

    // ── TC-D07 ─────────────────────────────────────────────────────
    /** PF and ESI scale with attendance ratio, not fixed */
    public function test_daily_worker_pf_esi_scale_with_attendance(): void
    {
        $month = '2026-05'; // 31 days

        // Present for exactly 10 days
        $dates      = ['05', '06', '07', '08', '12', '13', '14', '15', '19', '20'];
        $days       = [];
        foreach ($dates as $d) {
            $days["2026-05-{$d}"] = 'present';
        }
        $this->makeAttendance($this->dailyEmployee, $days);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $ratio       = 10 / 31;
        $expectedPf  = round(($this->salaryPackage['pf']  / 100) * $ratio, 2);
        $expectedEsi = round(($this->salaryPackage['esi'] / 100) * $ratio, 2);

        $this->logPayoutTest(
            'TC-D07 | Daily | PF & ESI scale with 10/31 ratio',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            [
                'present_days'  => 10,
                'ratio'         => round($ratio, 4),
                'expected_pf'   => $expectedPf,
                'expected_esi'  => $expectedEsi,
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(10, $result['present_days']);
        $this->assertEqualsWithDelta($expectedPf,  $result['pf'],  0.5);
        $this->assertEqualsWithDelta($expectedEsi, $result['esi'], 0.5);
    }

    // ── TC-D08 ─────────────────────────────────────────────────────
    /** Absent days = (eligible_calendar_days) - present_days, shown as integer */
    public function test_daily_worker_absent_days_is_integer(): void
    {
        $month = '2026-05';

        $days = ['2026-05-05' => 'present'];
        $this->makeAttendance($this->dailyEmployee, $days);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $this->logPayoutTest(
            'TC-D08 | Daily | Absent days must be integer (no floats)',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            ['expected_absent_days_type' => 'int', 'note' => 'No float values like 30.9999']
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertIsInt($result['absent_days'], 'absent_days must be integer');
        $this->assertIsInt($result['prorated_days'], 'prorated_days must be integer');
        // 31 eligible calendar days − 1 present = 30 absent
        $this->assertEquals(30, $result['absent_days']);
    }

    // ── TC-D09 ─────────────────────────────────────────────────────
    /** Overtime for daily worker */
    public function test_daily_worker_overtime_calculation(): void
    {
        $month           = '2026-05';
        $overtimeMinutes = 90;

        $days = ['2026-05-06' => ['status' => 'present', 'overtime' => $overtimeMinutes]];
        $this->makeAttendance($this->dailyEmployee, $days);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $totalDailyRupee = (3_000_000 + 500_000 + 200_000 + 100_000) / 31 / 100;
        $expectedOT      = round(($totalDailyRupee / 8 / 60) * $overtimeMinutes, 2);

        $this->logPayoutTest(
            'TC-D09 | Daily | Overtime 90 min',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            [
                'daily_rate_rupee'      => round($totalDailyRupee, 4),
                'overtime_minutes'      => $overtimeMinutes,
                'expected_overtime_amt' => $expectedOT,
            ]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEqualsWithDelta($expectedOT, $result['overtime_amount'], 0.5);
    }

    // ── TC-D10 ─────────────────────────────────────────────────────
    /** Paid previous month — current month eligible */
    public function test_daily_worker_paid_previous_month_current_eligible(): void
    {
        $month = '2026-05';

        $this->makePaidPayout($this->dailyEmployee, '2026-04-28');

        $days = ['2026-05-04' => 'present', '2026-05-05' => 'present'];
        $this->makeAttendance($this->dailyEmployee, $days);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $this->logPayoutTest(
            'TC-D10 | Daily | Paid April — May eligible',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            ['expected' => 'no error, 2 days paid']
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(2, $result['present_days']);
        $this->assertGreaterThan(0, $result['net_salary']);
    }

    // ── TC-D11 ─────────────────────────────────────────────────────
    /** Half day attendance counts as present (1 full day for pay) */
    public function test_daily_worker_half_day_counts_as_present(): void
    {
        $month = '2026-05';

        $days = ['2026-05-06' => 'half_day'];
        $this->makeAttendance($this->dailyEmployee, $days);

        $result = Payout::calculateForEmployee($this->dailyEmployee, $month);

        $this->logPayoutTest(
            'TC-D11 | Daily | Half day marked — counts as 1 present day for pay',
            $this->dailyEmployee,
            $month,
            $days,
            $result,
            ['expected_present_days' => 1]
        );

        $this->assertArrayNotHasKey('error', $result);
        $this->assertEquals(1, $result['present_days']);
        $this->assertGreaterThan(0, $result['gross_salary']);
    }
}
