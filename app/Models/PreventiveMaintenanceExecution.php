<?php

namespace App\Models;

use App\Enums\PreventiveExecutionStatus;
use Database\Factories\PreventiveMaintenanceExecutionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Preventive maintenance execution (REQ-17): a concrete scheduled action
 * derived from a preventive plan or created as a one-off execution for an
 * asset card. Status changes only go through transitionTo() (same pattern
 * as tickets); postpone() and reassignTo() leave a trace in the audit trail
 * (preventive_execution_status_transitions). Skipped and Cancelled require a
 * reason, Completed requires execution_notes, and starting/completing record
 * timestamps and the completer.
 *
 * @property int $id
 * @property int|null $preventive_maintenance_plan_id
 * @property int $asset_card_id
 * @property int $maintenance_category_id
 * @property string $title
 * @property string|null $description
 * @property string|null $instructions
 * @property Carbon $scheduled_at
 * @property Carbon|null $original_scheduled_at
 * @property int $assigned_to_id
 * @property PreventiveExecutionStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property int|null $completed_by_id
 * @property string|null $execution_notes
 * @property string|null $findings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'preventive_maintenance_plan_id',
    'asset_card_id',
    'maintenance_category_id',
    'title',
    'description',
    'instructions',
    'scheduled_at',
    'original_scheduled_at',
    'assigned_to_id',
    'execution_notes',
    'findings',
])]
class PreventiveMaintenanceExecution extends Model
{
    /** @use HasFactory<PreventiveMaintenanceExecutionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'scheduled',
    ];

    protected static function booted(): void
    {
        static::creating(function (PreventiveMaintenanceExecution $execution): void {
            if ($execution->original_scheduled_at === null && $execution->scheduled_at !== null) {
                $execution->original_scheduled_at = $execution->scheduled_at;
            }
        });

        static::created(function (PreventiveMaintenanceExecution $execution): void {
            $execution->statusTransitions()->create([
                'from_status' => null,
                'to_status' => $execution->status,
                'changed_by' => Auth::id(),
                'note' => null,
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'preventive_maintenance_plan_id' => 'integer',
            'asset_card_id' => 'integer',
            'maintenance_category_id' => 'integer',
            'scheduled_at' => 'date',
            'original_scheduled_at' => 'date',
            'assigned_to_id' => 'integer',
            'completed_by_id' => 'integer',
            'status' => PreventiveExecutionStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PreventiveMaintenancePlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PreventiveMaintenancePlan::class, 'preventive_maintenance_plan_id');
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }

    /**
     * @return HasMany<PreventiveExecutionStatusTransition, $this>
     */
    public function statusTransitions(): HasMany
    {
        return $this->hasMany(PreventiveExecutionStatusTransition::class, 'preventive_maintenance_execution_id')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [PreventiveExecutionStatus::Scheduled, PreventiveExecutionStatus::InProgress]);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOverdue($query)
    {
        return $query->pending()->whereDate('scheduled_at', '<', Carbon::today());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to_id', $userId);
    }

    public function isOverdue(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        return $this->scheduled_at instanceof Carbon
            ? $this->scheduled_at->isBefore(Carbon::today())
            : Carbon::parse($this->scheduled_at)->isBefore(Carbon::today());
    }

    public function transitionTo(PreventiveExecutionStatus $status, ?int $changedBy = null, ?string $note = null): ?string
    {
        if (! $this->status->canTransitionTo($status)) {
            return "No se puede pasar de '{$this->status->label()}' a '{$status->label()}'.";
        }

        if ($status->requiresReason() && ($note === null || trim($note) === '')) {
            return 'Indica el motivo para este cambio.';
        }

        if ($status === PreventiveExecutionStatus::Completed && ($this->execution_notes === null || trim($this->execution_notes) === '')) {
            return 'Registra las notas de la ejecución antes de completarla.';
        }

        $from = $this->status;

        DB::transaction(function () use ($from, $status, $changedBy, $note): void {
            if ($status === PreventiveExecutionStatus::InProgress && $this->started_at === null) {
                $this->started_at = Carbon::now();
            }

            if ($status === PreventiveExecutionStatus::Completed) {
                if ($this->completed_at === null) {
                    $this->completed_at = Carbon::now();
                }
                $this->completed_by_id = $changedBy;
            }

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

    public function postpone(Carbon|string $newDate, ?int $changedBy = null, ?string $reason = null): ?string
    {
        if ($this->status !== PreventiveExecutionStatus::Scheduled) {
            return 'Solo se puede aplazar una ejecución programada.';
        }

        if ($reason === null || trim($reason) === '') {
            return 'Indica el motivo del aplazamiento.';
        }

        $newDateCarbon = $newDate instanceof Carbon ? $newDate->copy()->startOfDay() : Carbon::parse($newDate)->startOfDay();
        $today = Carbon::today();
        $current = $this->scheduled_at instanceof Carbon ? $this->scheduled_at->copy()->startOfDay() : Carbon::parse($this->scheduled_at)->startOfDay();

        if ($newDateCarbon->lt($today) || ! $newDateCarbon->gt($current)) {
            return 'La nueva fecha debe ser posterior a la fecha programada actual y no puede estar en el pasado.';
        }

        if ($this->preventive_maintenance_plan_id !== null) {
            $exists = static::query()
                ->where('preventive_maintenance_plan_id', $this->preventive_maintenance_plan_id)
                ->whereDate('scheduled_at', $newDateCarbon->toDateString())
                ->where('id', '!=', $this->id)
                ->exists();

            if ($exists) {
                return 'Ya existe una ejecución programada para ese plan en la fecha indicada.';
            }
        }

        $from = $this->scheduled_at;
        $note = 'Aplazado del '.$from->format('d/m/Y').' al '.$newDateCarbon->format('d/m/Y').': '.$reason;

        DB::transaction(function () use ($newDateCarbon, $changedBy, $note): void {
            $this->scheduled_at = $newDateCarbon;
            $this->save();

            $this->statusTransitions()->create([
                'from_status' => $this->status,
                'to_status' => $this->status,
                'changed_by' => $changedBy,
                'note' => $note,
            ]);
        });

        return null;
    }

    public function reassignTo(User $technician, ?int $changedBy = null, ?string $note = null): ?string
    {
        if (! in_array($this->status, [PreventiveExecutionStatus::Scheduled, PreventiveExecutionStatus::InProgress], true)) {
            return 'Solo se puede reasignar una ejecución programada o en proceso.';
        }

        if ($this->assigned_to_id === $technician->id) {
            return 'La ejecución ya está asignada a ese técnico.';
        }

        $baseNote = 'Reasignado a '.$technician->name;
        if ($note !== null && trim($note) !== '') {
            $baseNote .= ': '.trim($note);
        }

        DB::transaction(function () use ($technician, $changedBy, $baseNote): void {
            $this->assigned_to_id = $technician->id;
            $this->save();

            $this->statusTransitions()->create([
                'from_status' => $this->status,
                'to_status' => $this->status,
                'changed_by' => $changedBy,
                'note' => $baseNote,
            ]);
        });

        return null;
    }
}
