<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Designation: string implements HasColor, HasIcon, HasLabel
{
    case SERVICE_BOY = 'service_boy';
    case HOUSE_KEEPING = 'house_keeping';
    case COOK = 'cook';
    case HEAD_COOK = 'head_cook';
    case ASSISTANT_COOK = 'assistant_cook';
    case HELPER = 'helper';
    case DISH_WASHER = 'dish_washer';
    case SUPERVISOR = 'supervisor';

    public function getColor(): string
    {
        return match ($this) {
            self::SERVICE_BOY => 'info',
            self::HOUSE_KEEPING => 'success',
            self::COOK => 'warning',
            self::HEAD_COOK => 'danger',
            self::ASSISTANT_COOK => 'primary',
            self::HELPER => 'gray',
            self::DISH_WASHER => 'cyan',
            self::SUPERVISOR => 'success',
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::SERVICE_BOY => Heroicon::Users,
            self::HOUSE_KEEPING => Heroicon::Sparkles,
            self::COOK => Heroicon::Fire,
            self::HEAD_COOK => Heroicon::Star,
            self::ASSISTANT_COOK => Heroicon::CheckBadge,
            self::HELPER => Heroicon::HandThumbUp,
            self::DISH_WASHER => Heroicon::OutlinedEyeDropper,
            self::SUPERVISOR => Heroicon::ShieldCheck,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SERVICE_BOY => 'Service Boy',
            self::HOUSE_KEEPING => 'House Keeping',
            self::COOK => 'Cook',
            self::HEAD_COOK => 'Head Cook',
            self::ASSISTANT_COOK => 'Assistant Cook',
            self::HELPER => 'Helper',
            self::DISH_WASHER => 'Dish Washer',
            self::SUPERVISOR => 'Supervisor',
        };
    }
}
