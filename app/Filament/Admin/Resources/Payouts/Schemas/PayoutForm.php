<?php

namespace App\Filament\Admin\Resources\Payouts\Schemas;

use App\Enums\PayoutStatus;

use App\Models\Employee;
use App\Models\Payout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use App\Enums\PayoutType;

class PayoutForm
{
    // ── Auto-fill salary data from Employee + Attendance ───────────
    protected static function recalculate(Get $get, Set $set): void
    {
        $employeeId = $get('employee_id');
        $month      = $get('payout_month');

        if (!$employeeId || !$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            self::clearFields($set);
            return;
        }

        $employee = Employee::find($employeeId);
        if (!$employee) return;

        $isDaily = $employee->payout_type === 'day_worker';

        // Check eligibility
        $eligibility = Payout::calculateEligibilityPeriod($employee, $month);

        if ($eligibility['eligible_days'] === 0 && $eligibility['reason']) {
            $set('eligibility_message', '⚠️ ' . $eligibility['reason']);
            $set('is_prorated', false);
            $set('proration_start_date', $eligibility['start_date']);
            $set('proration_end_date', $eligibility['end_date']);
            $set('prorated_days', 0);
            self::clearSalaryFields($set);
            return;
        }

        // Run the appropriate calculation
        $data = Payout::calculateForEmployee($employee, $month);

        if (isset($data['error'])) {
            $set('eligibility_message', '❌ ' . $data['error']);
            self::clearSalaryFields($set);
            return;
        }

        // ── Proration / eligibility status message ──────────────────
        if ($isDaily) {
            // Daily workers: show eligible period (joining_date → month_end) and days present
            $set('is_prorated', false);
            $set('proration_start_date', $data['proration_start_date']);
            $set('proration_end_date', $data['proration_end_date']);
            $set('prorated_days', $data['prorated_days']);  // calendar days in eligible period

            $set('eligibility_message', sprintf(
                '✓ Daily Worker — Eligible: %s to %s (%d days) | Present: %d days | Daily wage: ₹%s',
                $data['proration_start_date']->format('d M Y'),
                $data['proration_end_date']->format('d M Y'),
                $data['prorated_days'],
                $data['present_days'],
                number_format($data['_daily_wage_rupee'] ?? 0, 2)
            ));
        } else {
            // Monthly workers: show proration details if applicable
            $set('is_prorated', $data['is_prorated'] ?? false);
            $set('proration_start_date', $data['proration_start_date']);
            $set('proration_end_date', $data['proration_end_date']);
            $set('prorated_days', $data['prorated_days']);

            if ($data['is_prorated']) {
                $set('eligibility_message', sprintf(
                    '✓ Prorated Salary: %d working days (%s to %s) | Multiplier: %.2f%%',
                    $data['prorated_days'],
                    $data['proration_start_date']->format('d M Y'),
                    $data['proration_end_date']->format('d M Y'),
                    ($data['proratio_multiplier'] ?? 0) * 100
                ));
            } else {
                $set('eligibility_message', sprintf(
                    '✓ Full month eligible — %d working days | Per-day rate: ₹%s',
                    $data['total_working_days'],
                    number_format($data['_per_day_rate_rupee'] ?? 0, 2)
                ));
            }
        }

        // ── Push all salary fields to form ─────────────────────────
        $skip = [
            'status', 'payout_type', 'is_prorated',
            'proration_start_date', 'proration_end_date',
            'prorated_days', 'proratio_multiplier', 'error',
            '_calendar_days', '_per_day_rate_rupee',
            '_daily_wage_rupee', '_holiday_count',
        ];

        foreach ($data as $key => $value) {
            if (!in_array($key, $skip)) {
                $set($key, $value);
            }
        }
    }

    // ── Clear all computed fields ───────────────────────────────────
    protected static function clearFields(Set $set): void
    {
        $set('eligibility_message', null);
        $set('is_prorated', false);
        $set('proration_start_date', null);
        $set('proration_end_date', null);
        $set('prorated_days', null);
        self::clearSalaryFields($set);
    }

