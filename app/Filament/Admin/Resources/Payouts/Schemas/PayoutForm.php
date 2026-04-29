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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PayoutForm
{

    // ── Auto-fill salary data from Employee + Attendance ───────────
    protected static function recalculate(Get $get, Set $set): void
    {
        $employeeId = $get('employee_id');
        $month      = $get('payout_month');

        if (! $employeeId || ! $month || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return;
        }

        $employee = Employee::find($employeeId);
        if (! $employee) return;

        $data = Payout::calculateForEmployee($employee, $month);

        foreach ($data as $key => $value) {
            if (!in_array($key, ['status', 'payout_type'])) {
                $set($key, $value);
            }
        }
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payout Period')
                ->columns(3)
                ->schema([
                    Select::make('employee_id')
                        ->label('Employee')
                        ->relationship('employee', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            self::recalculate($get, $set);
                        }),

                    TextInput::make('payout_month')
                        ->label('Month (YYYY-MM)')
                        ->placeholder('2025-01')
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

            // ── Section 2: Attendance Summary (read-only) ───────────
            Section::make('Attendance Summary')
                ->columns(5)
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

            // ── Section 3: Earnings ─────────────────────────────────
            Section::make('Earnings')
                ->columns(3)
                ->schema([
                    TextInput::make('basic_salary')
                        ->label('Basic Salary')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('hra')
                        ->label('HRA')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('conveyance')
                        ->label('Conveyance')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('medical')
                        ->label('Medical')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('other_allowances')
                        ->label('Other Allowances')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('overtime_amount')
                        ->label('Overtime Amount')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('gross_salary')
                        ->label('Gross Salary')
                        ->numeric()->prefix('₹')
                        ->readOnly()
                        ->extraInputAttributes(['class' => 'font-bold text-green-600']),
                ]),

            // ── Section 4: Deductions ───────────────────────────────
            Section::make('Deductions')
                ->columns(3)
                ->schema([
                    TextInput::make('pf')
                        ->label('PF')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('esi')
                        ->label('ESI')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('absent_deduction')
                        ->label('Absent Deduction')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('late_deduction')
                        ->label('Late Deduction')
                        ->numeric()->prefix('₹')->readOnly(),
                    TextInput::make('other_deductions')
                        ->label('Other Deductions')
                        ->numeric()->prefix('₹')
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            // Recalculate total_deductions and net when other_deductions changes
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
                        ->numeric()->prefix('₹')
                        ->readOnly()
                        ->extraInputAttributes(['class' => 'font-bold text-red-600']),
                ]),

            // ── Section 5: Net Pay & Payment Info ──────────────────
            Section::make('Net Pay & Payment')
                ->columns(3)
                ->schema([
                    TextInput::make('net_salary')
                        ->label('Net Salary')
                        ->numeric()->prefix('₹')
                        ->readOnly()
                        ->extraInputAttributes(['class' => 'text-xl font-bold text-primary-600']),
                    Select::make('payout_type')
                        ->label('Payment Mode')
                        ->options(PayoutType::class),
                    DatePicker::make('paid_on')
                        ->label('Paid On'),
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
