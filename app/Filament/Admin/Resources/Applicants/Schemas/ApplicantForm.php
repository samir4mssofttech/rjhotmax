<?php

namespace App\Filament\Admin\Resources\Applicants\Schemas;

use App\Enums\Department;
use App\Enums\Designation;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ApplicantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        // Left Column: Personal & Contact Info
                        Group::make()
                            ->schema([
                                Section::make('Personal Information')
                                    ->description('Basic identity details of the applicant.')
                                    ->icon('heroicon-m-user')
                                    ->schema([
                                        TextInput::make('applicant_name')
                                            ->label('Full Name')
                                            ->placeholder('John Doe')
                                            ->required(),
                                        Select::make('gender')
                                            ->options(Gender::class)
                                            ->native(false)
                                            ->required(),
                                        DatePicker::make('date_of_birth')
                                            ->label('Date of Birth')
                                            // ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->maxDate(now()->subYears(18))
                                            ->helperText('Applicant must be at least 18 years old.')
                                            ->required(),
                                        TextInput::make('aadhar_number')
                                            ->label('Aadhar Number')
                                            ->mask('9999 9999 9999')
                                            ->placeholder('0000 0000 0000')
                                            ->required(),
                                        TextInput::make('pan_number')
                                            ->label('PAN Number')
                                            ->placeholder('ABCDE1234F')
                                            ->maxLength(10)
                                            ->required(),
                                    ])->columns(3),

                                Section::make('Contact Details')
                                    ->description('How can we reach the applicant?')
                                    ->icon('heroicon-m-phone')
                                    ->schema([
                                        TextInput::make('email_id')
                                            ->label('Email Address')
                                            ->email()
                                            ->unique()
                                            ->prefixIcon('heroicon-m-envelope')
                                            ->placeholder('john@example.com')
                                            ->required(),
                                        TextInput::make('mobile_number')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->prefixIcon('heroicon-m-device-phone-mobile')
                                            ->placeholder('eg: +91 98765 43210')
                                            ->required(),
                                        Textarea::make('address')
                                            ->rows(3)
                                            ->placeholder('Enter full residential address...')
                                            ->required()
                                            ->columnspanfull(),
                                    ])
                                    ->columns(2),

                                // ➕ ADD: Emergency Contact section
                                Section::make('Emergency Contact')
                                    ->description('Contact person in case of emergency.')
                                    ->icon('heroicon-m-user-group')
                                    ->schema([
                                        TextInput::make('emergency_contact_name')
                                            ->label('Contact Name')
                                            ->required(),
                                        TextInput::make('emergency_contact_phone')
                                            ->label('Contact Phone')
                                            ->tel()
                                            ->required(),
                                        Select::make('emergency_contact_relation')
                                            ->label('Relation')
                                            ->placeholder('Select Relation')
                                            ->options([
                                                'father' => 'Father',
                                                'mother' => 'Mother',
                                                'spouse' => 'Spouse',
                                                'sibling' => 'Sibling',
                                                'brother' => 'Brother',
                                                'sister' => 'Sister',
                                                'friend' => 'Friend',
                                                'guardian' => 'Guardian',
                                                'other' => 'Other',
                                            ])
                                            ->required()
                                            ->searchable()
                                            ->native(false),
                                    ])->columns(3),

                                // ➕ ADD: Bank Details section
                                Section::make('Bank Details')
                                    ->icon('heroicon-m-building-library')
                                    ->schema([
                                        TextInput::make('bank_account_number')
                                            ->label('Account Number')
                                            ->required(),
                                        TextInput::make('bank_name')
                                            ->label('Bank Name')
                                            ->required(),
                                        TextInput::make('bank_ifsc_code')
                                            ->label('IFSC Code')
                                            ->placeholder('e.g. SBIN0001234')
                                            ->required(),
                                    ])->columns(3),

                                // ➕ ADD: Document Upload section
                                Section::make('Documents')
                                    ->icon('heroicon-m-paper-clip')
                                    ->schema([
                                        Select::make('id_proof_type')
                                            ->label('ID Proof Type')
                                            ->options([
                                                'aadhaar' => 'Aadhaar Card',
                                                'pan' => 'PAN Card',
                                                'passport' => 'Passport',
                                                'driving_license' => 'Driving License',
                                                'voter_id' => 'Voter ID',
                                                'other' => 'Other',
                                            ])
                                            ->required()
                                            ->native(false),

                                        FileUpload::make('file_path')
                                            ->label('Upload Document')
                                            ->disk('public')
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->directory('applicant-documents')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ])->columnSpanFull(),

                        // Right Column: Application & Location Details
                        Group::make()
                            ->schema([
                                Section::make('Application Status')
                                    ->icon('heroicon-m-briefcase')
                                    ->schema([
                                        TextInput::make('applicant_code')
                                            ->label('Application ID')
                                            ->default('APP-' . strtoupper(uniqid()))
                                            ->disabled() // Usually auto-generated
                                            ->dehydrated()
                                            ->required(),
                                        DatePicker::make('apply_date')
                                            ->label('Application Date')
                                            ->default(now())
                                            ->native(false)
                                            ->required(),
                                        Select::make('position')
                                            ->label('Department')
                                            ->options(Department::class)
                                            ->searchable()
                                            ->native(false)
                                            ->required(),
                                        Select::make('designation')
                                            ->label('Designation')
                                            ->options(Designation::class)
                                            ->searchable()
                                            ->native(false)
                                            ->required(),
                                        // ➕ ADD inside Application Status section:
                                        Section::make('Employment Details')
                                            ->columns(1)
                                            ->schema([
                                                Radio::make('employment_type')
                                                    ->label('Employment Type')
                                                    ->inline()
                                                    ->options(EmploymentType::class)
                                                    ->default(EmploymentType::FULL_TIME)
                                                    ->required()
                                                    ->live() // Mandatory for reactivity
                                                    ->afterStateUpdated(fn(Set $set) => $set('contract_start_date', null)),

                                                // This should show up when "Contract" is selected
                                                DatePicker::make('contract_start_date')
                                                    ->label('Contract Start Date')
                                                    ->native(false)
                                                    ->required()
                                                    ->visible(fn(Get $get) => $get('employment_type') === EmploymentType::CONTRACT),

                                                DatePicker::make('contract_end_date')
                                                    ->label('Contract End Date')
                                                    ->native(false)
                                                    ->required()
                                                    ->visible(fn(Get $get) => $get('employment_type') === EmploymentType::CONTRACT),

                                                TextInput::make('contract_terms')
                                                    ->label('Contract Terms (Optional)')
                                                    ->placeholder('e.g. Contract conditions')
                                                    ->visible(fn(Get $get) => $get('employment_type') === EmploymentType::CONTRACT),



                                                // This should show up when "Intern" is selected
                                                DatePicker::make('internship_start_date')
                                                    ->label('Internship Start Date')
                                                    ->required()
                                                    ->native(false)
                                                    ->visible(fn(Get $get) => $get('employment_type') === EmploymentType::INTERN),
                                                DatePicker::make('internship_end_date')
                                                    ->label('Internship End Date')
                                                    ->required()
                                                    ->native(false)
                                                    ->visible(fn(Get $get) => $get('employment_type') === EmploymentType::INTERN),
                                            ]),

                                        Select::make('reporting_manager_id')
                                            ->label('Reporting Manager')
                                            ->options(fn() => User::whereIn('user_role', [UserRole::ADMIN, UserRole::EMPLOYEE])
                                                ->get()
                                                ->pluck('full_name', 'id'))
                                            ->searchable()
                                            ->native(false),
                                        TextInput::make('experience')
                                            ->label('Total Experience')
                                            ->numeric()
                                            ->suffix('Years')
                                            ->required(),
                                    ])->columns(2),

                                Section::make('Location Details')
                                    ->icon('heroicon-m-map-pin')
                                    ->schema([
                                        Select::make('branch_id')
                                            ->relationship('branch', 'name')
                                            ->getOptionLabelFromRecordUsing(fn($record) => $record->display_name)
                                            ->native(false)
                                            ->required(),
                                        TextInput::make('city')
                                            ->required(),
                                        TextInput::make('state')
                                            ->required(),
                                        TextInput::make('location')
                                            ->label('Specific Location/Area')
                                            ->required(),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnspanfull(),
            ]);
    }
}
