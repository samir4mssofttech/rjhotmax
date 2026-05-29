<?php

namespace App\Filament\Admin\Resources\Payouts\Tables;

use App\Enums\PayoutStatus;
use App\Exports\PayoutExport;
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
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class PayoutsTable
{
    // ── Helper: build the export rows from the filtered month ──
    private static function getExportRows(HasTable $livewire): array
    {
        // Read the chosen month from the filter state
        $monthState = $livewire->getTableFilterState('payout_month');
        $month = $monthState['value'] ?? null;

        if (! $month) {
            return [];
        }

        $payouts = Payout::with('employee')
            ->where('payout_month', $month)
            ->get();

        $rows = [];
        $sl = 1;

        foreach ($payouts as $payout) {
            $employee = $payout->employee;

            $rows[] = [
                'sl'         => $sl++,
                'name' => ($employee->name ?? '-') . ' (' . ($employee->account_number ?? '-') . ')',
                'account_no' => $employee->bank_account_number ?? '-',
                'ifsc'       => $employee->ifsc_code ?? '-',
                'bank_name'  => $employee->bank_name ?? '-',
                'amount'     => $payout->net_salary,
            ];
        }

        return $rows;
    }

    // ── Helper: read the selected month string ──
    private static function getSelectedMonth(HasTable $livewire): ?string
    {
        $state = $livewire->getTableFilterState('payout_month');

        return $state['value'] ?? null;
    }

    // ────────────────────────────────────────────────────────────
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

            // ── FILTERS (month filter added) ─────────────────
            ->filters([
                TrashedFilter::make(),

                // Month filter – dynamically lists all distinct months from DB
                SelectFilter::make('payout_month')
                    ->label('Payout Month')
                    ->options(
                        fn(): array => Payout::query()
                            ->distinct()
                            ->orderByDesc('payout_month')
                            ->pluck('payout_month', 'payout_month')
                            ->toArray()
                    )
                    ->searchable()
                    ->placeholder('All Months'),

                SelectFilter::make('status')
                    ->options(PayoutStatus::class),

                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
            ])

            // ── HEADER ACTIONS (Download PDF + Excel) ─────────
            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function (HasTable $livewire) {
                        $month = self::getSelectedMonth($livewire);

                        if (! $month) {
                            Notification::make()
                                ->title('Please select a Payout Month filter first.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $rows = self::getExportRows($livewire);

                        if (empty($rows)) {
                            Notification::make()
                                ->title('No payout records found for ' . $month)
                                ->warning()
                                ->send();

                            return;
                        }

                        $pdf = FacadePdf::loadView('pdfs.payouts-summary', [
                            'month' => $month,
                            'rows'  => $rows,
                        ])->setPaper('a4', 'landscape');

                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            "Payout_Summary_{$month}.pdf"
                        );
                    }),

                Action::make('downloadExcel')
                    ->label('Download Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (HasTable $livewire) {
                        $month = self::getSelectedMonth($livewire);

                        if (! $month) {
                            Notification::make()
                                ->title('Please select a Payout Month filter first.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $rows = self::getExportRows($livewire);

                        if (empty($rows)) {
                            Notification::make()
                                ->title('No payout records found for ' . $month)
                                ->warning()
                                ->send();

                            return;
                        }

                        return Excel::download(
                            new PayoutExport($rows, $month),
                            "Payout_Summary_{$month}.xlsx"
                        );
                    }),
            ])

            // ── ROW ACTIONS ───────────────────────────────────
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
                            'approved_by' => auth()->id(),
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

                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),

                    // Per-row payslip download (your existing action)
                    Action::make('downloadPayslip')
                        ->label('Download Payslip')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Payout $record) {
                            $employee = $record->employee;

                            $earnings = array_filter([
                                ['label' => 'Basic Salary',      'amount' => $record->basic_salary],
                                ['label' => 'HRA',               'amount' => $record->hra],
                                ['label' => 'Conveyance',        'amount' => $record->conveyance],
                                ['label' => 'Medical Allowance', 'amount' => $record->medical],
                                ['label' => 'Overtime Pay',      'amount' => $record->overtime_amount],
                                ['label' => 'Other Allowances',  'amount' => $record->other_allowances],
                            ], fn($item) => $item['amount'] > 0);

                            $deductions = array_filter([
                                ['label' => 'Provident Fund (PF)', 'amount' => $record->pf],
                                ['label' => 'ESI',                 'amount' => $record->esi],
                                ['label' => 'Absent Deduction',    'amount' => $record->absent_deduction],
                                ['label' => 'Late Deduction',      'amount' => $record->late_deduction],
                                ['label' => 'Other Deductions',    'amount' => $record->other_deductions],
                            ], fn($item) => $item['amount'] > 0);

                            $daysInMonth  = \Carbon\Carbon::parse($record->payout_month . '-01')->daysInMonth;
                            $absentDays = $record->absent_days ?? 0;
                            // Calculate LOP and round to 2 decimal places to avoid long floats
                            $lopAmount = round(($record->gross_salary / $daysInMonth) * $absentDays, 2);
                            $presentDays = $daysInMonth - $absentDays;

                            // $lopDays      = $record->absent_days ?? 0;
                            // $presentDays  = $daysInMonth - $lopDays;

                            $data = [
                                'employee'         => $employee,
                                'month'            => $record->payout_month,
                                'nod'              => $daysInMonth,
                                'ndp'              => $presentDays,
                                'lop'              => $lopAmount,
                                'earnings'         => $earnings,
                                'deductions'       => $deductions,
                                'total_earnings'   => $record->gross_salary,
                                'total_deductions' => $record->total_deductions,
                                'net_pay'          => $record->net_salary,
                                'net_pay_words'    => 'Rupees ' . ucwords(\Number::spell($record->net_salary)) . ' Only',
                                'logoPath'         => 'images/rjlogo.png',
                            ];

                            $pdf = FacadePdf::loadView('pdfs.payslip', $data);

                            return response()->streamDownload(
                                fn() => print($pdf->output()),
                                "Payslip_{$employee->name}_{$record->payout_month}.pdf"
                            );
                        }),

                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])

            // ── BULK ACTIONS ──────────────────────────────────
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
            ]);
    }
}
