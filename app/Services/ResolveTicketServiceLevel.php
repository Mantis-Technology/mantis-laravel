<?php

namespace App\Services;

use App\Models\ServiceLevel;
use App\Models\Ticket;
use Illuminate\Support\Collection;

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
        return $this->among($ticket, ServiceLevel::query()->get());
    }

    /**
     * Resolves the level from an already-loaded set of active rules, so
     * reports can classify many cases without querying the table per ticket.
     *
     * @param  Collection<int, ServiceLevel>  $serviceLevels
     */
    public function among(Ticket $ticket, Collection $serviceLevels): ?ServiceLevel
    {
        return $serviceLevels
            ->filter(fn (ServiceLevel $level): bool => $level->matches($ticket))
            ->sortBy(fn (ServiceLevel $level): array => [
                -$level->specificity(),
                $level->id,
            ])
            ->first();
    }
}
