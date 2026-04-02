<?php

namespace App\Filament\Admin\Resources\Employees\Tables;

use App\Enums\EmployeeStatus;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
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
                IconColumn::make('status')
                    ->label('Status')
                    ->boolean(),

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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
