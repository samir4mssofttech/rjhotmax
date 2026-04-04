<?php

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                Radio::make('status')
                    ->options(EmployeeStatus::class)
                    ->inline()
                    ->default(EmployeeStatus::ACTIVE) // Use Enum case if possible
                    ->required()
                    ->live(),

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
