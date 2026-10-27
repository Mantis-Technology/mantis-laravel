<?php

namespace App\Models;

use App\Enums\PreventiveExecutionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit entry of a preventive maintenance execution lifecycle (REQ-17): records
 * each status change of a PreventiveMaintenanceExecution, who performed it and
 * when. Postpone and reassign also write entries with the same status to keep
 * a trace. This table only keeps created_at (UPDATED_AT = null).
 *
 * @property int $id
 * @property int $preventive_maintenance_execution_id
 * @property PreventiveExecutionStatus|null $from_status
 * @property PreventiveExecutionStatus $to_status
 * @property int|null $changed_by
 * @property string|null $note
 * @property Carbon|null $created_at
 */
#[Fillable([
    'preventive_maintenance_execution_id',
    'from_status',
    'to_status',
    'changed_by',
    'note',
])]
class PreventiveExecutionStatusTransition extends Model
{
    /**
     * Audit entries are immutable: they only keep their creation time.
     */
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'preventive_maintenance_execution_id' => 'integer',
            'from_status' => PreventiveExecutionStatus::class,
            'to_status' => PreventiveExecutionStatus::class,
            'changed_by' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PreventiveMaintenanceExecution, $this>
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(PreventiveMaintenanceExecution::class, 'preventive_maintenance_execution_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
