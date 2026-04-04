<?php

namespace App\Filament\Admin\Resources\Applicants\Schemas;

use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ApplicantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── Personal Information ─────────────────────────────────
            Section::make('Personal Information')
                ->icon('heroicon-m-user')
                ->columns(4)
                ->schema([
                    TextEntry::make('applicant_code')
                        ->label('Application ID')
                        ->weight(FontWeight::SemiBold)
                        ->copyable(),
                    TextEntry::make('applicant_name')
                        ->label('Full Name')
                        ->weight(FontWeight::SemiBold),
                    TextEntry::make('status')
                        ->badge(),
                    TextEntry::make('gender'),
                    TextEntry::make('date_of_birth')
                        ->label('Date of Birth')
                        ->date('d M Y'),
                    TextEntry::make('experience')
                        ->suffix(' Years'),
                    TextEntry::make('aadhar_number')
                        ->label('Aadhaar Number')
                        ->fontFamily('mono')
                        ->copyable(),
                    TextEntry::make('pan_number')
                        ->label('PAN Number')
                        ->fontFamily('mono')
                        ->weight(FontWeight::SemiBold)
                        ->copyable(),
                ])->columnspanfull(),

            // ── Contact Details ──────────────────────────────────────
            Section::make('Contact Details')
                ->icon('heroicon-m-phone')
                ->columns(2)
                ->schema([
                    TextEntry::make('email_id')
                        ->label('Email Address')
                        ->icon('heroicon-m-envelope')
                        ->copyable(),
                    TextEntry::make('mobile_number')
                        ->label('Phone Number')
                        ->icon('heroicon-m-device-phone-mobile')
                        ->copyable(),
                    TextEntry::make('address')
                        ->label('Residential Address')
                        ->placeholder('Not provided')
                        ->columnSpanFull(),
                ]),

            // ── Job Information ──────────────────────────────────────
            Section::make('Job Information')
                ->icon('heroicon-m-briefcase')
                ->columns(3)
                ->schema([
                    TextEntry::make('position')
                        ->label('Department')
                        ->badge(),
                    TextEntry::make('designation')
                        ->label('Designation')
                        ->badge(),
                    TextEntry::make('employment_type')
                        ->label('Employment Type')
                        ->badge()
                        ->color(fn($state) => match ($state?->value) {
                            'full_time' => 'success',
                            'contract'  => 'warning',
                            'intern'    => 'info',
                            default     => 'gray',
                        }),
                    TextEntry::make('apply_date')
                        ->label('Application Date')
                        ->date('d M Y'),
                    TextEntry::make('date_of_joining')
                        ->label('Joining Date')
                        ->date('d M Y')
                        ->placeholder('Not set'),
                    TextEntry::make('reportingManager.full_name')
                        ->label('Reporting Manager')
                        ->icon('heroicon-m-user-circle')
                        ->placeholder('Not assigned'),

                ]),

            // ── Emergency Contact ────────────────────────────────────
            Section::make('Emergency Contact')
                ->icon('heroicon-m-user-group')
                ->columns(3)
                ->schema([
                    TextEntry::make('emergency_contact_name')
                        ->label('Contact Name'),
                    TextEntry::make('emergency_contact_phone')
                        ->label('Phone')
                        ->copyable(),
                    TextEntry::make('emergency_contact_relation')
                        ->label('Relation')
                        ->badge()
                        ->color('gray'),
                ]),

            // ── Bank Details ─────────────────────────────────────────
            Section::make('Bank Details')
                ->icon('heroicon-m-building-library')
                ->columns(3)
                ->schema([
                    TextEntry::make('bank_name')
                        ->label('Bank Name')
                        ->placeholder('Not provided'),
                    TextEntry::make('bank_account_number')
                        ->label('Account Number')
                        ->fontFamily('mono')
                        ->copyable()
                        ->placeholder('Not provided'),
                    TextEntry::make('bank_ifsc_code')
                        ->label('IFSC Code')
                        ->fontFamily('mono')
                        ->copyable()
                        ->placeholder('Not provided'),
                ]),

            // ── Location & Salary ────────────────────────────────────
            Section::make('Location & Compensation')
                ->icon('heroicon-m-map-pin')
                ->columns(3)
                ->schema([

                    TextEntry::make('city'),
                    TextEntry::make('state'),

                    TextEntry::make('salary')
                        ->label('Monthly Salary')
                        ->money('INR')
                        ->weight(FontWeight::Bold)
                        ->size('Lg')
                        ->placeholder('Not set'),
                    TextEntry::make('location')
                        ->label('Specific Area')
                        ->columnSpanFull(),
                    TextEntry::make('branch.display_name')
                        ->label('Assigned Branch')
                        ->badge()
                        ->color('primary') // Makes it stand out
                        ->size('Lg') // Makes the text/badge larger
                        ->weight(FontWeight::Bold)
                        ->columnSpanFull(),
                ]),

            Section::make('Documents')
                ->icon(Heroicon::DocumentText)
                ->schema([
                    TextEntry::make('id_proof_type')
                        ->label('ID Proof Type')
                        ->formatStateUsing(fn($state) => match ($state) {
                            'aadhaar' => 'Aadhaar Card',
                            'pan' => 'PAN Card',
                            'passport' => 'Passport',
                            'driving_license' => 'Driving License',
                            'voter_id' => 'Voter ID',
                            default => 'Other',
                        }),

                    TextEntry::make('file_path')
                        ->label('ID Proof')
                        ->placeholder('No document uploaded')
                        ->formatStateUsing(fn() => 'View ID Proof')
                        ->url(fn($record) => Storage::disk('public')->url($record->file_path))
                        ->openUrlInNewTab(),
                    TextEntry::make('appointment_letter_path')
                        ->label('Appointment Letter')
                        ->placeholder('Not uploaded yet')
                        ->formatStateUsing(fn() => 'View Letter')
                        ->url(fn($state) => Storage::disk('public')->url($state))
                        ->openUrlInNewTab()
                ])
                ->columns(3),

            // ── Audit Trail ──────────────────────────────────────────
            Section::make('Audit Trail')
                ->icon('heroicon-m-clock')
                ->columns(2)
                ->collapsed()
                ->schema([
                    // TextEntry::make('creator.full_name')
                    //     ->label('Created By')
                    //     ->icon('heroicon-m-user'),
                    TextEntry::make('created_at')
                        ->label('Created At')
                        ->dateTime('d M Y, h:i A'),
                    // TextEntry::make('editor.full_name')
                    //     ->label('Last Updated By')
                    //     ->icon('heroicon-m-user'),
                    TextEntry::make('updated_at')
                        ->label('Updated At')
                        ->dateTime('d M Y, h:i A'),
                ]),
        ]);
    }
}
