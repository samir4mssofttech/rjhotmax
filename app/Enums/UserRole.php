<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum UserRole: string implements HasColor, HasIcon, HasLabel
{
    case ADMIN = 'admin';
    case AGENT = 'agent';
    case HR = 'hr';
    case EMPLOYEE = 'employee';

    public function getColor(): string
    {
        return match ($this) {
            self::ADMIN => 'danger',
            self::AGENT => 'primary',
            self::HR => 'success',
            self::EMPLOYEE => 'info',
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::ADMIN => Heroicon::User,
            self::AGENT => Heroicon::User,
            self::HR => Heroicon::User,
            self::EMPLOYEE => Heroicon::User,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::AGENT => 'Agent',
            self::HR => 'HR',
            self::EMPLOYEE => 'Employee',
        };
    }
}
