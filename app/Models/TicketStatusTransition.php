<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit entry of the maintenance case lifecycle (REQ-14): records each
 * status change of a ticket, who performed it and when. It only tracks
 * process state changes; the detailed information of the technical work
 * belongs to later requirements.
 *
 * @property int $id
 * @property int $ticket_id
 * @property TicketStatus|null $from_status
 * @property TicketStatus $to_status
 * @property int|null $changed_by
 * @property string|null $note
 * @property Carbon|null $created_at
 */
#[Fillable([
    'ticket_id',
    'from_status',
    'to_status',
    'changed_by',
    'note',
])]
class TicketStatusTransition extends Model
{
    /**
     * Audit entries are immutable: they only keep their creation time.
     *
     * @var string|null
     */
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'ticket_id' => 'integer',
            'from_status' => TicketStatus::class,
            'to_status' => TicketStatus::class,
            'changed_by' => 'integer',
            'created_at' => 'datetime',
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
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
