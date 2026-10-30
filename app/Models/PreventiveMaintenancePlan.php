<?php

namespace App\Models;

use App\Enums\PreventiveFrequencyType;
use App\Enums\PreventivePlanStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Preventive maintenance plan (REQ-17): schedules recurring or one-time
 * preventive work for an asset card. A plan links the asset, the maintenance
 * category, the responsible technician and the person who created it. Status
 * changes only go through transitionTo() and the plan drives generation of
 * executions by recurrence. The plan itself does not track work performed;
 * executions and their audit trail hold the execution history.
 *
 * @property int $id
 * @property int $asset_card_id
 * @property int $maintenance_category_id
 * @property string $title
 * @property string|null $description
 * @property string|null $instructions
 * @property PreventiveFrequencyType $frequency_type
 * @property int $interval
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property int $responsible_id
 * @property int|null $created_by_id
 * @property PreventivePlanStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'asset_card_id',
    'maintenance_category_id',
    'title',
    'description',
    'instructions',
    'frequency_type',
    'interval',
    'starts_at',
    'ends_at',
    'responsible_id',
    'created_by_id',
])]
class PreventiveMaintenancePlan extends Model
{
    /** @use HasFactory<PreventiveMaintenancePlanFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'interval' => 1,
    ];

    protected function casts(): array
    {
        return [
            'asset_card_id' => 'integer',
            'maintenance_category_id' => 'integer',
            'frequency_type' => PreventiveFrequencyType::class,
            'interval' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'responsible_id' => 'integer',
            'created_by_id' => 'integer',
            'status' => PreventivePlanStatus::class,
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
     * @return BelongsTo<MaintenanceCategory, $this>
     */
    public function maintenanceCategory(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return HasMany<PreventiveMaintenanceExecution, $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(PreventiveMaintenanceExecution::class, 'preventive_maintenance_plan_id')
            ->orderBy('scheduled_at')
            ->orderBy('id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('status', PreventivePlanStatus::Active);
    }

    public function isRecurring(): bool
    {
        return $this->frequency_type !== PreventiveFrequencyType::Once;
    }

    public function hasEnded(): bool
    {
        if ($this->ends_at === null) {
            return false;
        }

        return $this->ends_at->isBefore(Carbon::today());
    }

    public function transitionTo(PreventivePlanStatus $status): ?string
    {
        if (! $this->status->canTransitionTo($status)) {
            return "No se puede pasar de '{$this->status->label()}' a '{$status->label()}'.";
        }

        $this->status = $status;
        $this->save();

        return null;
    }

    public function markAsFinished(): void
    {
        if ($this->status === PreventivePlanStatus::Active) {
            $this->status = PreventivePlanStatus::Finished;
            $this->save();
        }
    }
}
