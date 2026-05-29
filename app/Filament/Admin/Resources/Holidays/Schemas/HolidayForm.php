<?php

namespace App\Filament\Admin\Resources\Holidays\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Holiday Information')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('holiday_date')
                            ->label('Holiday Date')
                            ->required()
                            ->date(),
                        TextInput::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->nullable(),
                    ])
            ]);
    }
}
