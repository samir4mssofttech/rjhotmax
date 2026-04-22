<?php

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
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
                    ->visibility('public'),
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
                        $set('basic_salary', $salary * 0.40);      // 40%
                        $set('hra', $salary * 0.20);               // 20%
                        $set('conveyance', $salary * 0.08);        // 8%
                        $set('medical', $salary * 0.20);           // 20%
                        $set('other_allowances', $salary * 0.12);  // 12%
                    })
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null),
                // ----------------------------

                Select::make('payout_type')
                    ->label('Payout Type')
                    ->native(false)
                    ->options([
                        'salried' => 'Salaried(Monthly)',
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
                    ->numeric()
                    ->prefix('₹')
                    ->label('Basic Salary')
                    ->default(0)
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('hra')
                    ->numeric()
                    ->prefix('₹')
                    ->label('HRA')
                    ->default(0)
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('conveyance')
                    ->numeric()
                    ->prefix('₹')
                    ->label('Conveyance')
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('medical')
                    ->numeric()
                    ->prefix('₹')
                    ->label('Medical')
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('other_allowances')
                    ->numeric()
                    ->prefix('₹')
                    ->label('Other Allowances')
                    ->dehydrateStateUsing(fn($state) => \App\Helpers\CurrencyHelper::rupeeToPaisa((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::paisaToRupee((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('pf')
                    ->suffix('%')
                    ->label('PF Contribution')
                    ->dehydrateStateUsing(fn($state) => CurrencyHelper::percentToInt((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::intToPercent((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('esi')
                    ->suffix('%')
                    ->label('ESI Contribution')
                    ->dehydrateStateUsing(fn($state) => CurrencyHelper::percentToInt((float) $state))
                    ->formatStateUsing(fn($state) => $state ? \App\Helpers\CurrencyHelper::intToPercent((int) $state) : null)
                    ->visible(fn(Get $get) => $get('is_active')),

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
                    ->columnSpanFull(),
            ]);
    }
}
