<?php

namespace App\Filament\Admin\Resources\Payouts\Schemas;

use App\Enums\PayoutStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Schemas\Components\Split;
use Filament\Infolists\Components\View;

class PayoutInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ══════════════════════════════════════════════════════════════
                // HERO SECTION: The "Bottom Line" 
                // This is the first thing the admin/user sees.
                // ══════════════════════════════════════════════════════════════
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('net_salary')
                                    ->label('Net Take-Home Salary')
                                    ->money('INR')
                                    ->weight(FontWeight::Bold)
                                    ->size('3xl') // Extra large for maximum impact
                                    ->color('primary')
                                    // ->alignCenter()
                                    ,
                                
                                TextEntry::make('payout_type')
                                    ->label('Payment Method')
                                    ->badge()
                                    ->color('gray')
                                    // ->alignCenter()
                                    ,
                            ]),
                    ])
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'bg-primary-50 dark:bg-primary-950/20 border-primary-500 border-2', 
                    ]),

                // ══════════════════════════════════════════════════════════════
                // PREMIUM HEADER: Employee & Payout Status
                // ══════════════════════════════════════════════════════════════
                Section::make()
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Section::make()
                                    ->columnSpan(4)
                                    ->schema([
                                        TextEntry::make('employee.name')
                                            ->label('Employee Name')
                                            ->weight(FontWeight::Bold)
                                            ->size('lg')
                                            ->icon('heroicon-m-user-circle')
                                            ->iconPosition('before'),

                                        TextEntry::make('employee.account_number')
                                            ->label('Employee ID')
                                            ->color('gray')
                                            ->size('sm'),
                                    ])
                                    ->icon('heroicon-m-briefcase'),

                                Section::make()
                                    ->columnSpan(4)
                                    ->schema([
                                        TextEntry::make('payout_month')
                                            ->label('Payroll Period')
                                            ->weight(FontWeight::Bold)
                                            ->size('lg')
                                            ->icon('heroicon-m-calendar')
                                            ->iconPosition('before'),

                                        TextEntry::make('created_at')
                                            ->label('Generated On')
                                            ->dateTime('M d, Y')
                                            ->color('gray')
                                            ->size('sm'),
                                    ])
                                    ->icon('heroicon-m-calendar-days'),

                                Section::make()
                                    ->columnSpan(4)
                                    ->schema([
                                        TextEntry::make('status')
                                            ->label('Payout Status')
                                            ->badge()
                                            ->weight(FontWeight::Bold)
                                            ->size('lg')
                                            ->color(fn(PayoutStatus $state): string => match ($state) {
                                                PayoutStatus::Paid => 'success',
                                                PayoutStatus::Draft => 'warning',
                                                PayoutStatus::Approved => 'info',
                                                default => 'gray',
                                            })
                                            ->icon('heroicon-m-check-circle'),

                                        TextEntry::make('paid_on')
                                            ->label('Payment Date')
                                            ->date('M d, Y')
                                            ->visible(fn($state) => $state !== null)
                                            ->color('success')
                                            ->size('sm'),
                                    ])
                                    ->icon('heroicon-m-shield-check'),
                            ]),
                    ])
                    ->columnSpanFull(),

                // ══════════════════════════════════════════════════════════════
                // DETAILED BREAKDOWN
                // ══════════════════════════════════════════════════════════════
                Section::make('Earnings & Allowances')
                    ->columnSpan(1)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('basic_salary')->label('Basic Salary')->money('INR')->weight(FontWeight::Bold),
                                TextEntry::make('hra')->label('House Rent Allowance')->money('INR'),
                                TextEntry::make('conveyance')->label('Conveyance Allowance')->money('INR'),
                                TextEntry::make('medical')->label('Medical Allowance')->money('INR'),
                                TextEntry::make('other_allowances')->label('Other Allowances')->money('INR'),
                                TextEntry::make('overtime_amount')->label('Overtime Pay')->money('INR')->color('success')->icon('heroicon-m-star'),
                                
                                TextEntry::make('gross_salary')
                                    ->label('Total Gross Salary')
                                    ->money('INR')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->color('success')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('Deductions')
                    ->columnSpan(1)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('pf')->label('Provident Fund (PF)')->money('INR')->weight(FontWeight::Bold),
                                TextEntry::make('esi')->label('Employee State Insurance')->money('INR'),
                                TextEntry::make('absent_deduction')->label('Absent Deduction')->money('INR')->color('warning'),
                                TextEntry::make('late_deduction')->label('Late Deduction')->money('INR')->color('warning'),
                                TextEntry::make('other_deductions')->label('Other Deductions')->money('INR')->columnSpanFull(),

                                TextEntry::make('total_deductions')
                                    ->label('Total Deductions')
                                    ->money('INR')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->color('danger')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // ══════════════════════════════════════════════════════════════
                // FINANCIAL SUMMARY: Now acts as a "Calculation" row
                // ══════════════════════════════════════════════════════════════
                Section::make('Calculation Summary')
                    ->description('How the final payout was calculated')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('gross_salary')
                                    ->label('Gross Salary')
                                    ->money('INR')
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),

                                TextEntry::make('total_deductions')
                                    ->label('Less Deductions')
                                    ->money('INR')
                                    ->weight(FontWeight::Bold)
                                    ->color('danger'),

                                TextEntry::make('net_salary')
                                    ->label('Net Payout')
                                    ->money('INR')
                                    ->weight(FontWeight::Bold)
                                    ->size('xl')
                                    ->color('primary'),
                            ]),
                    ]),

                // ══════════════════════════════════════════════════════════════
                // ATTENDANCE & PERFORMANCE
                // ══════════════════════════════════════════════════════════════
                Section::make('Attendance & Performance')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_working_days')->label('Working Days')->weight(FontWeight::Bold)->size('lg')->alignCenter()->color('primary'),
                                TextEntry::make('present_days')->label('Present')->weight(FontWeight::Bold)->size('lg')->alignCenter()->color('success')->icon('heroicon-m-check-circle'),
                                TextEntry::make('absent_days')->label('Absent')->weight(FontWeight::Bold)->size('lg')->alignCenter()->color('danger')->icon('heroicon-m-x-circle'),
                                TextEntry::make('late_days')->label('Late')->weight(FontWeight::Bold)->size('lg')->alignCenter()->color('warning')->icon('heroicon-m-exclamation-circle'),
                            ]),
                    ]),

                Section::make('Payment Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('payout_type')->label('Payment Method')->badge()->weight(FontWeight::Bold)->color('info')->icon('heroicon-m-credit-card'),
                                TextEntry::make('paid_on')->label('Payment Date')->date('l, F j, Y')->icon('heroicon-m-calendar')->visible(fn($state) => $state !== null),
                                TextEntry::make('remarks')->label('Internal Notes')->markdown()->visible(fn($state) => filled($state))->default('No additional remarks'),
                            ]),
                    ]),
            ]);
    }
}