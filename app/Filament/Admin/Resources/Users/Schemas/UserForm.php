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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Informations')
                ->components([
                    // Section 1: Primary Information
                    Section::make('Personal Information')
                        ->description('Basic identity and contact details.')
                        ->aside() // This puts the title/description on the left for a clean look
                        ->schema([
                            Grid::make()->schema([
                                TextInput::make('full_name')
                                    ->required()
                                    ->prefixIcon('heroicon-m-user'),
                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->prefixIcon('heroicon-m-envelope'),
                                TextInput::make('mobile_number')
                                    ->tel()
                                    ->required()
                                    ->prefixIcon('heroicon-m-phone'),
                                Grid::make(2)->schema([
                                    DatePicker::make('date_of_birth')
                                        ->native(false)
                                        ->required(),
                                    Select::make('gender')
                                        ->options(Gender::class)
                                        ->native(false)
                                        ->required(),
                                ])->columnSpanFull(),
                            ]),
                        ]),

                    // Section 2: Employment Details
                    Section::make('Employment Details')
                        ->description('Role and organizational placement.')
                        ->aside()
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('employee_code')
                                    ->label('Employee ID')
                                    ->unique(ignoreRecord: true)
                                    ->required()
                                    ->prefixIcon('heroicon-m-identification'),
                                Select::make('user_role')
                                    ->options(UserRole::class)
                                    ->native(false)
                                    ->required(),
                                Select::make('department')
                                    ->options(Department::class)
                                    ->native(false)
                                    ->required(),
                                Select::make('designation')
                                    ->options(Designation::class)
                                    ->native(false)
                                    ->required(),
                            ]),
                        ]),

                    // Section 3: Legal & Verification
                    Section::make('Verification Documents')
                        ->description('Government IDs for compliance.')
                        ->aside()
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('aadhaar_number')
                                    ->label('Aadhaar Number')
                                    ->required()
                                    ->mask('9999-9999-9999')
                                    ->stripCharacters('-')
                                    ->placeholder('0000-0000-0000')
                                    ->regex('/^[2-9]{1}[0-9]{3}\s?[0-9]{4}\s?[0-9]{4}$/'),
                                TextInput::make('pan_number')
                                    ->label('PAN Card')
                                    ->required()
                                    ->maxLength(10)
                                    ->autocapitalize('characters')
                                    ->placeholder('ABCDE1234F')
                                    ->regex('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'),
                            ]),
                        ]),

                    // Section 4: Address
                    Section::make('Address Details')
                        ->description('Current residential information.')
                        ->aside()
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('state')->required(),
                                TextInput::make('city')->required(),
                                Textarea::make('address')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                        ]),
                ])->columnSpanFull(),


            ]);
    }
}
