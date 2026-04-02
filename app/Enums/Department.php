<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Department: string implements HasColor, HasIcon, HasLabel
{
    case SALES = 'sales';
    case MANAGEMENT = 'management';
    case SUPPORT = 'support';

    public function getColor(): string
    {
        return match ($this) {
            self::SALES => 'info',
            self::MANAGEMENT => 'success',
            self::SUPPORT => 'primary',
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::SALES => Heroicon::Briefcase,
            self::MANAGEMENT => Heroicon::Cog8Tooth,
            self::SUPPORT => Heroicon::UserGroup,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SALES => 'Sales',
            self::MANAGEMENT => 'Management',
            self::SUPPORT => 'Support',
        };
    }
}
