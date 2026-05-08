<?php

namespace App\Filament\Admin\Resources\Payouts\Schemas;

use App\Enums\PayoutStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Schemas\Components\Split;

class PayoutInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── TOP HEADER: Quick Overview ─────────────────────────────────
                Section::make('Payout Overview')
                    ->description('General information regarding this payout cycle.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('employee.name')
                                    ->label('Employee')
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-m-user'),

                                TextEntry::make('payout_month')
                                    ->label('Payroll Period')
                                    ->icon('heroicon-m-calendar'),

                                 // ✅ FIXED THIS BLOCK
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (PayoutStatus $state): string => match ($state) {
                                        PayoutStatus::Paid => 'success',
                                        PayoutStatus::Draft => 'gray',
                                        PayoutStatus::Approved => 'primary',
                                        default => 'gray',
                                    })
                                    ->icon('heroicon-m-circle-stack'),


                                TextEntry::make('net_salary')
                                    ->label('Net Take-Home')
                                    ->money('INR') // Or use ->prefix('₹')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->size('lg'),
                            ]),
                    ]),

                Section::make([
                    // ── LEFT COLUMN: The Calculation Logic ──────────────────────
                    Section::make('Salary Breakdown')
                        ->columnSpan(2)
                        ->schema([
                            // Attendance Stats as a high-level grid
                            Grid::make(5)
                                ->schema([
                                    TextEntry::make('total_working_days')
                                        ->label('Working Days')
                                        ->alignCenter(),
                                    TextEntry::make('present_days')
                                        ->label('Present')
                                        ->alignCenter()
                                        ->color('success'),
                                    TextEntry::make('absent_days')
                                        ->label('Absent')
                                        ->alignCenter()
                                        ->color('danger'),
                                    TextEntry::make('late_days')
                                        ->label('Late')
                                        ->alignCenter()
                                        ->color('warning'),
                                    TextEntry::make('overtime_minutes')
                                        ->label('OT Mins')
                                        ->alignCenter(),
                                ]),

                            // Earnings & Deductions Side-by-Side
                            Grid::make(2)
                                ->schema([
                                    // Earnings Column
                                    Section::make('Earnings')
                                        ->schema([
                                            TextEntry::make('basic_salary')->label('Basic')->money('INR'),
                                            TextEntry::make('hra')->label('HRA')->money('INR'),
                                            TextEntry::make('conveyance')->label('Conveyance')->money('INR'),
                                            TextEntry::make('medical')->label('Medical')->money('INR'),
                                            TextEntry::make('other_allowances')->label('Other Allowances')->money('INR'),
                                            TextEntry::make('overtime_amount')->label('Overtime Pay')->money('INR')->color('success'),
                                            TextEntry::make('gross_salary')
                                                ->label('Gross Salary')
                                                ->money('INR')
                                                ->weight(FontWeight::Bold)
                                                ->color('success'),
                                        ]),

                                    // Deductions Column
                                    Section::make('Deductions')
                                        ->schema([
                                            TextEntry::make('pf')->label('Provident Fund')->money('INR'),
                                            TextEntry::make('esi')->label('ESI')->money('INR'),
                                            TextEntry::make('absent_deduction')->label('Absent Deduction')->money('INR'),
                                            TextEntry::make('late_deduction')->label('Late Deduction')->money('INR'),
                                            TextEntry::make('other_deductions')->label('Other Deductions')->money('INR'),
                                            TextEntry::make('total_deductions')
                                                ->label('Total Deductions')
                                                ->money('INR')
                                                ->weight(FontWeight::Bold)
                                                ->color('danger'),
                                        ]),
                                ]),
                        ]),

                    // ── RIGHT COLUMN: Payment & Meta ─────────────────────────────
                    Section::make('Payment Details')
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('payout_type')
                                ->label('Payment Method')
                                ->badge()
                                ->color('info'),
                            
                            TextEntry::make('paid_on')
                                ->label('Payment Date')
                                ->date()
                                ->icon('heroicon-m-check-circle'),

                            TextEntry::make('remarks')
                                ->label('Internal Remarks')
                                ->placeholder('No remarks provided')
                                ->columnSpanFull(),
                        ]),
                ])
            ]);
    }
}