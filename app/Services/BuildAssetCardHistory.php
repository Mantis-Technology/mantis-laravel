<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssetCard;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\Ticket;

/**
 * Historial unificado de un activo: incidencias (tickets) y mantenimientos
 * preventivos, del más reciente al más antiguo.
 *
 * No filtra por usuario: hoy la ficha del activo solo la abre el
 * administrador del tenant (grupo `role:tenant_admin` en routes/tenant.php).
 * Si la ficha se abre a otros roles, hay que aplicar aquí las reglas de
 * visibilidad de tickets y ejecuciones.
 */
class BuildAssetCardHistory
{
    private const LIMIT = 50;

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(AssetCard $assetCard): array
    {
        $tickets = $assetCard->tickets()
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'key' => "ticket-{$ticket->id}",
                'type' => 'ticket',
                'title' => $ticket->title,
                'status_label' => $ticket->status->label(),
                'status_color' => $ticket->status->color(),
                'occurred_at' => $ticket->created_at?->toIso8601String(),
                'has_time' => true,
                'is_overdue' => false,
                'url' => route('tickets.show', $ticket),
            ]);

        $executions = $assetCard->preventiveExecutions()
            ->orderByDesc('scheduled_at')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (PreventiveMaintenanceExecution $execution): array => [
                'key' => "preventive-{$execution->id}",
                'type' => 'preventive',
                'title' => $execution->title,
                'status_label' => $execution->status->label(),
                'status_color' => $execution->status->color(),
                'occurred_at' => $execution->scheduled_at->toDateString(),
                'has_time' => false,
                'is_overdue' => $execution->isOverdue(),
                'url' => route('preventive-executions.show', $execution),
            ]);

        return $tickets
            ->concat($executions)
            ->sortByDesc(fn (array $item): string => (string) $item['occurred_at'])
            ->take(self::LIMIT)
            ->values()
            ->all();
    }
}
