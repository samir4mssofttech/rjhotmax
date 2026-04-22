<?php

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Enums\EmployeeStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
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
                                Grid::make(3)->schema([
                                    TextEntry::make('branch.display_name')
                                        ->badge()
                                        ->label('Branch'),
                                    TextEntry::make('join_date')
                                        ->date(),
                                    TextEntry::make('skill_type')
                                        ->badge(),
                                    TextEntry::make('payout_type')
                                        ->label('Payment Cycle'),
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

                        // SECTION 3: Salary Breakdown
                        Section::make('Compensation & Benefits')
                            ->columnSpanFull()
                            ->schema([
                                Grid::make(4)->schema([
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

                                    TextEntry::make('pf')
                                        ->label('PF Contribution')
                                        ->color('danger')
                                        ->suffix(' %')
                                        ->formatStateUsing(fn($state) => \App\Helpers\CurrencyHelper::intToPercent((int) $state)),

                                    TextEntry::make('esi')
                                        ->label('ESI Contribution')
                                        ->color('danger')
                                        ->suffix(' %')
                                        ->formatStateUsing(fn($state) =>\App\Helpers\CurrencyHelper::intToPercent((int) $state)),

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
