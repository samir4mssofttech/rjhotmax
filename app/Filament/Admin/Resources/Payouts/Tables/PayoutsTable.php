<?php

namespace App\Filament\Admin\Resources\Payouts\Tables;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
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
                TrashedFilter::make(),
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
                    ->visible(fn(Payout $record) => $record->status === PayoutStatus::Draft)
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
                    ->visible(fn(Payout $record) => $record->status === PayoutStatus::Approved)
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
                    BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn($r) => $r->update([
                                'status'      => PayoutStatus::Approved,
                                'approved_by' => auth()->id(),
                            ]));
                            Notification::make()->title('Payouts Approved')->success()->send();
                        }),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),

                    // --- UPDATED DYNAMIC DOWNLOAD ACTION ---
                    Action::make('downloadPayslip')
                        ->label('Download Payslip')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Payout $record) {
                            // 1. Get the employee via relationship
                            $employee = $record->employee;

                            // 2. Dynamically map the earnings from the Payout record
                            $earnings = [
                                ['label' => 'Basic Salary', 'amount' => $record->basic_salary],
                                ['label' => 'HRA', 'amount' => $record->hra],
                                ['label' => 'Conveyance', 'amount' => $record->conveyance],
                                ['label' => 'Medical Allowance', 'amount' => $record->medical],
                                ['label' => 'Overtime Pay', 'amount' => $record->overtime_amount],
                                ['label' => 'Other Allowances', 'amount' => $record->other_allowances],
                            ];

                            // 3. Dynamically map the deductions from the Payout record
                            $deductions = [
                                ['label' => 'Provident Fund (PF)', 'amount' => $record->pf],
                                ['label' => 'ESI', 'amount' => $record->esi],
                                ['label' => 'Absent Deduction', 'amount' => $record->absent_deduction],
                                ['label' => 'Late Deduction', 'amount' => $record->late_deduction],
                                ['label' => 'Other Deductions', 'amount' => $record->other_deductions],
                            ];

                            // 4. Remove zero values from the lists to keep the PDF clean
                            $earnings = array_filter($earnings, fn($item) => $item['amount'] > 0);
                            $deductions = array_filter($deductions, fn($item) => $item['amount'] > 0);

                            // 5. Prepare the final data array
                            $data = [
                                'employee' => $employee,
                                'month' => $record->payout_month,
                                'earnings' => $earnings,
                                'deductions' => $deductions,
                                'total_earnings' => $record->gross_salary,
                                'total_deductions' => $record->total_deductions,
                                'net_pay' => $record->net_salary,
                                'net_pay_words' => 'Rupees ' . number_format($record->net_salary, 2) . ' Only', // You can replace this with a number-to-words package
                                'logoPath' => 'images/rjlogo.png',
                            ];

                            $pdf = FacadePdf::loadView('pdfs.payslip', $data);

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, "Payslip_{$employee->name}_{$record->payout_month}.pdf");
                        }),
                    // ----------------------------------------
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
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
