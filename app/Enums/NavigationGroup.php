<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasIcon, HasLabel
{
    case USER;
    case HRMS;
    case CUSTOMERS;
    case MEMBERS;
    case PRODUCTS;
    case SCHEMES;
    case METAL;
    case GOLDINVENTORY;

    public function getLabel(): string
    {
        return match ($this) {
            self::USER => 'USER MANAGEMENT',
            self::HRMS => 'EMPLOYEE MANAGEMENT',
            self::CUSTOMERS => 'CUSTOMER MANAGEMENT',
            self::MEMBERS => 'MEMBER MANAGEMENT',
            self::PRODUCTS => 'PRODUCT MASTERS',
            self::SCHEMES => 'SCHEME MASTERS',
            self::METAL => 'METAL RATES',
            self::GOLDINVENTORY => 'GOLD INVENTORY',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::USER => 'heroicon-o-user-circle',
            self::HRMS => 'heroicon-o-users',
            self::CUSTOMERS => 'heroicon-o-user-group',
            self::MEMBERS => 'heroicon-o-users',
            self::PRODUCTS => 'heroicon-o-banknotes',
            self::SCHEMES => 'heroicon-o-briefcase',
            self::METAL => 'heroicon-o-cube-transparent',
            self::GOLDINVENTORY => 'heroicon-o-currency-rupee',
        };
    }
}
