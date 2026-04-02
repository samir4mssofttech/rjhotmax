<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum ApplicantStatus: string implements HasColor, HasIcon, HasLabel
{
    case INITIATED = 'initiated';
    case OFFERED = 'offered';
    case ACCEPTED = 'accepted';
    case JOINING_LETTER_SENT = 'joining_letter_sent';
    case APPOINTMENT_LETTER_SENT = 'appointment_letter_sent';
    case REJECTED = 'rejected';

    public function getColor(): string
    {
        return match ($this) {
            self::INITIATED => 'success',
            self::OFFERED => 'info',
            self::ACCEPTED => 'success',
            self::JOINING_LETTER_SENT => 'info',
            self::APPOINTMENT_LETTER_SENT => 'info',
            self::REJECTED => 'danger',
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::INITIATED => Heroicon::CheckCircle,
            self::OFFERED => Heroicon::PaperAirplane,
            self::ACCEPTED => Heroicon::CheckCircle,
            self::JOINING_LETTER_SENT => Heroicon::PaperAirplane,
            self::APPOINTMENT_LETTER_SENT => Heroicon::PaperAirplane,
            self::REJECTED => Heroicon::XCircle,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::INITIATED => 'Initiated',
            self::OFFERED => 'Offer Letter Sent',
            self::ACCEPTED => 'Offer Accepted',
            self::JOINING_LETTER_SENT => 'Joining Letter Sent',
            self::APPOINTMENT_LETTER_SENT => 'Appointment Letter Sent',
            self::REJECTED => 'Rejected',
        };
    }
}
