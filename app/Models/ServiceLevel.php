<?php

namespace App\Models;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Expected attention times for a maintenance case (REQ-16). A rule matches a
 * case when every non-null dimension (category, type, priority) equals the
 * case's classification; null columns are wildcards. The most specific active
 * rule wins.
 *
 * @property int $id
 * @property int|null $maintenance_category_id
 * @property MaintenanceType|null $maintenance_type
 * @property MaintenancePriority|null $priority
 * @property int $response_hours
 * @property int $resolution_hours
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'maintenance_category_id',
    'maintenance_type',
    'priority',
    'response_hours',
    'resolution_hours',
    'is_active',
])]
class ServiceLevel extends Model
{
    protected static function booted(): void
    {
        // `is_active` behaves like a soft delete: inactive rules are excluded
        // from every query unless explicitly included.
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('is_active', true);
        });
    }

    protected function casts(): array
    {
        return [
            'maintenance_category_id' => 'integer',
            'maintenance_type' => MaintenanceType::class,
            'priority' => MaintenancePriority::class,
            'response_hours' => 'integer',
            'resolution_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Includes inactive rules, like SoftDeletes' `withTrashed`.
     *
     * @param  Builder<ServiceLevel>  $query
     * @return Builder<ServiceLevel>
     */
    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    /**
     * Retrieves only inactive rules, like SoftDeletes' `onlyTrashed`.
     *
     * @param  Builder<ServiceLevel>  $query
     * @return Builder<ServiceLevel>
     */
    public function scopeOnlyInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active')->where('is_active', false);
    }

    /**
     * @return BelongsTo<MaintenanceCategory, $this>
     */
    public function maintenanceCategory(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class);
    }

    /**
     * Whether this rule applies to the given case: each dimension it
     * constrains must match the case's classification.
     */
    public function matches(Ticket $ticket): bool
    {
        if ($this->maintenance_category_id !== null
            && $this->maintenance_category_id !== $ticket->maintenance_category_id) {
            return false;
        }

        if ($this->maintenance_type !== null
            && $this->maintenance_type !== $ticket->maintenance_type) {
            return false;
        }

        if ($this->priority !== null && $this->priority !== $ticket->priority) {
            return false;
        }

        return true;
    }

    /**
     * Number of dimensions this rule constrains; the higher, the more
     * specific the rule is.
     */
    public function specificity(): int
    {
        return ($this->maintenance_category_id !== null ? 1 : 0)
            + ($this->maintenance_type !== null ? 1 : 0)
            + ($this->priority !== null ? 1 : 0);
    }
}
