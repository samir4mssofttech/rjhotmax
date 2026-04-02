<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EmployeeStatus: string implements HasColor, HasIcon, HasLabel
{
    case ACTIVE = 'active';
    case PROBATION = 'probation';
    case NOTICE_PERIOD = 'notice_period';
    case EXIT = 'exit';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PROBATION => 'Probation',
            self::NOTICE_PERIOD => 'Notice Period',
            self::EXIT => 'Exit',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::PROBATION => 'warning',
            self::NOTICE_PERIOD => 'danger',
            self::EXIT => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',        // ✅ active
            self::PROBATION => 'heroicon-o-clock',            // ⏳ probation
            self::NOTICE_PERIOD => 'heroicon-o-exclamation-circle', // ⚠️ notice
            self::EXIT => 'heroicon-o-x-circle',              // ❌ exit
        };
    }
}