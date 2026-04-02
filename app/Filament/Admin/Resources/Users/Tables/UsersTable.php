<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('mobile_number')
                    ->searchable(),
                TextColumn::make('user_role')
                    ->badge()
                    ->sortable(),
                TextColumn::make('employee_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->sortable()
                    ->badge(),
                TextColumn::make('designation')
                    ->sortable()
                    ->badge(),
                TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                TextColumn::make('gender')
                    ->sortable()
                    ->badge(),
                TextColumn::make('aadhaar_number')
                    ->searchable(),
                TextColumn::make('pan_number')
                    ->searchable(),
                TextColumn::make('state')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
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
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->slideOver(),
                    EditAction::make()
                        ->slideOver(),
                ])
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
