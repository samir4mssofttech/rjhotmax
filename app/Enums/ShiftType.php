<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ShiftType: string implements HasColor, HasIcon, HasLabel
{
    case General = 'General';
    case Night = 'Night';
    case Rotational = 'Rotational';

    public function getLabel(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Night => 'Night',
            self::Rotational => 'Rotational',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::General => 'primary',
            self::Night => 'dark',
            self::Rotational => 'secondary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::General => 'heroicon-o-sun',
            self::Night => 'heroicon-o-moon',
            self::Rotational => 'heroicon-o-refresh',
        };
    }
}

