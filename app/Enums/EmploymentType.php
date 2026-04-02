<?php
namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmploymentType: string implements HasLabel
{
    case FULL_TIME = 'full_time';
    case CONTRACT  = 'contract';
    case INTERN    = 'intern';

    public function getLabel(): ?string
    {
        return match($this) {
            self::FULL_TIME => 'Full-time',
            self::CONTRACT  => 'Contract',
            self::INTERN    => 'Intern',
        };
    }
}