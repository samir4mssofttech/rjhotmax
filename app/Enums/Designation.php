<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Designation: string implements HasColor, HasIcon, HasLabel
{
    case MANAGER = 'manager';
    case BUSINESS_HEAD = 'business_head';
    case HUMAN_RESOURCES = 'human_resources';
    case SALES_EXECUTIVE = 'sales_executive';

    public function getColor(): string
    {
        return match ($this) {
            self::MANAGER => 'info',
            self::BUSINESS_HEAD => 'success',
            self::HUMAN_RESOURCES => 'primary',
            self::SALES_EXECUTIVE => 'warning',
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::MANAGER => Heroicon::UserGroup,
            self::BUSINESS_HEAD => Heroicon::Briefcase,
            self::HUMAN_RESOURCES => Heroicon::ClipboardDocument,
            self::SALES_EXECUTIVE => Heroicon::ChartBar,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MANAGER => 'Manager',
            self::BUSINESS_HEAD => 'Business Head',
            self::HUMAN_RESOURCES => 'Human Resources',
            self::SALES_EXECUTIVE => 'Sales Executive',
        };
    }
}
