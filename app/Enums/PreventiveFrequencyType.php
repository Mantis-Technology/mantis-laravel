<?php

namespace App\Enums;

enum PreventiveFrequencyType: string
{
    case Once = 'once';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'Única vez',
            self::Daily => 'Diaria',
            self::Weekly => 'Semanal',
            self::Monthly => 'Mensual',
            self::Yearly => 'Anual',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Once => 'gray',
            self::Daily => 'info',
            self::Weekly => 'info',
            self::Monthly => 'warning',
            self::Yearly => 'warning',
        };
    }
}