    protected static function clearSalaryFields(Set $set): void
    {
        foreach ([
            'basic_salary', 'hra', 'conveyance', 'medical', 'other_allowances',
            'overtime_amount', 'gross_salary', 'pf', 'esi', 'absent_deduction',
            'late_deduction', 'other_deductions', 'total_deductions', 'net_salary',
            'total_working_days', 'present_days', 'absent_days', 'late_days', 'overtime_minutes',
        ] as $field) {
            $set($field, null);
        }
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([

                    // ── Step 1: Selection ───────────────────────────────────
                    Wizard\Step::make('Payout Selection')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('branch_id')
                                        ->label('Branch')
                                        ->relationship('branch', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->required(),

                                    Select::make('employee_id')
                                        ->label('Employee')
                                        ->relationship('employee', 'name', function ($query, Get $get) {
                                            $branchId = $get('branch_id');
                                            $query->where('is_active', true);
                                            if ($branchId) {
                                                $query->where('branch_id', $branchId);
                                            }
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::recalculate($get, $set)),

                                    TextInput::make('payout_month')
                                        ->label('Month (YYYY-MM)')
                                        ->placeholder('2026-01')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::recalculate($get, $set)),

                                    Select::make('status')
                                        ->label('Status')
                                        ->options(PayoutStatus::class)
                                        ->default(PayoutStatus::Draft)
                                        ->required(),
                                ]),

                            // Eligibility & Proration Info
                            Section::make('Eligibility Status')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('eligibility_message')
                                        ->label('Status')
                                        ->disabled()
                                        ->helperText(function (Get $get): string {
                                            $employeeId = $get('employee_id');
                                            if (!$employeeId) {
                                                return 'Select an employee and month to see eligibility details.';
                                            }
                                            $employee = Employee::find($employeeId);
                                            if (!$employee) return 'Employee not found.';

                                            return $employee->payout_type === 'day_worker'
                                                ? 'Daily worker: full salary minus all absent days (including holidays/Sundays).'
                                                : 'Monthly worker: full salary minus absent working days. Holidays & Sundays are paid rest days.';
                                        })
                                        ->columnSpanFull(),

                                    Toggle::make('is_prorated')
                                        ->label('Is Prorated Salary?')
                                        ->disabled()
                                        ->hint('Auto-set for monthly workers who join/leave mid-month')
                                        ->visible(function (Get $get): bool {
                                            $employeeId = $get('employee_id');
                                            if (!$employeeId) return false;
                                            $employee = Employee::find($employeeId);
                                            return $employee?->payout_type !== 'day_worker';
                                        }),

                                    DatePicker::make('proration_start_date')
                                        ->label('Eligibility Start Date')
                                        ->disabled()
                                        ->columnSpan(1),

                                    DatePicker::make('proration_end_date')
                                        ->label('Eligibility End Date')
                                        ->disabled()
                                        ->columnSpan(1),

                                    TextInput::make('prorated_days')
                                        ->label(function (Get $get): string {
                                            $employeeId = $get('employee_id');
                                            if (!$employeeId) return 'Eligible Days';
                                            $employee = Employee::find($employeeId);
                                            return $employee?->payout_type === 'day_worker'
                                                ? 'Eligible Worked Days(Daily)'
                                                : 'Eligible Working Days(Monthly)';
                                        })
                                        ->disabled()
                                        ->numeric()
                                        ->columnSpan(1),
                                ]),
                        ]),

                    // ── Step 2: Attendance ──────────────────────────────────
                    Wizard\Step::make('Attendance Summary')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            Grid::make(5)
                                ->schema([
                                    TextInput::make('total_working_days')
                                        ->label('Working Days')
                                        ->helperText('Excl. Sundays & holidays')
                                        ->numeric()->readOnly(),
                                    TextInput::make('present_days')
                                        ->label('Present')
                                        ->numeric()->readOnly(),
                                    TextInput::make('absent_days')
                                        ->label('Absent')
                                        ->helperText('On working days only')
                                        ->numeric()->readOnly(),
                                    TextInput::make('late_days')
                                        ->label('Late Days')
                                        ->numeric()->readOnly(),
                                    TextInput::make('overtime_minutes')
                                        ->label('OT Minutes')
                                        ->numeric()->readOnly(),
                                ]),
                        ]),

                    // ── Step 3: Earnings & Deductions ───────────────────────
                    Wizard\Step::make('Earnings & Deductions')
                        ->icon('heroicon-o-currency-rupee')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Section::make('Earnings')
                                        ->columnSpan(1)
                                        ->description(function (Get $get): string {
                                            $employeeId = $get('employee_id');
                                            if (!$employeeId) return '';
                                            $employee = Employee::find($employeeId);
                                            return $employee?->payout_type === 'day_worker'
                                                ? 'Daily worker: amounts match employee table, deductions applied below'
                                                : 'Monthly worker: amounts are prorated if applicable';
                                        })
                                        ->schema([
                                            TextInput::make('basic_salary')
                                                ->label('Basic Salary')
                                                ->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('hra')->label('HRA')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('conveyance')->label('Conveyance')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('medical')->label('Medical')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('other_allowances')->label('Other Allowances')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('overtime_amount')->label('Overtime Amount')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('gross_salary')
                                                ->label('Gross Salary')
                                                ->numeric()->prefix('₹')->readOnly()
                                                ->extraInputAttributes(['class' => 'text-xl font-bold text-green-600 dark:text-green-400']),
                                        ]),

                                    Section::make('Deductions')
                                        ->columnSpan(1)
                                        ->description(function (Get $get): string {
                                            $employeeId = $get('employee_id');
                                            if (!$employeeId) return '';
                                            $employee = Employee::find($employeeId);
                                            return $employee?->payout_type === 'day_worker'
                                                ? 'Daily worker: absent deduction includes unpaid rest days'
                                                : 'Monthly worker: PF & ESI are prorated if applicable';
                                        })
                                        ->schema([
                                            TextInput::make('pf')->label('PF')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('esi')->label('ESI')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('absent_deduction')
                                                ->label('Absent Deduction')
                                                ->numeric()->prefix('₹')->readOnly()
                                                ->helperText(function (Get $get): string {
                                                    $employeeId = $get('employee_id');
                                                    if (!$employeeId) return '';
                                                    $employee = Employee::find($employeeId);
                                                    return $employee?->payout_type === 'day_worker'
                                                        ? 'Absent Days × (Monthly Salary ÷ calendar days) (Includes Sundays/Holidays)'
                                                        : 'absent_days × (monthly_salary ÷ calendar_days)';
                                                }),
                                            TextInput::make('late_deduction')->label('Late Deduction')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('other_deductions')
                                                ->label('Other Deductions')
                                                ->numeric()->prefix('₹')
                                                ->live()
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    $total = ($get('pf') ?? 0)
                                                        + ($get('esi') ?? 0)
                                                        + ($get('absent_deduction') ?? 0)
                                                        + ($get('late_deduction') ?? 0)
                                                        + ($get('other_deductions') ?? 0);
                                                    $set('total_deductions', round($total, 2));
                                                    $set('net_salary', round(($get('gross_salary') ?? 0) - $total, 2));
                                                }),
                                            TextInput::make('total_deductions')
                                                ->label('Total Deductions')
                                                ->numeric()->prefix('₹')->readOnly()
                                                ->extraInputAttributes(['class' => 'text-xl font-bold text-red-600 dark:text-red-400']),
                                        ]),
                                ]),
                        ]),

                    // ── Step 4: Net Pay & Payment ───────────────────────────
                    Wizard\Step::make('Net Pay & Payment')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('net_salary')
                                        ->label('Net Salary')
                                        ->numeric()->prefix('₹')
                                        ->readOnly()
                                        ->extraInputAttributes(['class' => 'text-2xl font-black text-primary-600 dark:text-primary-400']),
                                    Select::make('payout_type')
                                        ->label('Payment Mode')
                                        ->options(PayoutType::class),
                                    DatePicker::make('paid_on')
                                        ->label('Paid On'),
                                ]),
                            Textarea::make('remarks')
                                ->label('Remarks')
                                ->columnSpanFull()
                                ->helperText('Add any notes about the payout (e.g., partial payment, adjustment reason)'),
                        ]),

                ])->columnSpanFull()
            ]);
    }
}