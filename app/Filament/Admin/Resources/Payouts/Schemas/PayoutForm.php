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
                                        ->schema([
                                            TextInput::make('basic_salary')->label('Basic Salary')->numeric()->prefix('₹')->readOnly(),
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
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull()
            ]);
    }
}
