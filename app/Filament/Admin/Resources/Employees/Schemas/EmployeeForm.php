<?php

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
use App\Enums\ShiftType;
use App\Helpers\CurrencyHelper;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TimePicker;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('profile_photo')
                    ->label('Profile Photo')
                    ->image()
                    ->avatar()
                    ->directory('employee-photos')
                    ->disk('public'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->maxLength(255)
                    ->unique()
                    ->default(null),
                TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->display_name)
                    ->native(false),
                Select::make('shift_id')
                    ->relationship('shift', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('type')
                                ->options(ShiftType::class)
                                ->required(),

                            TimePicker::make('start_time')
                                ->seconds(false)
                                ->required(),

                            TimePicker::make('end_time')
                                ->seconds(false)
                                ->required(),

                        ]),
                    ]),

                DatePicker::make('join_date')
                    ->default(now())
                    ->required(),

                Select::make('skill_type')
                    ->options([
                        'Skilled' => 'Skilled',
                        'Semi-Skilled' => 'Semi-Skilled',
                        "Fully-Skilled" => "Fully-Skilled",
                        'Unskilled' => 'Unskilled',
                    ])
                    ->native(false)
                    ->required(),

                // --- MODIFIED SALARY FIELD ---
                TextInput::make('salary')
                    ->numeric()
                    ->label('CTC')
                    ->prefix('₹')
                    ->default(0)
                    ->debounce(500)
                    ->live() // Makes the field reactive
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if (!$state) return;

                        $salary = (float) $state;

                        // Calculate percentages
                        // $set('basic_salary', $salary * 0.50);      // 40%  -> 50%
                        // $set('hra', $salary * 0.10);               // 20%  -> 10%
                        // $set('conveyance', $salary * 0.08);        // 8%
                        // $set('medical', $salary * 0.20);           // 20%
                        // $set('other_allowances', $salary * 0.12);  // 12%
                        // $set('pf', $salary * 0.12);  // 12%
                        // $set('esi', $salary * 0.0075);  // 12%

                        // First calculate salary components
                        $basic = $salary * 0.50;
                        $hra = $salary * 0.10;
                        $conveyance = $salary * 0.08;
                        $medical = $salary * 0.20;
                        $other = $salary * 0.12;

                        // Set components
                        $set('basic_salary', $basic);
                        $set('hra', $hra);
                        $set('conveyance', $conveyance);
                        $set('medical', $medical);
                        $set('other_allowances', $other);

                        // ✅ PF = 12% of BASIC SALARY
                        $set('pf', $basic * 0.12);

                        // ✅ ESI = 0.75% of GROSS (CTC here)
                        $set('esi', $salary * 0.0075);
                    })
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null),
                // ----------------------------
                TextInput::make('security_money')
                    ->numeric()
                    ->prefix('₹')
                    ->label('Security Money')
                    ->default(0)
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null),
                Select::make('payout_type')
                    ->label('Payout Type')
                    ->native(false)
                    ->options([
                        'salaried' => 'Salaried(Monthly)',
                        'day_worker' => 'Day Worker(Daily)',
                    ])
                    ->required()
                    ->default(null),

                ToggleButtons::make('employee_status')
                    ->options(EmployeeStatus::class)
                    ->inline()
                    ->grouped()
                    ->default(EmployeeStatus::ACTIVE)
                    ->required()
                    ->live(),

                DatePicker::make('exit_date')
                    ->label('Date of Exit')
                    ->required()
                    ->visible(fn(Get $get) => $get('employee_status') === EmployeeStatus::EXIT)
                    ->columns(2),

                Textarea::make('exit_reason')
                    ->label('Reason for Exit / Termination')
                    ->placeholder('Enter details regarding the resignation or termination...')
                    ->required()
                    ->visible(fn(Get $get) => $get('employee_status') === EmployeeStatus::EXIT)
                    ->columns(2),
                    
                Toggle::make('is_active')
                    ->label('Verified')
                    ->default(false)
                    ->onColor('success')
                    ->offColor('danger')
                    ->live()
                    ->onIcon('heroicon-o-check-circle')
                    ->offIcon('heroicon-o-x-circle')
                    ->inline(false),

                TextInput::make('basic_salary')
                    ->helperText('50% of CTC')
                    ->numeric()
                    ->prefix('₹')
                    ->label('Basic Salary')
                    ->default(0)
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('hra')
                    ->helperText('10% of CTC')
                    ->numeric()
                    ->prefix('₹')
                    ->label('HRA')
                    ->default(0)
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('conveyance')
                    ->helperText('8% of CTC')
                    ->numeric()
                    ->prefix('₹')
                    ->label('Conveyance')
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('medical')
                    ->helperText('20% of CTC')
                    ->numeric()
                    ->prefix('₹')
                    ->label('Medical')
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('other_allowances')
                    ->helperText('12% of CTC')
                    ->numeric()
                    ->prefix('₹')
                    ->label('Other Allowances')
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('pf')
                    ->helperText('12% of CTC. This amount will be deducted from the employee\'s salary and contributed to PF fund.')
                    ->prefix('₹')
                    ->label('PF Contribution')
                    ->dehydrateStateUsing(fn($state) => CurrencyHelper::percentToInt((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::intToPercent((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('esi')
                    ->helperText('0.75% of CTC. This amount will be deducted from the employee\'s salary and contributed to ESI fund.')
                    ->prefix('₹')
                    ->label('ESI Contribution')
                    ->dehydrateStateUsing(fn($state) => CurrencyHelper::percentToInt((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::intToPercent((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),


            ]);
    }
}
