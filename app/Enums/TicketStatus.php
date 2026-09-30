<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Reported = 'reported';
    case Categorized = 'categorized';
    case Assigned = 'assigned';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reportado',
            self::Categorized => 'Categorizado',
            self::Assigned => 'Asignado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Reported => 'gray',
            self::Categorized => 'info',
            self::Assigned => 'success',
        };
    }
}
