<?php

namespace App\Filament\Admin\Resources\Applicants\Tables;

use App\Enums\ApplicantStatus;
use App\Enums\UserRole;
use App\Enums\EmployeeStatus;
use App\Models\Applicant;
use App\Models\Employee;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
// use Filament\Tables\Filters\SelectFilter;
// use App\Enums\Department;
// use App\Enums\EmploymentType;

class ApplicantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('applicant_name')
            ->columns([
                TextColumn::make('status')
                    ->sortable()
                    ->badge(),
                TextColumn::make('applicant_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('applicant_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->searchable()
                    ->sortable(),

                //==================
                // ➕ ADD columns (after 'position' column):
                TextColumn::make('designation')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('employment_type')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_of_joining')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('salary')
                    ->money('INR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reportingManager.full_name')
                    ->label('Reporting Manager')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                //==================
                TextColumn::make('apply_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('branch.display_name')->badge(),
                TextColumn::make('city')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('state')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('experience')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email_id')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mobile_number')
                    ->label('Mobile')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('creator.full_name')
                    ->label('Created By')
                    ->sortable(['first_name', 'last_name'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('editor.full_name')
                    ->label('Updated By')
                    ->sortable(['first_name', 'last_name'])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->slideOver(),
                    EditAction::make()
                        ->slideOver(),
                    Action::make('send_offer_letter')
                        ->visible(fn(Applicant $record) => $record->status === ApplicantStatus::INITIATED)
                        ->icon(ApplicantStatus::OFFERED->getIcon())
                        ->color(ApplicantStatus::OFFERED->getColor())
                        ->label('Send Offer Letter')
                        ->modalWidth(Width::TwoExtraLarge)
                        ->modalIcon(ApplicantStatus::OFFERED->getIcon())
                        ->modalIconColor(ApplicantStatus::OFFERED->getColor())
                        ->modalDescription('Are you sure you\'d like to send the offer letter? This cannot be undone.')
                        ->fillForm(fn(Applicant $record): array => [
                            'branch_id' => $record->branch_id,
                            'city' => $record->city,
                            'state' => $record->state,
                            'location' => $record->location,
                            'date_of_joining' => $record->date_of_joining,
                            'salary' => $record->salary,
                        ])
                        ->slideOver()
                        ->schema([
                            Grid::make(2)
                                ->columns([
                                    'sm' => 1,
                                    'xl' => 2,
                                ])
                                ->schema([
                                    TextEntry::make('applicant_code'),
                                    TextEntry::make('applicant_name'),
                                    Select::make('branch_id')
                                        ->relationship('branch', 'name')
                                        ->getOptionLabelFromRecordUsing(fn($record) => $record->display_name)
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    TextInput::make('city')
                                        ->required(),
                                    TextInput::make('state')
                                        ->required(),
                                    TextInput::make('location')
                                        ->required(),
                                    DatePicker::make('date_of_joining')
                                        ->required(),
                                    TextInput::make('salary')
                                        ->required()
                                        ->numeric()
                                        ->live()
                                        ->helperText(fn(?string $state): ?string => $state !== null ? Str::ucwords(Number::spell((int) $state, 'en-IN')) : null),
                                ]),
                        ])
                        ->action(function (Applicant $record, array $data): void {
                            $record->update([
                                'branch_id' => $data['branch_id'],
                                'city' => $data['city'],
                                'state' => $data['state'],
                                'location' => $data['location'],
                                'date_of_joining' => $data['date_of_joining'] ?? null,
                                'salary' => $data['salary'] ?? null,
                                'status' => ApplicantStatus::OFFERED,
                            ]);
                        }),
                    Action::make('offer_accepted')
                        ->visible(fn(Applicant $record) => $record->status === ApplicantStatus::OFFERED)
                        ->icon(ApplicantStatus::ACCEPTED->getIcon())
                        ->color(ApplicantStatus::ACCEPTED->getColor())
                        ->label('Offer Accepted')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you\'d like to change status to Offer Accepted? This cannot be undone.')
                        ->action(function (Applicant $record): void {
                            $record->update([
                                'status' => ApplicantStatus::ACCEPTED,
                                'confirmation_date' => now()->toDateString(), // ✅ auto-set on acceptance

                            ]);
                        }),
                    Action::make('offer_rejected')
                        ->visible(fn(Applicant $record) => $record->status === ApplicantStatus::OFFERED)
                        ->icon(ApplicantStatus::REJECTED->getIcon())
                        ->color(ApplicantStatus::REJECTED->getColor())
                        ->label('Offer Rejected')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you\'d like to change status to Offer Rejected? This cannot be undone.')
                        ->action(function (Applicant $record): void {
                            $record->update([
                                'status' => ApplicantStatus::REJECTED,
                            ]);
                        }),
                    Action::make('send_joining_letter')
                        ->visible(fn(Applicant $record) => $record->status === ApplicantStatus::ACCEPTED)
                        ->icon(ApplicantStatus::JOINING_LETTER_SENT->getIcon())
                        ->color(ApplicantStatus::JOINING_LETTER_SENT->getColor())
                        ->label('Send Joining Letter')
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you\'d like to send the joining letter? This cannot be undone.')
                        ->action(function (Applicant $record): void {
                            $record->update([
                                'status' => ApplicantStatus::JOINING_LETTER_SENT,
                            ]);
                        }),
                    Action::make('send_appointment_letter')
                        ->visible(fn(Applicant $record) => $record->status === ApplicantStatus::JOINING_LETTER_SENT)
                        ->icon(ApplicantStatus::APPOINTMENT_LETTER_SENT->getIcon())
                        ->color(ApplicantStatus::APPOINTMENT_LETTER_SENT->getColor())
                        ->label('Send Appointment Letter')
                        ->modalWidth(Width::TwoExtraLarge)
                        ->modalIcon(ApplicantStatus::APPOINTMENT_LETTER_SENT->getIcon())
                        ->modalIconColor(ApplicantStatus::APPOINTMENT_LETTER_SENT->getColor())
                        ->modalDescription('Are you sure you\'d like to send the appointment letter? This cannot be undone.')
                        ->fillForm(fn(Applicant $record): array => [
                            'branch_id' => $record->branch_id,
                            'city' => $record->city,
                            'state' => $record->state,
                            'location' => $record->location,
                            'date_of_joining' => $record->date_of_joining,
                            'salary' => $record->salary,
                        ])
                        ->slideOver()
                        ->schema([
                            Grid::make(2)
                                ->columns([
                                    'sm' => 1,
                                    'xl' => 2,
                                ])
                                ->schema([
                                    TextEntry::make('applicant_code'),
                                    TextEntry::make('applicant_name'),
                                   Select::make('branch_id')
                                        ->relationship('branch', 'name')
                                        ->getOptionLabelFromRecordUsing(fn($record) => $record->display_name)
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    TextInput::make('city')
                                        ->required(),
                                    TextInput::make('state')
                                        ->required(),
                                    TextInput::make('location')
                                        ->required(),
                                    DatePicker::make('date_of_joining')
                                        ->required(),
                                    TextInput::make('salary')
                                        ->required()
                                        ->numeric()
                                        ->live()
                                        ->helperText(fn(?string $state): ?string => $state !== null ? Str::ucwords(Number::spell((int) $state, 'en-IN')) : null),
                                    // ✅ ADD THIS
                                    FileUpload::make('appointment_letter')
                                        ->label('Upload applicant`s Signed Offer Letter')
                                        ->disk('public')
                                        ->directory('appointment_letters')
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->maxSize(5120) // 5MB
                                        ->required()
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->action(function (Applicant $record, array $data): void {

                            $user = null; // important

                            DB::transaction(function () use ($record, $data, &$user) {

                                // 1️⃣ Generate Employee Code
                                $employeeCode = 'RJ' . str_pad($record->id, 5, '0', STR_PAD_LEFT);

                                // 2️⃣ Create Employee
                                $member = Employee::create([
                                    'applicant_id' => $record->id,
                                    'employee_code' => $employeeCode,
                                    'name' => $record->applicant_name,
                                    'email' => $record->email_id,
                                    'phone' => $record->mobile_number,
                                    'join_date' => $data['date_of_joining'],
                                    'confirmation_date' => $record->confirmation_date, // set on acceptance
                                    'exit_date' => null,
                                    'branch_id' => $data['branch_id'],
                                    'is_active' => false, 
                                    'employee_status' => EmployeeStatus::PROBATION,
                                ]);

                                // 3️⃣ Create User
                                $user = User::firstOrCreate(
                                    ['email' => $record->email_id],
                                    [
                                        'full_name' => $record->applicant_name,
                                        'employee_code' => $employeeCode,
                                        'password' => bcrypt(Str::random(10)),
                                        'user_role' => UserRole::EMPLOYEE,
                                        'department' => $record->position,
                                        'designation' => $record->designation, // 👈 from applicant
                                        'mobile_number' => $record->mobile_number,
                                        'date_of_birth' => $record->date_of_birth,
                                        'gender' => $record->gender,
                                        'aadhaar_number' => $record->aadhar_number,
                                        'pan_number' => $record->pan_number,
                                        'state' => $record->state,
                                        'city' => $record->city,
                                        'address' => $record->address,
                                    ]
                                );
                                // Password::broker('users')->sendResetLink(['email' => $user->email]);

                                // 4️⃣ Attach user to member
                                $member->update([
                                    'user_id' => $user->id,
                                ]);

                                // 5️⃣ Update Applicant Status
                                $record->update([
                                    'appointment_letter_path' => $data['appointment_letter'],
                                    'status' => ApplicantStatus::APPOINTMENT_LETTER_SENT,
                                ]);

                                // 6️⃣ Generate Reset Token for USER (not Applicant)
                                $token = Password::createToken($user);
                                // $user->sendPasswordSetNotification($token);
                            });
                            // ADD THIS: This shows the notification on the web application for the ADMIN
                            \Filament\Notifications\Notification::make()
                                ->title('Success!')
                                ->body('The account has been created and the invitation email has been sent to ' . $record->email_id)
                                ->success()
                                ->duration(8000)
                                ->send(); // This sends it to the current user (the Admin)

                        }),

                ]),
            ]);
    }
}
