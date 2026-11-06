<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\StatisticsFilters;
use App\Dto\TicketSlaStatus;
use App\Enums\MaintenancePriority;
use App\Enums\PreventiveExecutionStatus;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\MaintenanceCategory;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\ServiceLevel;
use App\Models\Ticket;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the operational statistics of REQ-20 from the case lifecycle and
 * the preventive executions. Everything is derived on read: cases, attention
 * times, worked hours, category distribution and technician behaviour. The
 * service only describes what already happened; it never predicts failures.
 */
class BuildOperationalStatistics
{
    /**
     * Upper bound for the points of the trend chart.
     */
    private const TREND_MAX_BUCKETS = 120;

    /**
     * Short Spanish month names for the trend labels.
     *
     * @var array<int, string>
     */
    private const MONTHS = [
        1 => 'ene',
        2 => 'feb',
        3 => 'mar',
        4 => 'abr',
        5 => 'may',
        6 => 'jun',
        7 => 'jul',
        8 => 'ago',
        9 => 'sep',
        10 => 'oct',
        11 => 'nov',
        12 => 'dic',
    ];

    public function __construct(
        private readonly ResolveTicketServiceLevel $serviceLevels,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(StatisticsFilters $filters): array
    {
        $tickets = $this->tickets($filters)->get();
        $executions = $this->executions($filters)->get();
        $serviceLevels = ServiceLevel::query()->get();

        $slaByTicket = [];
        foreach ($tickets as $ticket) {
            $slaByTicket[$ticket->id] = TicketSlaStatus::fromTicket(
                $ticket,
                $this->serviceLevels->among($ticket, $serviceLevels),
            );
        }

        return [
            'summary' => $this->summary($tickets, $executions, $slaByTicket),
            'trend' => $this->trend($tickets, $executions, $filters),
            'ticket_status_distribution' => $this->ticketStatusDistribution($tickets),
            'preventive_status_distribution' => $this->preventiveStatusDistribution($executions),
            'priority_distribution' => $this->priorityDistribution($tickets),
            'category_distribution' => $this->categoryDistribution($tickets, $executions),
            'technicians' => $this->technicianPerformance($tickets, $executions, $slaByTicket),
            'attention' => $this->attention($tickets, $executions, $slaByTicket),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * @return Builder<Ticket>
     */
    private function tickets(StatisticsFilters $filters): Builder
    {
        return Ticket::query()
            ->with([
                'assetCard:id,code',
                'assignee:id,name',
                'maintenanceCategory:id,name',
                'statusTransitions',
            ])
            ->when(
                $filters->dateFrom !== null,
                fn (Builder $query): Builder => $query->where('created_at', '>=', $filters->dateFrom)
            )
            ->when(
                $filters->dateTo !== null,
                fn (Builder $query): Builder => $query->where('created_at', '<=', $filters->dateTo->copy()->endOfDay())
            )
            ->when(
                $filters->maintenanceCategoryId !== null,
                fn (Builder $query): Builder => $query->where('maintenance_category_id', $filters->maintenanceCategoryId)
            )
            ->when(
                $filters->assetCardId !== null,
                fn (Builder $query): Builder => $query->where('asset_card_id', $filters->assetCardId)
            )
            ->when(
                $filters->technicianId !== null,
                fn (Builder $query): Builder => $query->where('assigned_to', $filters->technicianId)
            )
            ->when(
                $filters->ticketStatus !== null,
                fn (Builder $query): Builder => $query->where('status', $filters->ticketStatus)
            )
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * @return Builder<PreventiveMaintenanceExecution>
     */
    private function executions(StatisticsFilters $filters): Builder
    {
        return PreventiveMaintenanceExecution::query()
            ->with([
                'assetCard:id,code',
                'assignee:id,name',
                'maintenanceCategory:id,name',
            ])
            ->when(
                $filters->dateFrom !== null,
                fn (Builder $query): Builder => $query->whereDate('scheduled_at', '>=', $filters->dateFrom->toDateString())
            )
            ->when(
                $filters->dateTo !== null,
                fn (Builder $query): Builder => $query->whereDate('scheduled_at', '<=', $filters->dateTo->toDateString())
            )
            ->when(
                $filters->maintenanceCategoryId !== null,
                fn (Builder $query): Builder => $query->where('maintenance_category_id', $filters->maintenanceCategoryId)
            )
            ->when(
                $filters->assetCardId !== null,
                fn (Builder $query): Builder => $query->where('asset_card_id', $filters->assetCardId)
            )
            ->when(
                $filters->technicianId !== null,
                fn (Builder $query): Builder => $query->where('assigned_to_id', $filters->technicianId)
            )
            ->when(
                $filters->preventiveStatus !== null,
                fn (Builder $query): Builder => $query->where('status', $filters->preventiveStatus)
            )
            ->orderBy('scheduled_at')
            ->orderBy('id');
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  Collection<int, PreventiveMaintenanceExecution>  $executions
     * @param  array<int, TicketSlaStatus>  $slaByTicket
     * @return array<string, mixed>
     */
    private function summary(Collection $tickets, Collection $executions, array $slaByTicket): array
    {
        $responseMinutes = [];
        $resolutionMinutes = [];
        $ticketWorkedMinutes = 0.0;

        foreach ($tickets as $ticket) {
            $startedAt = $this->firstTransitionAt($ticket, TicketStatus::InProgress);
            $resolvedAt = $this->firstTransitionAt($ticket, TicketStatus::Resolved);

            if ($ticket->created_at !== null && $startedAt !== null) {
                $responseMinutes[] = max(0, $ticket->created_at->diffInMinutes($startedAt, false));
            }

            if ($ticket->created_at !== null && $resolvedAt !== null) {
                $resolutionMinutes[] = max(0, $ticket->created_at->diffInMinutes($resolvedAt, false));
            }

            if ($startedAt !== null && $resolvedAt !== null) {
                $ticketWorkedMinutes += max(0, $startedAt->diffInMinutes($resolvedAt, false));
            }
        }

        $preventiveWorkedMinutes = 0.0;
        foreach ($executions as $execution) {
            if ($execution->started_at !== null && $execution->completed_at !== null) {
                $preventiveWorkedMinutes += max(0, $execution->started_at->diffInMinutes($execution->completed_at, false));
            }
        }

        $sla = $this->slaSummary($slaByTicket);

        return [
            'total_tickets' => $tickets->count(),
            'open_tickets' => $tickets
                ->reject(fn (Ticket $ticket): bool => $ticket->status->isFinal())
                ->count(),
            'resolved_tickets' => $tickets
                ->where('status', TicketStatus::Resolved)
                ->count(),
            'closed_tickets' => $tickets
                ->where('status', TicketStatus::Closed)
                ->count(),
            'cancelled_tickets' => $tickets
                ->where('status', TicketStatus::Cancelled)
                ->count(),
            'total_preventive' => $executions->count(),
            'completed_preventive' => $executions
                ->where('status', PreventiveExecutionStatus::Completed)
                ->count(),
            'overdue_preventive' => $executions
                ->filter(fn (PreventiveMaintenanceExecution $execution): bool => $execution->isOverdue())
                ->count(),
            'average_response_hours' => $this->averageHours($responseMinutes),
            'average_resolution_hours' => $this->averageHours($resolutionMinutes),
            'ticket_worked_hours' => round($ticketWorkedMinutes / 60, 1),
            'preventive_worked_hours' => round($preventiveWorkedMinutes / 60, 1),
            'total_worked_hours' => round(($ticketWorkedMinutes + $preventiveWorkedMinutes) / 60, 1),
            'sla_evaluated' => $sla['evaluated'],
            'sla_met' => $sla['met'],
            'sla_missed' => $sla['missed'],
            'sla_compliance_rate' => $sla['rate'],
            'sla_overdue_tickets' => $sla['overdue'],
            'sla_at_risk_tickets' => $sla['at_risk'],
        ];
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  Collection<int, PreventiveMaintenanceExecution>  $executions
     * @return list<array<string, mixed>>
     */
    private function trend(Collection $tickets, Collection $executions, StatisticsFilters $filters): array
    {
        $eventDates = collect();

        foreach ($tickets as $ticket) {
            if ($ticket->created_at !== null) {
                $eventDates->push($ticket->created_at);
            }

            $resolvedAt = $this->firstTransitionAt($ticket, TicketStatus::Resolved);

            if ($resolvedAt !== null) {
                $eventDates->push($resolvedAt);
            }
        }

        foreach ($executions as $execution) {
            if ($execution->status === PreventiveExecutionStatus::Completed && $execution->completed_at !== null) {
                $eventDates->push($execution->completed_at);
            }
        }

        $start = $filters->dateFrom?->copy() ?? $eventDates->min() ?? Carbon::now();
        $end = $filters->dateTo?->copy() ?? $eventDates->max() ?? Carbon::now();

        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();
        $unit = $this->trendUnit($start, $end);

        $cursor = match ($unit) {
            'day' => $start->copy(),
            'week' => $start->copy()->startOfWeek(Carbon::MONDAY),
            default => $start->copy()->startOfMonth(),
        };

        $periods = [];

        while ($cursor->lessThanOrEqualTo($end) && count($periods) < self::TREND_MAX_BUCKETS) {
            $key = $this->periodKey($cursor, $unit);

            $periods[$key] = [
                'period' => $key,
                'label' => $this->periodLabel($cursor, $unit),
                'reported' => 0,
                'resolved' => 0,
                'preventive_completed' => 0,
            ];

            $cursor = match ($unit) {
                'day' => $cursor->copy()->addDay(),
                'week' => $cursor->copy()->addWeek(),
                default => $cursor->copy()->addMonth(),
            };
        }

        foreach ($tickets as $ticket) {
            if ($ticket->created_at !== null) {
                $this->incrementPeriod($periods, $this->periodKey($ticket->created_at, $unit), 'reported');
            }

            $resolvedAt = $this->firstTransitionAt($ticket, TicketStatus::Resolved);

            if ($resolvedAt !== null) {
                $this->incrementPeriod($periods, $this->periodKey($resolvedAt, $unit), 'resolved');
            }
        }

        foreach ($executions as $execution) {
            if ($execution->status === PreventiveExecutionStatus::Completed && $execution->completed_at !== null) {
                $this->incrementPeriod(
                    $periods,
                    $this->periodKey($execution->completed_at, $unit),
                    'preventive_completed',
                );
            }
        }

        return array_values($periods);
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return list<array<string, mixed>>
     */
    private function ticketStatusDistribution(Collection $tickets): array
    {
        $counts = [];

        foreach ($tickets as $ticket) {
            $counts[$ticket->status->value] = ($counts[$ticket->status->value] ?? 0) + 1;
        }

        return array_map(
            fn (TicketStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $counts[$status->value] ?? 0,
            ],
            TicketStatus::cases(),
        );
    }

    /**
     * @param  Collection<int, PreventiveMaintenanceExecution>  $executions
     * @return list<array<string, mixed>>
     */
    private function preventiveStatusDistribution(Collection $executions): array
    {
        $counts = [];

        foreach ($executions as $execution) {
            $counts[$execution->status->value] = ($counts[$execution->status->value] ?? 0) + 1;
        }

        return array_map(
            fn (PreventiveExecutionStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $counts[$status->value] ?? 0,
            ],
            PreventiveExecutionStatus::cases(),
        );
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return list<array<string, mixed>>
     */
    private function priorityDistribution(Collection $tickets): array
    {
        $counts = [];

        foreach ($tickets as $ticket) {
            $priority = $ticket->getAttribute('priority');
            $key = $priority instanceof MaintenancePriority ? $priority->value : 'unclassified';
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $rows = array_map(
            fn (MaintenancePriority $priority): array => [
                'value' => $priority->value,
                'label' => $priority->label(),
                'color' => $priority->color(),
                'count' => $counts[$priority->value] ?? 0,
            ],
            MaintenancePriority::cases(),
        );

        $rows[] = [
            'value' => 'unclassified',
            'label' => 'Sin clasificar',
            'color' => 'gray',
            'count' => $counts['unclassified'] ?? 0,
        ];

        return $rows;
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  Collection<int, PreventiveMaintenanceExecution>  $executions
     * @return list<array<string, mixed>>
     */
    private function categoryDistribution(Collection $tickets, Collection $executions): array
    {
        /** @var Collection<int, string> $categoryNames */
        $categoryNames = MaintenanceCategory::query()
            ->withInactive()
            ->pluck('name', 'id');

        $rows = [];

        foreach ($tickets as $ticket) {
            $id = $ticket->maintenance_category_id;
            $key = $id === null ? 'none' : (string) $id;

            $rows[$key] ??= [
                'id' => $id,
                'name' => $this->categoryName($categoryNames, $id),
                'tickets' => 0,
                'preventive' => 0,
            ];

            $rows[$key]['tickets']++;
        }

        foreach ($executions as $execution) {
            $id = $execution->maintenance_category_id;
            $key = (string) $id;

            $rows[$key] ??= [
                'id' => $id,
                'name' => $this->categoryName($categoryNames, $id),
                'tickets' => 0,
                'preventive' => 0,
            ];

            $rows[$key]['preventive']++;
        }

        $rows = array_values($rows);

        usort($rows, function (array $left, array $right): int {
            $leftTotal = $left['tickets'] + $left['preventive'];
            $rightTotal = $right['tickets'] + $right['preventive'];

            return $rightTotal <=> $leftTotal ?: $left['name'] <=> $right['name'];
        });

        return $rows;
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  Collection<int, PreventiveMaintenanceExecution>  $executions
     * @param  array<int, TicketSlaStatus>  $slaByTicket
     * @return list<array<string, mixed>>
     */
    private function technicianPerformance(Collection $tickets, Collection $executions, array $slaByTicket): array
    {
        $technicians = User::query()
            ->role(Role::TECHNICIAN->value)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = [];

        foreach ($technicians as $technician) {
            $technicianTickets = $tickets->where('assigned_to', $technician->id);
            $technicianExecutions = $executions->where('assigned_to_id', $technician->id);

            $resolutionMinutes = [];
            $workedMinutes = 0.0;
            $resolved = 0;
            $slaEvaluated = 0;
            $slaMet = 0;

            foreach ($technicianTickets as $ticket) {
                $startedAt = $this->firstTransitionAt($ticket, TicketStatus::InProgress);
                $resolvedAt = $this->firstTransitionAt($ticket, TicketStatus::Resolved);

                if ($resolvedAt !== null) {
                    $resolved++;

                    if ($ticket->created_at !== null) {
                        $resolutionMinutes[] = max(0, $ticket->created_at->diffInMinutes($resolvedAt, false));
                    }
                }

                if ($startedAt !== null && $resolvedAt !== null) {
                    $workedMinutes += max(0, $startedAt->diffInMinutes($resolvedAt, false));
                }

                $sla = $slaByTicket[$ticket->id] ?? null;
                $resolution = $sla?->resolution;
                $state = is_array($resolution) ? $resolution['state'] : null;

                if (in_array($state, ['met', 'missed'], true)) {
                    $slaEvaluated++;

                    if ($state === 'met') {
                        $slaMet++;
                    }
                }
            }

            $completedPreventive = 0;
            foreach ($technicianExecutions as $execution) {
                if ($execution->status !== PreventiveExecutionStatus::Completed) {
                    continue;
                }

                $completedPreventive++;

                if ($execution->started_at !== null && $execution->completed_at !== null) {
                    $workedMinutes += max(0, $execution->started_at->diffInMinutes($execution->completed_at, false));
                }
            }

            $rows[] = [
                'id' => $technician->id,
                'name' => $technician->name,
                'assigned_tickets' => $technicianTickets->count(),
                'resolved_tickets' => $resolved,
                'completed_preventive' => $completedPreventive,
                'average_resolution_hours' => $this->averageHours($resolutionMinutes),
                'worked_hours' => round($workedMinutes / 60, 1),
                'sla_compliance_rate' => $slaEvaluated > 0
                    ? round($slaMet / $slaEvaluated * 100, 1)
                    : null,
            ];
        }

        usort($rows, function (array $left, array $right): int {
            if ($left['worked_hours'] !== $right['worked_hours']) {
                return $right['worked_hours'] <=> $left['worked_hours'];
            }

            return $left['name'] <=> $right['name'];
        });

        return $rows;
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @param  Collection<int, PreventiveMaintenanceExecution>  $executions
     * @param  array<int, TicketSlaStatus>  $slaByTicket
     * @return array<string, mixed>
     */
    private function attention(Collection $tickets, Collection $executions, array $slaByTicket): array
    {
        $overduePreventive = $executions
            ->filter(fn (PreventiveMaintenanceExecution $execution): bool => $execution->isOverdue())
            ->sortBy(fn (PreventiveMaintenanceExecution $execution): string => $execution->scheduled_at->toDateString())
            ->take(5)
            ->map(fn (PreventiveMaintenanceExecution $execution): array => [
                'id' => $execution->id,
                'title' => $execution->title,
                'scheduled_at' => $execution->scheduled_at->toDateString(),
                'days_overdue' => max(0, (int) $execution->scheduled_at->startOfDay()->diffInDays(Carbon::today())),
                'asset_card' => $execution->assetCard ? [
                    'id' => $execution->assetCard->id,
                    'code' => $execution->assetCard->code,
                ] : null,
                'assignee' => $execution->assignee?->name,
            ])
            ->values()
            ->all();

        $slaTickets = [];

        foreach ($tickets as $ticket) {
            $sla = $slaByTicket[$ticket->id] ?? null;
            $resolution = $sla?->resolution;

            if (! is_array($resolution) || ! in_array($resolution['state'], ['overdue', 'at_risk'], true)) {
                continue;
            }

            $slaTickets[] = [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'status_color' => $ticket->status->color(),
                'priority' => $ticket->priority ? [
                    'value' => $ticket->priority->value,
                    'label' => $ticket->priority->label(),
                    'color' => $ticket->priority->color(),
                ] : null,
                'asset_card' => $ticket->assetCard ? [
                    'id' => $ticket->assetCard->id,
                    'code' => $ticket->assetCard->code,
                ] : null,
                'assignee' => $ticket->assignee?->name,
                'sla_state' => (string) $resolution['state'],
                'sla_state_label' => (string) $resolution['state_label'],
                'sla_state_color' => (string) $resolution['state_color'],
                'due_at' => (string) $resolution['due_at'],
            ];
        }

        usort($slaTickets, fn (array $left, array $right): int => $left['due_at'] <=> $right['due_at']);

        return [
            'overdue_preventive' => $overduePreventive,
            'sla_tickets' => array_slice($slaTickets, 0, 5),
        ];
    }

    /**
     * @param  array<int, TicketSlaStatus>  $slaByTicket
     * @return array{evaluated: int, met: int, missed: int, overdue: int, at_risk: int, rate: float|null}
     */
    private function slaSummary(array $slaByTicket): array
    {
        $evaluated = 0;
        $met = 0;
        $missed = 0;
        $overdue = 0;
        $atRisk = 0;

        foreach ($slaByTicket as $sla) {
            $resolution = $sla->resolution;

            if (! is_array($resolution)) {
                continue;
            }

            $state = $resolution['state'];

            if ($state === 'met' || $state === 'missed') {
                $evaluated++;

                if ($state === 'met') {
                    $met++;
                } else {
                    $missed++;
                }

                continue;
            }

            if ($state === 'overdue') {
                $overdue++;
            }

            if ($state === 'at_risk') {
                $atRisk++;
            }
        }

        return [
            'evaluated' => $evaluated,
            'met' => $met,
            'missed' => $missed,
            'overdue' => $overdue,
            'at_risk' => $atRisk,
            'rate' => $evaluated > 0 ? round($met / $evaluated * 100, 1) : null,
        ];
    }

    private function firstTransitionAt(Ticket $ticket, TicketStatus $status): ?CarbonInterface
    {
        $transition = $ticket->statusTransitions
            ->first(fn ($transition): bool => $transition->to_status === $status);

        return $transition?->created_at;
    }

    /**
     * @param  list<float|int>  $minutes
     */
    private function averageHours(array $minutes): ?float
    {
        if ($minutes === []) {
            return null;
        }

        return round(array_sum($minutes) / count($minutes) / 60, 1);
    }

    /**
     * @param  Collection<int, string>  $categoryNames
     */
    private function categoryName(Collection $categoryNames, ?int $categoryId): string
    {
        if ($categoryId === null) {
            return 'Sin clasificar';
        }

        $name = $categoryNames->get($categoryId);

        return is_string($name) ? $name : 'Sin clasificar';
    }

    private function trendUnit(CarbonInterface $start, CarbonInterface $end): string
    {
        $days = (int) $start->diffInDays($end);

        if ($days <= 31) {
            return 'day';
        }

        if ($days <= 180) {
            return 'week';
        }

        return 'month';
    }

    private function periodKey(CarbonInterface $date, string $unit): string
    {
        return match ($unit) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
            default => $date->format('Y-m'),
        };
    }

    private function periodLabel(CarbonInterface $date, string $unit): string
    {
        return match ($unit) {
            'day' => $date->format('d/m'),
            'week' => 'Sem '.$date->format('d/m'),
            default => self::MONTHS[$date->month].' '.$date->format('Y'),
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $periods
     */
    private function incrementPeriod(array &$periods, string $key, string $field): void
    {
        if (! isset($periods[$key])) {
            return;
        }

        $periods[$key][$field] = (int) $periods[$key][$field] + 1;
    }
}
