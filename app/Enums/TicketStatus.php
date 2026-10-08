<?php

namespace App\Enums;

/**
 * Lifecycle of a maintenance case (REQ-14): from the initial report until
 * its attention is closed. The `allowedTransitions()` map is the single
 * source of truth for the process flow; the Ticket model applies it on top
 * of its own guards.
 */
enum TicketStatus: string
{
    case Reported = 'reported';
    case Categorized = 'categorized';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reportado',
            self::Categorized => 'Categorizado',
            self::Assigned => 'Asignado',
            self::InProgress => 'En atención',
            self::Resolved => 'Resuelto',
            self::Closed => 'Cerrado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Label for the action that moves a case into this status.
     */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Reported => 'Reportar',
            self::Categorized => 'Categorizar',
            self::Assigned => 'Asignar técnico',
            self::InProgress => 'Iniciar atención',
            self::Resolved => 'Marcar como resuelto',
            self::Closed => 'Cerrar caso',
            self::Cancelled => 'Cancelar caso',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Reported => 'gray',
            self::Categorized => 'info',
            self::Assigned => 'warning',
            self::InProgress => 'warning',
            self::Resolved => 'success',
            self::Closed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Statuses a case can move to from this one. A case only moves forward
     * one step at a time: it cannot skip a stage, and a case cannot be
     * closed without having been categorized, assigned and attended.
     * Cancellation is only allowed before the attention starts.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Reported => [self::Categorized, self::Cancelled],
            self::Categorized => [self::Assigned, self::Cancelled],
            self::Assigned => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Resolved],
            self::Resolved => [self::Closed],
            self::Closed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * A final status has no outgoing transitions (closed or cancelled).
     */
    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Statuses that require the case to already have a responsible
     * technician assigned before it can enter them.
     */
    public function requiresResponsible(): bool
    {
        return in_array($this, [self::Assigned, self::InProgress], true);
    }

    /**
     * Statuses that require the case to be classified (REQ-13) before it can
     * enter them.
     */
    public function requiresClassification(): bool
    {
        return $this === self::Categorized;
    }
}
