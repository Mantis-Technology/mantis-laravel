<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Historial de responsables de un caso (REQ-15): quién fue el técnico
 * responsable, quién lo asignó y durante qué periodo. Una asignación sin
 * ended_at es la vigente.
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $assigned_to
 * @property int|null $assigned_by
 * @property Carbon $assigned_at
 * @property Carbon|null $ended_at
 * @property string|null $note
 */
#[Fillable([
    'ticket_id',
    'assigned_to',
    'assigned_by',
    'assigned_at',
    'ended_at',
    'note',
])]
class TicketAssignment extends Model
{
    // Esta tabla maneja su propio periodo (assigned_at / ended_at).
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'ticket_id' => 'integer',
            'assigned_to' => 'integer',
            'assigned_by' => 'integer',
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
