<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PayoutType: string implements HasLabel
{
    case Cash = 'cash';
    case Bank = 'bank';
    case UPI  = 'upi';

    public function getLabel(): string
    {
        return match($this) {
            self::Cash => 'Cash',
            self::Bank => 'Bank Transfer',
            self::UPI  => 'UPI',
        };
    }
}
