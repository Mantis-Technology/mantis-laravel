<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maintenance ticket (REQ-12): a fault/incident report links the reporting
 * of an incident to the subsequent maintenance management. A ticket is
 * associated with its asset (asset card), the company (its tenant database),
 * the reporter, and, optionally, the location where the problem occurred. It
 * starts in the Reported state, ready for classification (REQ-13). The full
 * lifecycle is REQ-14 and assignment is REQ-15.
 *
 * @property int $id
 * @property int $asset_card_id
 * @property int $reported_by
 * @property int|null $assigned_to
 * @property int|null $location_id
 * @property string $title
 * @property string|null $description
 * @property TicketStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'asset_card_id',
    'reported_by',
    'assigned_to',
    'location_id',
    'title',
    'description',
    'status',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'asset_card_id' => 'integer',
            'reported_by' => 'integer',
            'assigned_to' => 'integer',
            'location_id' => 'integer',
            'status' => TicketStatus::class,
        ];
    }

    /**
     * @return BelongsTo<AssetCard, $this>
     */
    public function assetCard(): BelongsTo
    {
        return $this->belongsTo(AssetCard::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Advances the ticket to the next status in the skeleton (Reported →
     * Categorized → Assigned). Only allows moving forward one step at a
     * time: it can't skip a status or go backwards. Returns an error
     * message if the transition is invalid, or null if it was applied.
     */
    public function transitionTo(TicketStatus $status): ?string
    {
        $next = match ($this->status) {
            TicketStatus::Reported => TicketStatus::Categorized,
            TicketStatus::Categorized => TicketStatus::Assigned,
            TicketStatus::Assigned => null,
        };

        if ($status !== $next) {
            return "Cannot move from '{$this->status->label()}' to '{$status->label()}' directly.";
        }

        $this->update(['status' => $status]);

        return null;
    }
}
