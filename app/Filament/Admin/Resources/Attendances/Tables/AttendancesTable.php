<?php

namespace App\Filament\Admin\Resources\Attendances\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
    TextColumn::make('employee.name')
        ->searchable()
        ->sortable(),

    TextColumn::make('shift.type')
        ->label('Shift')
        ->badge()
        ->sortable(),

    TextColumn::make('date')
        ->date()
        ->sortable(),

    TextColumn::make('check_in_time')
        ->time(),

    TextColumn::make('check_out_time')
        ->time(),

    TextColumn::make('worked_minutes')
        ->label('Worked Hours')
        ->formatStateUsing(fn ($state) => 
            $state ? number_format($state / 60, 2) . ' hrs' : '—'
        )
        ->sortable(),

    TextColumn::make('overtime_minutes')
        ->label('Overtime')
        ->formatStateUsing(fn ($state) => 
            $state 
                ? ($state >= 0 
                    ? '+' . number_format($state / 60, 2) 
                    : number_format($state / 60, 2)
                  ) . ' hrs'
                : '—'
        )
        ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
        ->sortable(),

    IconColumn::make('is_late')
        ->label('Late')
        ->boolean()
        ->trueColor('danger')
        ->falseColor('success'),

    TextColumn::make('status')
        ->badge()
        ->colors([
            'present' => 'success',
            'absent' => 'danger',
            'half_day' => 'warning',
            'on_leave' => 'info',
            'holiday' => 'gray',
            'weekly_off' => 'gray',
        ]),

    TextColumn::make('enteredBy.name')
        ->label('Entered By')
        ->toggleable(isToggledHiddenByDefault: true),

    TextColumn::make('created_at')
        ->dateTime()
        ->toggleable(isToggledHiddenByDefault: true),
])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'present'    => 'Present',
                        'absent'     => 'Absent',
                        'half_day'   => 'Half Day',
                        'on_leave'   => 'On Leave',
                        'holiday'    => 'Holiday',
                        'weekly_off' => 'Weekly Off',
                    ]),

                SelectFilter::make('shift')
                    ->relationship('shift', 'type')
                    ->preload(),

                SelectFilter::make('is_late')
                    ->label('Late entries only')
                    ->options([1 => 'Yes', 0 => 'No']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
