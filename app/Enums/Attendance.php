<?php

namespace App\Enums;
use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Attendance: string implements HasColor, HasIcon, HasLabel
{
    case PRESENT = 'present';
    case ABSENT = 'absent';
    case HALF_DAY   = 'half_day';
    case ON_LEAVE   = 'on_leave';
    case HOLIDAY    = 'holiday';
    case WEEKLY_OFF = 'weekly_off';

    public function getColor(): string
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::ABSENT => 'danger',
            self::HALF_DAY => 'primary',
            self::ON_LEAVE => 'warning',
            self::HOLIDAY => 'info',
            self::WEEKLY_OFF => 'secondary',
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::PRESENT => Heroicon::Check,
            self::ABSENT => Heroicon::XMark,
            self::HALF_DAY => Heroicon::Clock,
            self::ON_LEAVE => Heroicon::Briefcase,
            self::HOLIDAY => Heroicon::Sparkles,
            self::WEEKLY_OFF => Heroicon::Calendar,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::HALF_DAY => 'Half Day',
            self::ON_LEAVE => 'On Leave',
            self::HOLIDAY => 'Holiday',
            self::WEEKLY_OFF => 'Weekly Off',
        };
    }
}
