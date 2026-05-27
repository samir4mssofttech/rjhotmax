<?php

namespace App\Filament\Admin\Resources\Payouts\Schemas;

use App\Enums\PayoutStatus;
use App\Enums\PayoutType;
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

class PayoutForm
{
    // ── Auto-fill salary data from Employee + Attendance ───────────
    protected static function recalculate(Get $get, Set $set): void
    {
        $employeeId = $get('employee_id');
        $month = $get('payout_month');

        if (!$employeeId || !$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
            $set('eligibility_message', null);
            $set('is_prorated', false);
            $set('proration_start_date', null);
            $set('proration_end_date', null);
            $set('prorated_days', null);
            return;
        }

        $employee = Employee::find($employeeId);
        if (!$employee) return;

        // Calculate eligibility first
        $eligibility = Payout::calculateEligibilityPeriod($employee, $month);
        
        // Check if eligible
        if ($eligibility['eligible_days'] === 0 && $eligibility['reason']) {
            $set('eligibility_message', '⚠️ ' . $eligibility['reason']);
            $set('is_prorated', true);
            $set('proration_start_date', $eligibility['start_date']);
            $set('proration_end_date', $eligibility['end_date']);
            $set('prorated_days', 0);
            
            // Clear all salary fields
            $data = Payout::calculateForEmployee($employee, $month);
            if (isset($data['error'])) {
                foreach (['basic_salary', 'hra', 'conveyance', 'medical', 'other_allowances', 
                         'overtime_amount', 'gross_salary', 'pf', 'esi', 'absent_deduction', 
                         'late_deduction', 'other_deductions', 'total_deductions', 'net_salary',
                         'total_working_days', 'present_days', 'absent_days', 'late_days', 'overtime_minutes'] as $field) {
                    $set($field, null);
                }
            }
            return;
        }

        // Calculate salary with proration
        $data = Payout::calculateForEmployee($employee, $month);

        if (isset($data['error'])) {
            $set('eligibility_message', '❌ ' . $data['error']);
            return;
        }

        // Set proration info
        $set('is_prorated', $data['is_prorated'] ?? false);
        $set('proration_start_date', $data['proration_start_date']);
        $set('proration_end_date', $data['proration_end_date']);
        $set('prorated_days', $data['prorated_days']);

        // Set eligibility message
        if ($data['is_prorated']) {
            $message = sprintf(
                '✓ Prorated Salary: %d days (%s to %s) with %.2f%% multiplier',
                $data['prorated_days'],
                $data['proration_start_date']->format('Y-m-d'),
                $data['proration_end_date']->format('Y-m-d'),
                ($data['proratio_multiplier'] ?? 0) * 100
            );
            $set('eligibility_message', $message);
        } else {
            $set('eligibility_message', '✓ Full month eligible (26 days)');
        }

        // Set all salary fields
        foreach ($data as $key => $value) {
            if (!in_array($key, ['status', 'payout_type', 'is_prorated', 'proration_start_date', 
                                'proration_end_date', 'prorated_days', 'proratio_multiplier', 'error'])) {
                $set($key, $value);
            }
        }
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
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

                                            // Show only active employees
                                            $query->where('is_active', true);

                                            if ($branchId) {
                                                $query->where('branch_id', $branchId);
                                            }
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalculate($get, $set);
                                        }),

                                    TextInput::make('payout_month')
                                        ->label('Month (YYYY-MM)')
                                        ->placeholder('2026-01')
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::recalculate($get, $set);
                                        }),

                                    Select::make('status')
                                        ->label('Status')
                                        ->options(PayoutStatus::class)
                                        ->default(PayoutStatus::Draft)
                                        ->required(),
                                ]),

                            // Eligibility and Proration Info
                            Section::make('Eligibility Status')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('eligibility_message')
                                        ->label('Status')
                                        ->disabled()
                                        ->helperText('Shows eligibility and proration details')
                                        ->columnSpanFull(),

                                    Toggle::make('is_prorated')
                                        ->label('Is Prorated Salary?')
                                        ->disabled()
                                        ->hint('Auto-calculated based on joining/leaving dates and last paid date'),

                                    DatePicker::make('proration_start_date')
                                        ->label('Eligibility Start Date')
                                        ->disabled()
                                        ->columnSpan(1),

                                    DatePicker::make('proration_end_date')
                                        ->label('Eligibility End Date')
                                        ->disabled()
                                        ->columnSpan(1),

                                    TextInput::make('prorated_days')
                                        ->label('Eligible Days')
                                        ->disabled()
                                        ->numeric()
                                        ->columnSpan(1),
                                ]),
                        ]),

                    Wizard\Step::make('Attendance Summary')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            Grid::make(5)
                                ->schema([
                                    TextInput::make('total_working_days')
                                        ->label('Working Days')
                                        ->numeric()->readOnly(),
                                    TextInput::make('present_days')
                                        ->label('Present')
                                        ->numeric()->readOnly(),
                                    TextInput::make('absent_days')
                                        ->label('Absent')
                                        ->numeric()->readOnly(),
                                    TextInput::make('late_days')
                                        ->label('Late Days')
                                        ->numeric()->readOnly(),
                                    TextInput::make('overtime_minutes')
                                        ->label('OT Minutes')
                                        ->numeric()->readOnly(),
                                ]),
                        ]),

                    Wizard\Step::make('Earnings & Deductions')
                        ->icon('heroicon-o-currency-rupee')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Section::make('Earnings')
                                        ->columnSpan(1)
                                        ->description('Note: All amounts are prorated if applicable')
                                        ->schema([
                                            TextInput::make('basic_salary')
                                                ->label('Basic Salary')
                                                ->numeric()->prefix('₹')
                                                ->readOnly(),
                                            TextInput::make('hra')
                                                ->label('HRA')->numeric()
                                                ->prefix('₹')->readOnly(),
                                            TextInput::make('conveyance')
                                                ->label('Conveyance')->numeric()
                                                ->prefix('₹')->readOnly(),
                                            TextInput::make('medical')
                                                ->label('Medical')->numeric()
                                                ->prefix('₹')->readOnly(),
                                            TextInput::make('other_allowances')
                                                ->label('Other Allowances')->numeric()
                                                ->prefix('₹')->readOnly(),
                                            TextInput::make('overtime_amount')
                                                ->label('Overtime Amount')->numeric()
                                                ->prefix('₹')->readOnly(),
                                            TextInput::make('gross_salary')
                                                ->label('Gross Salary')
                                                ->numeric()->prefix('₹')->readOnly()
                                                ->extraInputAttributes(['class' => 'text-xl font-bold text-green-600 dark:text-green-400']),
                                        ]),

                                    Section::make('Deductions')
                                        ->columnSpan(1)
                                        ->description('Note: PF & ESI are prorated if applicable')
                                        ->schema([
                                            TextInput::make('pf')->label('PF')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('esi')->label('ESI')->numeric()->prefix('₹')->readOnly(),
                                            TextInput::make('absent_deduction')->label('Absent Deduction')->numeric()->prefix('₹')->readOnly(),
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