<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\Department;
use App\Enums\Designation;
use App\Enums\Gender;
use App\Enums\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 TextInput::make('full_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Select::make('user_role')
                    ->options(UserRole::class)
                    ->required()
                    ->native(false),
                TextInput::make('employee_code')->unique()
                    ->required(),
                Select::make('department')
                    ->options(Department::class)
                    ->required()
                    ->native(false),
                Select::make('designation')
                    ->options(Designation::class)
                    ->required()
                    ->native(false),
                TextInput::make('mobile_number')
                    ->required(),
                DatePicker::make('date_of_birth')
                    ->required(),
                Select::make('gender')
                    ->options(Gender::class)
                    ->required()
                    ->native(false),
                TextInput::make('aadhaar_number')
                    ->required()
                    ->mask('9999-9999-9999')
                    ->stripCharacters('-')
                    ->regex('/^[2-9]{1}[0-9]{3}\s?[0-9]{4}\s?[0-9]{4}$/'),
                TextInput::make('pan_number')
                    ->required()
                    ->autocapitalize('characters')
                    ->regex('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'),
                TextInput::make('state')
                    ->required(),
                TextInput::make('city')
                    ->required(),
                Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
