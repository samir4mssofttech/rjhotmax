<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Attendance: string implements HasColor, HasIcon, HasLabel
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case LATE = 'late';
    case HALF_DAY = 'half-day';

    public function getColor(): string
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::ABSENT => 'danger',
            self::LATE => 'warning',
            self::HALF_DAY => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PRESENT => Heroicon::Check,
            self::ABSENT => Heroicon::XMark,
            self::LATE => Heroicon::Clock,
            self::HALF_DAY => 'heroicon-o-adjustments',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::LATE => 'Late',
            self::HALF_DAY => 'Half Day',
        };
    }
}
