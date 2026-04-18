<?php

namespace App\Filament\Admin\Resources\Employees\Tables;

use App\Enums\EmployeeStatus;
use App\Helpers\CurrencyHelper;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')->label('Employee Id'),
                ImageColumn::make('profile_photo')
                    ->label('Photo')
                    ->circular()
                    ->size(40),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('branch.display_name')
                    ->label('Assigned Branch')
                    ->badge(),
                TextColumn::make('salary')
                    ->numeric()
                    ->label('Salary')
                    ->getStateUsing(fn($record) => CurrencyHelper::paisaToRupee($record->salary))
                    ->money('INR', true),
                TextColumn::make('join_date')
                    ->label('Join Date')
                    ->date()
                    ->sortable()
                    ->formatStateUsing(
                        fn($state) =>
                        $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : null
                    ),

                TextColumn::make('confirmation_date')
                    ->label('Confirmation Date')
                    ->sortable()
                    ->placeholder('Not Confirmed')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(
                        fn($state) =>
                        $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : null
                    ),
                TextColumn::make('exit_date')
                    ->placeholder('Still Working')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(
                        fn($state) =>
                        $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : null
                    ),
                IconColumn::make('employee_status')
                    ->tooltip(fn($record) => 'Employee is in ' . $record->employee_status->getLabel())
                    ->label('Employee Status')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Is Verified')
                    ->boolean(),

                TextColumn::make('basic_salary')
                    ->numeric()
                    ->label('Basic Salary')
                    ->money('INR', true)
                    ->getStateUsing(fn($record) => CurrencyHelper::paisaToRupee($record->basic_salary))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hra')
                    ->numeric()
                    ->label('HRA')
                    ->money('INR', true)
                    ->placeholder('Not Assigned')
                    ->getStateUsing(fn($record) => CurrencyHelper::paisaToRupee($record->hra))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('conveyance')
                    ->numeric()
                    ->label('Conveyance')
                    ->money('INR', true)
                    ->placeholder('Not Assigned')
                    ->getStateUsing(fn($record) => CurrencyHelper::paisaToRupee($record->conveyance))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('medical')
                    ->numeric()
                    ->label('Medical')
                    ->money('INR', true)
                    ->placeholder('Not Assigned')
                    ->getStateUsing(fn($record) => CurrencyHelper::paisaToRupee($record->medical))

                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('other_allowances')
                    ->numeric()
                    ->label('Other Allowances')
                    ->money('INR', true)
                    ->getStateUsing(fn($record) => CurrencyHelper::paisaToRupee($record->other_allowances))

                    ->placeholder('Not Assigned')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pf')
                    ->numeric()
                    ->label('PF')
                    ->money('INR', true)
                    ->placeholder('Not Assigned')
                    ->getStateUsing(fn($record) => CurrencyHelper::paisaToRupee($record->pf))

                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('esi')
                    ->numeric()
                    ->label('ESI')
                    ->money('INR', true)
                    ->placeholder('Not Assigned')
                    ->getStateUsing(fn($record) => CurrencyHelper::paisaToRupee($record->esi))

                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('status')
                    ->native(false)
                    ->options(
                        collect(EmployeeStatus::cases())
                            ->mapWithKeys(fn($case) => [
                                $case->value => $case->getLabel()
                            ])
                    ),
                Filter::make('join_date')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($q) =>
                                $q->whereDate('join_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($q) =>
                                $q->whereDate('join_date', '<=', $data['until'])
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make()
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
