<?php

namespace App\Filament\Admin\Resources\Payouts\Pages;

use App\Filament\Admin\Resources\Payouts\PayoutResource;
use App\Models\Employee;
use App\Models\Payout;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            // Action::make('generate_bulk')
            //     ->label('Generate Month Payouts')
            //     ->icon('heroicon-o-arrow-path')
            //     ->color('info')
            //     ->form([
            //         TextInput::make('month')
            //             ->label('Month (YYYY-MM)')
            //             ->placeholder('2025-01')
            //             ->required(),
            //     ])
            //     ->action(function (array $data) {
            //         $month = $data['month'];
            //         $employees = Employee::where('is_active', true)->get();
            //         $created = 0;

            //         foreach ($employees as $employee) {
            //             // Skip if already exists
            //             if (Payout::where('employee_id', $employee->id)
            //                 ->where('payout_month', $month)->exists()
            //             ) {
            //                 continue;
            //             }

            //             $calc = Payout::calculateForEmployee($employee, $month);

            //             Payout::create(array_merge($calc, [
            //                 'employee_id'  => $employee->id,
            //                 'payout_month' => $month,
            //                 'created_by'   => auth()->id(),
            //             ]));
            //             $created++;
            //         }

            //         Notification::make()
            //             ->title("Generated {$created} payouts for {$month}")
            //             ->success()
            //             ->send();
            //     }),
        ];
    }
}
