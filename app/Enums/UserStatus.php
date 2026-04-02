<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum UserStatus: int implements HasColor, HasIcon, HasLabel
{
    case ACTIVE = 1;
    case SUSPENDED = 0;

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::SUSPENDED => 'danger',
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::ACTIVE => Heroicon::CheckCircle,
            self::SUSPENDED => Heroicon::XCircle,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
        };
    }
}
