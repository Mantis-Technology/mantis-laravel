<?php

namespace App\Models;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Maintenance ticket (REQ-12): a fault/incident report links the reporting
 * of an incident to the subsequent maintenance management. A ticket is
 * associated with its asset (asset card), the company (its tenant database),
 * the reporter, and, optionally, the location where the problem occurred. It
 * starts in the Reported state, ready for classification (REQ-13). REQ-14
 * owns the lifecycle: the allowed status flow and the audit trail of every
 * status change. REQ-13 classification (category, type, priority) is required
 * before the case can advance to Categorized. Assignment is REQ-15.
 *
 * @property int $id
 * @property int $asset_card_id
 * @property int|null $maintenance_category_id
 * @property int $reported_by
 * @property int|null $assigned_to
 * @property int|null $location_id
 * @property int|null $categorized_by
 * @property string $title
 * @property string|null $description
 * @property MaintenanceType|null $maintenance_type
 * @property MaintenancePriority|null $priority
 * @property TicketStatus $status
 * @property Carbon|null $categorized_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'asset_card_id',
    'maintenance_category_id',
    'reported_by',
    'assigned_to',
    'location_id',
    'categorized_by',
    'title',
    'description',
    'maintenance_type',
    'priority',
    'categorized_at',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * New tickets always start reported. Status changes must go through
     * transitionTo(), never through mass assignment.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'reported',
    ];

    protected static function booted(): void
    {
        static::created(function (Ticket $ticket): void {
            $ticket->statusTransitions()->create([
                'from_status' => null,
                'to_status' => $ticket->status,
                'changed_by' => $ticket->reported_by,
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'asset_card_id' => 'integer',
            'maintenance_category_id' => 'integer',
            'reported_by' => 'integer',
            'assigned_to' => 'integer',
            'location_id' => 'integer',
            'categorized_by' => 'integer',
            'maintenance_type' => MaintenanceType::class,
            'priority' => MaintenancePriority::class,
            'status' => TicketStatus::class,
            'categorized_at' => 'datetime',
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
     * Failure class / maintenance category this case was classified with
     * (REQ-13).
     *
     * @return BelongsTo<MaintenanceCategory, $this>
     */
    public function maintenanceCategory(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class);
    }

    /**
     * User who classified the case (REQ-13).
     *
     * @return BelongsTo<User, $this>
     */
    public function categorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'categorized_by');
    }

    /**
     * Whether the case already carries the classification required to move
     * to the Categorized status (REQ-13).
     */
    public function isClassified(): bool
    {
        return $this->maintenance_category_id !== null
            && $this->maintenance_type !== null
            && $this->priority !== null;
    }

    /**
     * Classifies the case and moves it to Categorized (REQ-13), recording
     * who classified it and the audit entry of the transition.
     */
    public function categorize(
        MaintenanceCategory $category,
        MaintenanceType $type,
        MaintenancePriority $priority,
        ?int $changedBy = null,
        ?string $note = null,
    ): ?string {
        $this->maintenance_category_id = $category->id;
        $this->maintenance_type = $type;
        $this->priority = $priority;
        $this->categorized_by = $changedBy;
        $this->categorized_at = Carbon::now();

        return $this->transitionTo(TicketStatus::Categorized, $changedBy, $note);
    }

    /**
     * Audit trail of the ticket lifecycle, oldest first.
     *
     * @return HasMany<TicketStatusTransition, $this>
     */
    public function statusTransitions(): HasMany
    {
        return $this->hasMany(TicketStatusTransition::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Whether the ticket can move to the given status right now: the
     * transition must exist in the lifecycle and the case must already have
     * a responsible technician before it can be assigned or attended.
     */
    public function canTransitionTo(TicketStatus $status): bool
    {
        if (! $this->status->canTransitionTo($status)) {
            return false;
        }

        if ($status->requiresResponsible() && $this->assigned_to === null) {
            return false;
        }

        return ! $status->requiresClassification() || $this->isClassified();
    }

    /**
     * Applies a lifecycle transition, records it in the audit trail and
     * returns an error message when the transition is not allowed, or null
     * when it was applied.
     */
    public function transitionTo(TicketStatus $status, ?int $changedBy = null, ?string $note = null): ?string
    {
        if (! $this->status->canTransitionTo($status)) {
            return "No se puede pasar de '{$this->status->label()}' a '{$status->label()}'.";
        }

        if ($status->requiresResponsible() && $this->assigned_to === null) {
            return 'El caso necesita un responsable antes de avanzar a este estado.';
        }

        if ($status->requiresClassification() && ! $this->isClassified()) {
            return 'El caso necesita estar clasificado antes de avanzar a este estado.';
        }

        $from = $this->status;

        DB::transaction(function () use ($from, $status, $changedBy, $note): void {
            $this->status = $status;
            $this->save();

            $this->statusTransitions()->create([
                'from_status' => $from,
                'to_status' => $status,
                'changed_by' => $changedBy,
                'note' => $note,
            ]);
        });

        return null;
    }
}
