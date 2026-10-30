<?php

namespace App\Enums;

enum PreventiveExecutionStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programado',
            self::InProgress => 'En proceso',
            self::Completed => 'Completado',
            self::Skipped => 'Omitido',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Label for the action that moves an execution into this status.
     */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Scheduled => 'Programar',
            self::InProgress => 'Iniciar',
            self::Completed => 'Completar',
            self::Skipped => 'Omitir',
            self::Cancelled => 'Cancelar',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'gray',
            self::InProgress => 'info',
            self::Completed => 'success',
            self::Skipped => 'warning',
            self::Cancelled => 'danger',
        };
    }

    public function requiresReason(): bool
    {
        return in_array($this, [self::Skipped, self::Cancelled], true);
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Scheduled => [self::InProgress, self::Skipped, self::Cancelled],
            self::InProgress => [self::Completed, self::Skipped, self::Cancelled],
            self::Completed => [],
            self::Skipped => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Skipped, self::Cancelled], true);
    }
}
