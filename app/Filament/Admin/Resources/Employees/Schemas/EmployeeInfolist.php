<?php

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Employee Details')
                    ->schema([
                        // SECTION 1: Personal Information
                        Section::make('Personal Information')
                            ->columns(2)
                            ->schema([
                                ImageEntry::make('profile_photo')
                                    ->label('Profile Photo')
                                    ->circular()
                                    ->disk('public')
                                    ->grow(false),
                                TextEntry::make('name')
                                    ->weight('bold')
                                    ->size('lg'),
                                TextEntry::make('email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),
                                TextEntry::make('phone')
                                    ->icon('heroicon-m-phone'),
                            ]),

                        // SECTION 2: Employment Details
                        Section::make('Employment Details')
                            ->columnSpanFull()
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('branch.display_name')
                                        ->badge()
                                        ->label('Branch'),
                                    TextEntry::make('join_date')
                                        ->date(),
                                    TextEntry::make('skill_type')
                                        ->badge(),
                                    TextEntry::make('payout_type')
                                        ->label('Payment Cycle'),
                                    TextEntry::make('security_money')
                                        ->money('INR')
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::paisaToRupee((int) $state)),
                                    TextEntry::make('employee_status')
                                        ->badge()
                                        ->color(fn(EmployeeStatus $state): string => match ($state) {
                                            EmployeeStatus::ACTIVE => 'success',
                                            EmployeeStatus::EXIT => 'danger',
                                            default => 'gray',
                                        }),
                                    IconEntry::make('is_active')
                                        ->label('Verified')
                                        ->boolean(),
                                ]),
                            ]),

                        Section::make('Legal & Banking Information')
                            ->description('Government IDs and salary disbursement details')
                            ->schema([
                                Grid::make(3)->schema([
                                    // Gov IDs
                                    Group::make([
                                        TextEntry::make('pan_number')->label('PAN Number')->copyable(),
                                        TextEntry::make('aadhar_number')->label('Aadhar Number')->copyable(),
                                        TextEntry::make('uan_number')->label('UAN Number')->copyable(),
                                    ])->columnSpan(1),

                                    // Bank Details
                                    Group::make([
                                        TextEntry::make('bank_name')->label('Bank Name'),
                                        TextEntry::make('bank_account_number')->label('Account Number')->copyable(),
                                        TextEntry::make('ifsc_code')->label('IFSC Code')->copyable(),
                                    ])->columnSpan(1),

                                    // Statutory IDs
                                    Group::make([
                                        TextEntry::make('pf_number')->label('PF Number'),
                                        TextEntry::make('esi_number')->label('ESI Number'),
                                    ])->columnSpan(1),
                                ]),
                            ]),

                        // ══════════════════════════════════════════════════════════════
                        // FINANCIAL BREAKDOWN
                        // ══════════════════════════════════════════════════════════════
                        Section::make('Compensation & Benefits')
                            ->columnSpanFull()
                            ->schema([
                                Grid::make(5)->schema([
                                    TextEntry::make('salary')
                                        ->label('Total CTC')
                                        ->weight('bold')
                                        ->color('primary')
                                        ->money('INR') // This is a Filament helper, but we will use your custom helper below
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::paisaToRupee((int) $state)),

                                    TextEntry::make('basic_salary')
                                        ->label('Basic Salary')
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::paisaToRupee((int) $state)),

                                    TextEntry::make('hra')
                                        ->label('HRA')
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::paisaToRupee((int) $state)),

                                    TextEntry::make('conveyance')
                                        ->label('Conveyance')
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::paisaToRupee((int) $state)),

                                    TextEntry::make('medical')
                                        ->label('Medical')
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::paisaToRupee((int) $state)),

                                    TextEntry::make('other_allowances')
                                        ->label('Other Allowances')
                                        ->money('INR', true)
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::paisaToRupee((int) $state)),

                                    TextEntry::make('pf_number')
                                        ->label('PF Number')
                                        ->color('info'),

                                    TextEntry::make('esi_number')
                                        ->label('ESI Number')
                                        ->color('info'),

                                    TextEntry::make('pf')
                                        ->label('PF Contribution')
                                        ->color('danger')
                                        ->money('INR', true)
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::intToPercent((int) $state)),

                                    TextEntry::make('esi')
                                        ->label('ESI Contribution')
                                        ->color('danger')
                                        ->money('INR', true)
                                        ->formatStateUsing(fn($state) => '₹' . \App\Helpers\CurrencyHelper::intToPercent((int) $state)),

                                ]),
                            ]),

                        // SECTION 4: Exit Details (Conditional)
                        Section::make('Exit Details')
                            ->columnSpanFull()
                            ->visible(fn($record) => $record->employee_status === EmployeeStatus::EXIT)
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('exit_date')
                                        ->date(),
                                    TextEntry::make('exit_reason')
                                        ->columnSpanFull()
                                        ->markdown(),
                                ]),
                            ])->columnSpanFull(),
                    ])->columnSpanFull()

            ]);
    }
}
