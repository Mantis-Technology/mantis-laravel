<?php

namespace App\Models;

use App\Enums\TicketClosureResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Documented closure of a maintenance case (REQ-18): what work was done, the
 * result and the observations for future interventions. It is one more event
 * of the case history: it is created once, together with the transition to
 * Closed, and is never edited or overwritten. REQ-19 (traceability) and
 * REQ-21 (historical data) read it.
 *
 * @property int $id
 * @property int $ticket_id
 * @property string $work_performed
 * @property TicketClosureResult $result
 * @property string|null $observations
 * @property int|null $closed_by
 * @property Carbon $closed_at
 */
#[Fillable([
    'ticket_id',
    'work_performed',
    'result',
    'observations',
    'closed_by',
    'closed_at',
])]
class TicketClosure extends Model
{
    /**
     * Closures are immutable: they only keep their closing time.
     *
     * @var bool
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'ticket_id' => 'integer',
            'result' => TicketClosureResult::class,
            'closed_by' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
