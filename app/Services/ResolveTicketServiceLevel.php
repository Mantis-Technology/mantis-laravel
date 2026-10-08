<?php

namespace App\Services;

use App\Models\ServiceLevel;
use App\Models\Ticket;

/**
 * Resolves the service level that applies to a classified maintenance case
 * (REQ-16). Any active rule whose non-null dimensions match the case is a
 * candidate; the most specific one wins (most constrained dimensions), with
 * the oldest rule as a stable tie-breaker.
 */
class ResolveTicketServiceLevel
{
    public function for(Ticket $ticket): ?ServiceLevel
    {
        return ServiceLevel::query()
            ->get()
            ->filter(fn (ServiceLevel $level): bool => $level->matches($ticket))
            ->sortBy(fn (ServiceLevel $level): array => [
                -$level->specificity(),
                $level->id,
            ])
            ->first();
    }
}
