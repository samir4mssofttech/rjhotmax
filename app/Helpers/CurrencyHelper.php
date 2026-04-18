<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Convert Rupees to Paisa
     * Example: 10.50 → 1050
     */
    public static function rupeeToPaisa(float $amount): int
    {
        return (int) ($amount * 100);
    }

    /**
     * Convert Paisa to Rupees
     * Example: 1050 → 10.50
     */
    public static function paisaToRupee(?int $amount): float
    {
        return ($amount ?? 0) / 100;
    }

    public static function percentToInt(float $amount): int
    {
        return (int) ($amount * 100);
    }
    public static function intToPercent(?int $amount): float
    {
        return (float) ($amount ?? 0) / 100;
    }
}