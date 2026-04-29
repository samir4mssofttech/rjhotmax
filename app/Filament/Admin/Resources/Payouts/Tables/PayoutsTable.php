<?php

namespace App\Filament\Admin\Resources\Payouts\Tables;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('employee.name')
                    ->label('Employee')
                    ->searchable()->sortable(),
                TextColumn::make('payout_month')
                    ->label('Month')
                    ->sortable(),
                TextColumn::make('gross_salary')
                    ->label('Gross')
                    ->money('INR')->sortable(),
                TextColumn::make('total_deductions')
                    ->label('Deductions')
                    ->money('INR'),
                TextColumn::make('net_salary')
                    ->label('Net Pay')
                    ->money('INR')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                TextColumn::make('payout_type')
                    ->label('Mode')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('paid_on')
                    ->label('Paid On')
                    ->date(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PayoutStatus::class),
                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->visible(fn (Payout $record) => $record->status === PayoutStatus::Draft)
                    ->requiresConfirmation()
                    ->action(function (Payout $record) {
                        $record->update([
                            'status'      => PayoutStatus::Approved,
                            'approved_by' => Auth()->id(),
                        ]);
                        Notification::make()->title('Payout Approved')->success()->send();
                    }),

                Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Payout $record) => $record->status === PayoutStatus::Approved)
                    ->requiresConfirmation()
                    ->action(function (Payout $record) {
                        $record->update([
                            'status'  => PayoutStatus::Paid,
                            'paid_on' => now()->toDateString(),
                        ]);
                        Notification::make()->title('Marked as Paid')->success()->send();
                    }),

                Action::make('edit'),
                Action::make('delete'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    // Bulk approve
                    BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($r) => $r->update([
                                'status'      => PayoutStatus::Approved,
                                'approved_by' => auth()->id(),
                            ]));
                            Notification::make()->title('Payouts Approved')->success()->send();
                        }),
                ]),
            ])
           
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
