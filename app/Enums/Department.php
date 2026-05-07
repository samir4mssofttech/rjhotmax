<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum Department: string implements HasColor, HasIcon, HasLabel
{
    case SKILLED = 'skilled';
    case SEMI_SKILLED = 'semi_skilled';
    case HIGH_SKILLED = 'high_skilled';
    case UNSKILLED = 'unskilled';


    public function getColor(): string
    {
        return match ($this) {
            self::SKILLED => 'info',
            self::SEMI_SKILLED => 'success',
            self::HIGH_SKILLED => 'primary',
            self::UNSKILLED => 'secondary'
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::SKILLED => Heroicon::OutlinedSpeakerWave,
            self::SEMI_SKILLED => Heroicon::Star,
            self::HIGH_SKILLED => Heroicon::Sparkles,
            self::UNSKILLED => Heroicon::UserCircle,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SKILLED => 'Skilled',
            self::SEMI_SKILLED => 'Semi Skilled',
            self::HIGH_SKILLED => 'High Skilled',
            self::UNSKILLED => 'Unskilled',
        };
    }
}
