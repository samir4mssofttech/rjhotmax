<?php

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                // 1. Make status "live" so it updates the form immediately
                Radio::make('employee_status')
                    ->options(EmployeeStatus::class)
                    ->inline()
                    ->default(EmployeeStatus::ACTIVE) // Use Enum case if possible
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
                    ->label('Basic Salary')
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('hra')
                    ->numeric()
                    ->label('HRA')
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('conveyance')
                    ->numeric()
                    ->label('Conveyance')
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('medical')
                    ->numeric()
                    ->label('Medical')
                    ->visible(fn(Get $get) => $get('is_active')),

                TextInput::make('other_allowances')
                    ->numeric()
                    ->label('Other Allowances')
                    ->visible(fn(Get $get) => $get('is_active')),
                    
                // 2. Add Exit Date - Visible only when status is EXIT
                DatePicker::make('exit_date')
                    ->label('Date of Exit')
                    ->required()
                    ->visible(fn(callable $get) => $get('status') === EmployeeStatus::EXIT)
                    ->columns(2),

                // 3. Add Exit Reason - Visible only when status is EXIT
                Textarea::make('exit_reason')
                    ->label('Reason for Exit / Termination')
                    ->placeholder('Enter details regarding the resignation or termination...')
                    ->required()
                    ->visible(fn(callable $get) => $get('status') === EmployeeStatus::EXIT)
                    ->columnSpanFull(),
            ]);
    }
}
