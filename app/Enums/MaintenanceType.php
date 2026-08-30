<?php

namespace App\Enums;

enum MaintenanceType: string
{
    case Preventive = 'preventive';
    case Corrective = 'corrective';

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Preventivo',
            self::Corrective => 'Correctivo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Preventive => 'success',
            self::Corrective => 'warning',
        };
    }
}