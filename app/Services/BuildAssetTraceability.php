<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PreventiveExecutionStatus;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\PreventiveExecutionStatusTransition;
use App\Models\TicketAssignment;
use App\Models\TicketStatusTransition;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Trazabilidad de un activo (REQ-19): línea de tiempo de eventos (quién hizo
 * qué, cuándo y con qué resultado) armada solo con las tablas de auditoría que
 * ya existen: transiciones y responsables de los tickets (REQ-14/15), cierre
 * documentado (REQ-18) y transiciones de las ejecuciones preventivas (REQ-17).
 * No guarda nada propio ni duplica el cierre: lo lee.
 *
 * No filtra por usuario: la ficha del activo solo la abre el administrador del
 * tenant (grupo `role:tenant_admin` en routes/tenant.php). Si se abre a otros
 * roles, hay que aplicar aquí las reglas de visibilidad de tickets y de
 * preventivos.
 */
class BuildAssetTraceability
{
    /**
     * Todos los eventos del activo, del más reciente al más antiguo.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(AssetCard $assetCard): Collection
    {
        return $this->ticketEvents($assetCard)
            ->concat($this->preventiveEvents($assetCard))
            ->sortBy([['_ts', 'desc'], ['_id', 'desc'], ['key', 'desc']])
            ->map(function (array $event): array {
                unset($event['_ts'], $event['_id']);

                return $event;
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function ticketEvents(AssetCard $assetCard): Collection
    {
        $tickets = $assetCard->tickets()
            ->with([
                'statusTransitions.changedBy:id,name',
                'assignments.technician:id,name',
                'assignments.assignedBy:id,name',
                'closure',
                'maintenanceCategory:id,name',
            ])
            ->get();

        $events = collect();

        foreach ($tickets as $ticket) {
            $url = route('tickets.show', $ticket);
            $first = $ticket->assignments->first();

            foreach ($ticket->statusTransitions as $transition) {
                [$action, $detail] = match ($transition->to_status) {
                    TicketStatus::Reported => ['Reportado', null],
                    TicketStatus::Categorized => [
                        'Clasificado',
                        $ticket->maintenanceCategory
                            ? 'Categoría: '.$ticket->maintenanceCategory->name
                            : null,
                    ],
                    TicketStatus::Assigned => [
                        'Asignado',
                        $first?->technician
                            ? 'Responsable: '.$first->technician->name
                            : null,
                    ],
                    TicketStatus::InProgress => ['Atención iniciada', null],
                    TicketStatus::Resolved => ['Resuelto', null],
                    TicketStatus::Closed => ['Cerrado', $ticket->closure?->result->label()],
                    TicketStatus::Cancelled => ['Cancelado', null],
                };

                /** @var TicketStatusTransition $transition */
                $events->push($this->event(
                    'ticket',
                    "ticket-{$ticket->id}-transition-{$transition->id}",
                    $action,
                    $ticket->title,
                    $detail,
                    $transition->note,
                    $transition->changedBy,
                    $transition->created_at ?? $ticket->created_at,
                    $transition->id,
                    $url,
                ));
            }

            // La primera asignación completa el evento «Asignado»; las
            // siguientes son reasignaciones, que no generan transición.
            foreach ($ticket->assignments->slice(1) as $assignment) {
                /** @var TicketAssignment $assignment */
                $events->push($this->event(
                    'ticket',
                    "ticket-{$ticket->id}-assignment-{$assignment->id}",
                    'Reasignado',
                    $ticket->title,
                    $assignment->technician
                        ? 'Nuevo responsable: '.$assignment->technician->name
                        : null,
                    $assignment->note,
                    $assignment->assignedBy,
                    $assignment->assigned_at,
                    $assignment->id,
                    $url,
                ));
            }
        }

        return $events;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function preventiveEvents(AssetCard $assetCard): Collection
    {
        $executions = $assetCard->preventiveExecutions()
            ->with('statusTransitions.changedBy:id,name')
            ->get();

        $events = collect();

        foreach ($executions as $execution) {
            $url = route('preventive-executions.show', $execution);

            foreach ($execution->statusTransitions as $transition) {
                /** @var PreventiveExecutionStatusTransition $transition */
                $events->push($this->event(
                    'preventive',
                    "preventive-{$execution->id}-transition-{$transition->id}",
                    $this->preventiveAction($transition),
                    $execution->title,
                    null,
                    $transition->note,
                    $transition->changedBy,
                    $transition->created_at ?? $execution->created_at,
                    $transition->id,
                    $url,
                ));
            }
        }

        return $events;
    }

    /**
     * Aplazar y reasignar una ejecución escriben una transición con el mismo
     * estado y un texto que empieza por «Aplazado» o «Reasignado» (REQ-17);
     * así se distinguen de un cambio de estado.
     */
    private function preventiveAction(PreventiveExecutionStatusTransition $transition): string
    {
        if ($transition->from_status === null) {
            return 'Programada';
        }

        if ($transition->from_status === $transition->to_status) {
            $note = (string) $transition->note;

            return match (true) {
                str_starts_with($note, 'Aplazado') => 'Aplazada',
                str_starts_with($note, 'Reasignado') => 'Reasignada',
                default => 'Ajuste',
            };
        }

        return match ($transition->to_status) {
            PreventiveExecutionStatus::Scheduled => 'Programada',
            PreventiveExecutionStatus::InProgress => 'Iniciada',
            PreventiveExecutionStatus::Completed => 'Completada',
            PreventiveExecutionStatus::Skipped => 'Omitida',
            PreventiveExecutionStatus::Cancelled => 'Cancelada',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function event(
        string $source,
        string $key,
        string $action,
        string $title,
        ?string $detail,
        ?string $note,
        ?User $user,
        ?CarbonInterface $at,
        int $id,
        string $url,
    ): array {
        return [
            'key' => $key,
            'source' => $source,
            'action' => $action,
            'title' => $title,
            'detail' => $detail,
            'note' => $note,
            'user' => $user ? ['id' => $user->id, 'name' => $user->name] : null,
            'occurred_at' => $at?->toIso8601String(),
            'url' => $url,
            '_ts' => $at?->getTimestamp() ?? 0,
            '_id' => $id,
        ];
    }
}
