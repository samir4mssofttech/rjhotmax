<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Gender: string implements HasColor, HasIcon, HasLabel
{
    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';

    public function getColor(): array
    {
        return match ($this) {
            self::MALE => Color::Blue,
            self::FEMALE => Color::Pink,
            self::OTHER => Color::Purple,
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::MALE => Heroicon::User,
            self::FEMALE => Heroicon::User,
            self::OTHER => Heroicon::User,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::OTHER => 'Other',
        };
    }
}
