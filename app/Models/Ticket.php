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
 * Skeleton of the maintenance ticket (REQ-11): links the reporting of an
 * incident to the subsequent maintenance management. Only defines the
 * structure and the 3 initial states (Reported → Categorized → Assigned);
 * full registration from the user's perspective is REQ-12, categorization
 * is REQ-13, and the complete lifecycle (in progress, closed, etc.) is
 * REQ-14.
 *
 * @property int $id
 * @property int $asset_card_id
 * @property int $reported_by
 * @property int|null $assigned_to
 * @property string $title
 * @property TicketStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'asset_card_id',
    'reported_by',
    'assigned_to',
    'title',
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
