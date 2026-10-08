<?php

namespace App\Dto;

use App\Enums\TicketStatus;
use App\Models\ServiceLevel;
use App\Models\Ticket;
use App\Models\TicketStatusTransition;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Computed service level status of a maintenance case (REQ-16). Times are
 * measured from the report date (REQ-13 classification determines which rule
 * applies). Response time is met when attention starts (InProgress) and
 * resolution time when the case is resolved. The status is derived on every
 * read, so it always reflects the current service level configuration.
 */
final readonly class TicketSlaStatus
{
    /**
     * Ratio of the target window after which a pending target is flagged as
     * at risk.
     */
    private const float AT_RISK_RATIO = 0.8;

    /**
     * @param  array<string, mixed>|null  $response
     * @param  array<string, mixed>|null  $resolution
     */
    public function __construct(
        public bool $hasServiceLevel,
        public ?array $response,
        public ?array $resolution,
    ) {}

    public static function fromTicket(Ticket $ticket, ?ServiceLevel $serviceLevel): self
    {
        if ($serviceLevel === null || $ticket->created_at === null) {
            return new self(false, null, null);
        }

        $createdAt = $ticket->created_at;
        $now = Date::now();

        $respondedAt = $ticket->statusTransitions
            ->first(fn (TicketStatusTransition $transition): bool => $transition->to_status === TicketStatus::InProgress)
            ?->created_at;

        $resolvedAt = $ticket->statusTransitions
            ->first(fn (TicketStatusTransition $transition): bool => $transition->to_status === TicketStatus::Resolved)
            ?->created_at;

        return new self(
            true,
            self::target($serviceLevel->response_hours, $createdAt, $respondedAt, $now),
            self::target($serviceLevel->resolution_hours, $createdAt, $resolvedAt, $now),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'has_service_level' => $this->hasServiceLevel,
            'response' => $this->response,
            'resolution' => $this->resolution,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function target(int $hours, CarbonInterface $createdAt, ?CarbonInterface $happenedAt, CarbonInterface $now): array
    {
        $due = $createdAt->copy()->addHours($hours);
        $elapsedMinutes = $createdAt->diffInMinutes($now, false);
        $remainingMinutes = $now->diffInMinutes($due, false);

        if ($happenedAt !== null) {
            $state = $happenedAt->lessThanOrEqualTo($due) ? 'met' : 'missed';
        } elseif ($now->greaterThan($due)) {
            $state = 'overdue';
        } elseif ($hours > 0 && $elapsedMinutes >= $hours * 60 * self::AT_RISK_RATIO) {
            $state = 'at_risk';
        } else {
            $state = 'on_track';
        }

        return [
            'target_hours' => $hours,
            'due_at' => $due->toIso8601String(),
            'happened_at' => $happenedAt?->toIso8601String(),
            'elapsed_hours' => round($elapsedMinutes / 60, 1),
            'remaining_hours' => round($remainingMinutes / 60, 1),
            'state' => $state,
            'state_label' => self::label($state),
            'state_color' => self::color($state),
        ];
    }

    private static function label(string $state): string
    {
        return match ($state) {
            'on_track' => 'En tiempo',
            'at_risk' => 'Por vencer',
            'overdue' => 'Vencido',
            'met' => 'Cumplido',
            'missed' => 'Incumplido',
            default => 'Sin nivel',
        };
    }

    private static function color(string $state): string
    {
        return match ($state) {
            'on_track', 'met' => 'success',
            'at_risk' => 'warning',
            'overdue', 'missed' => 'danger',
            default => 'gray',
        };
    }
}
