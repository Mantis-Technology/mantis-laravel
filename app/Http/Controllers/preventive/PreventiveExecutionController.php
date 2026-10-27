<?php

declare(strict_types=1);

namespace App\Http\Controllers\preventive;

use App\Enums\PreventiveExecutionStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\preventive\Concerns\ResolvesPreventiveAccess;
use App\Models\PreventiveExecutionStatusTransition;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PreventiveExecutionController extends Controller
{
    use ResolvesPreventiveAccess;

    /**
     * REQ-17: agenda of preventive executions visible to the user. It can be
     * filtered by `status` and, with `assigned=me`, by the ones assigned to the
     * user.
     */
    public function index(Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canView($user), 403);

        $status = $this->statusFilter($request);
        $assignedToMe = $request->query('assigned') === 'me';

        $executions = $this->visibleExecutions($user)
            ->with(['assetCard:id,code', 'assignee:id,name', 'plan:id,title'])
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where('status', $status)
            )
            ->when(
                $assignedToMe,
                fn (Builder $query): Builder => $query->where('assigned_to_id', $user->id)
            )
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('PreventiveMaintenance/Executions/Index/index', [
            'executions' => $executions
                ->map(fn (PreventiveMaintenanceExecution $execution): array => $this->executionSummary($execution))
                ->values()
                ->all(),
            'filters' => [
                'status' => $status?->value,
                'assigned' => $assignedToMe ? 'me' : null,
            ],
            'statuses' => $this->statusOptions(),
            'can_manage' => $this->canManage($user),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        return Inertia::render('PreventiveMaintenance/Executions/Create/index', [
            'asset_cards' => $this->assetCards(),
            'maintenance_categories' => $this->maintenanceCategories(),
            'technicians' => $this->technicians(),
            'action' => route('preventive-executions.store'),
        ]);
    }

    /**
     * Schedules a one-off preventive execution that does not belong to a plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        $validated = $request->validate([
            'asset_card_id' => ['required', 'integer', 'exists:asset_cards,id'],
            'maintenance_category_id' => [
                'required',
                'integer',
                Rule::exists('maintenance_categories', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'assigned_to_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $execution = PreventiveMaintenanceExecution::query()->create([
            'asset_card_id' => (int) $validated['asset_card_id'],
            'maintenance_category_id' => (int) $validated['maintenance_category_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'scheduled_at' => $validated['scheduled_at'],
            'assigned_to_id' => $this->technicianId((int) $validated['assigned_to_id'], 'assigned_to_id'),
        ]);

        return redirect()
            ->route('preventive-executions.show', $execution)
            ->with('success', 'Mantenimiento programado correctamente.');
    }

    public function show(Request $request, PreventiveMaintenanceExecution $execution): Response
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canViewExecution($user, $execution), 403);

        $execution->load([
            'plan:id,title',
            'assetCard:id,code',
            'maintenanceCategory:id,name',
            'assignee:id,name',
            'completedBy:id,name',
            'statusTransitions.changedBy:id,name',
        ]);

        return Inertia::render('PreventiveMaintenance/Executions/Show/index', [
            'execution' => $this->executionDetail($execution, $user),
            'technicians' => $this->technicians(),
        ]);
    }

    /**
     * Moves the execution through its lifecycle. Skipping or cancelling needs
     * a reason and completing needs the execution notes, so an execution can
     * never close without the information its stage demands. Administrators
     * and the chief can apply any valid transition; the assigned technician
     * can only start and complete their own execution.
     */
    public function updateStatus(Request $request, PreventiveMaintenanceExecution $execution): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canViewExecution($user, $execution), 403);

        $requestedStatus = PreventiveExecutionStatus::tryFrom((string) $request->input('status'));

        if ($requestedStatus !== null) {
            abort_unless($this->canPerformTransition($user, $execution, $requestedStatus), 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(PreventiveExecutionStatus::class)],
            'note' => [
                Rule::requiredIf(
                    fn (): bool => $requestedStatus?->requiresReason() === true
                ),
                'nullable',
                'string',
                'max:500',
            ],
            'execution_notes' => [
                Rule::requiredIf(
                    fn (): bool => $requestedStatus === PreventiveExecutionStatus::Completed
                ),
                'nullable',
                'string',
                'max:5000',
            ],
            'findings' => ['nullable', 'string', 'max:5000'],
        ]);

        $status = PreventiveExecutionStatus::from($validated['status']);

        abort_unless($this->canPerformTransition($user, $execution, $status), 403);

        if ($status === PreventiveExecutionStatus::Completed) {
            $execution->fill([
                'execution_notes' => $validated['execution_notes'],
                'findings' => $validated['findings'] ?? null,
            ]);
        }

        $error = $execution->transitionTo($status, $user->id, $validated['note'] ?? null);

        if ($error !== null) {
            return redirect()
                ->route('preventive-executions.show', $execution)
                ->with('error', $error);
        }

        return redirect()
            ->route('preventive-executions.show', $execution)
            ->with('success', "La ejecución pasó a '{$status->label()}'.");
    }

    /**
     * Postpones a scheduled execution to a later date. The original date is
     * kept so the plan never regenerates it, and the reason goes to the audit
     * trail.
     */
    public function postpone(Request $request, PreventiveMaintenanceExecution $execution): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'note' => ['required', 'string', 'max:500'],
        ]);

        $error = $execution->postpone(
            Carbon::parse($validated['scheduled_at']),
            $user->id,
            $validated['note'],
        );

        if ($error !== null) {
            return redirect()
                ->route('preventive-executions.show', $execution)
                ->with('error', $error);
        }

        return redirect()
            ->route('preventive-executions.show', $execution)
            ->with('success', 'La ejecución fue aplazada.');
    }

    /**
     * Hands a scheduled or in progress execution over to another technician.
     * Only administrators and the maintenance chief can do it.
     */
    public function reassign(Request $request, PreventiveMaintenanceExecution $execution): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        $validated = $request->validate([
            'assigned_to_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $technician = User::query()->findOrFail(
            $this->technicianId((int) $validated['assigned_to_id'], 'assigned_to_id')
        );

        $error = $execution->reassignTo($technician, $user->id, $validated['note'] ?? null);

        if ($error !== null) {
            return redirect()
                ->route('preventive-executions.show', $execution)
                ->with('error', $error);
        }

        return redirect()
            ->route('preventive-executions.show', $execution)
            ->with('success', "La ejecución fue reasignada a {$technician->name}.");
    }

    private function statusFilter(Request $request): ?PreventiveExecutionStatus
    {
        $status = $request->query('status');

        return is_string($status) ? PreventiveExecutionStatus::tryFrom($status) : null;
    }

    /**
     * @return Builder<PreventiveMaintenanceExecution>
     */
    private function visibleExecutions(User $user): Builder
    {
        if ($this->canSeeAll($user)) {
            return PreventiveMaintenanceExecution::query();
        }

        return PreventiveMaintenanceExecution::query()->where('assigned_to_id', $user->id);
    }

    private function canViewExecution(User $user, PreventiveMaintenanceExecution $execution): bool
    {
        if (! $this->canView($user)) {
            return false;
        }

        return $this->canSeeAll($user) || $execution->assigned_to_id === $user->id;
    }

    private function canPerformTransition(User $user, PreventiveMaintenanceExecution $execution, PreventiveExecutionStatus $status): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        if ($execution->assigned_to_id !== $user->id) {
            return false;
        }

        return in_array($status, [
            PreventiveExecutionStatus::InProgress,
            PreventiveExecutionStatus::Completed,
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function executionSummary(PreventiveMaintenanceExecution $execution): array
    {
        return [
            'id' => $execution->id,
            'title' => $execution->title,
            'status' => $execution->status->value,
            'status_label' => $execution->status->label(),
            'status_color' => $execution->status->color(),
            'scheduled_at' => $execution->scheduled_at->toDateString(),
            'original_scheduled_at' => $execution->original_scheduled_at?->toDateString(),
            'is_postponed' => $execution->original_scheduled_at !== null
                && ! $execution->original_scheduled_at->isSameDay($execution->scheduled_at),
            'is_overdue' => $execution->isOverdue(),
            'asset_card' => $execution->assetCard ? [
                'id' => $execution->assetCard->id,
                'code' => $execution->assetCard->code,
            ] : null,
            'assignee' => $execution->assignee ? [
                'id' => $execution->assignee->id,
                'name' => $execution->assignee->name,
            ] : null,
            'plan' => $execution->plan ? [
                'id' => $execution->plan->id,
                'title' => $execution->plan->title,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function executionDetail(PreventiveMaintenanceExecution $execution, User $user): array
    {
        return $this->executionSummary($execution) + [
            'description' => $execution->description,
            'instructions' => $execution->instructions,
            'execution_notes' => $execution->execution_notes,
            'findings' => $execution->findings,
            'category' => $execution->maintenanceCategory ? [
                'id' => $execution->maintenanceCategory->id,
                'name' => $execution->maintenanceCategory->name,
            ] : null,
            'started_at' => $execution->started_at?->toIso8601String(),
            'completed_at' => $execution->completed_at?->toIso8601String(),
            'completed_by' => $execution->completedBy ? [
                'id' => $execution->completedBy->id,
                'name' => $execution->completedBy->name,
            ] : null,
            'is_final' => $execution->status->isFinal(),
            'status_transitions' => $execution->statusTransitions
                ->map(fn (PreventiveExecutionStatusTransition $transition): array => [
                    'id' => $transition->id,
                    'from_status' => $transition->from_status?->value,
                    'from_label' => $transition->from_status?->label(),
                    'to_status' => $transition->to_status->value,
                    'to_label' => $transition->to_status->label(),
                    'to_color' => $transition->to_status->color(),
                    'changed_by' => $transition->changedBy ? [
                        'id' => $transition->changedBy->id,
                        'name' => $transition->changedBy->name,
                    ] : null,
                    'note' => $transition->note,
                    'created_at' => $transition->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'allowed_transitions' => $this->transitionOptions($execution, $user),
            'can_postpone' => $this->canManage($user)
                && $execution->status === PreventiveExecutionStatus::Scheduled,
            'can_reassign' => $this->canManage($user)
                && in_array($execution->status, [
                    PreventiveExecutionStatus::Scheduled,
                    PreventiveExecutionStatus::InProgress,
                ], true),
        ];
    }

    /**
     * Transitions the user can perform right now, ready for the screen actions.
     *
     * @return list<array<string, mixed>>
     */
    private function transitionOptions(PreventiveMaintenanceExecution $execution, User $user): array
    {
        $transitions = array_filter(
            PreventiveExecutionStatus::cases(),
            fn (PreventiveExecutionStatus $status): bool => $execution->status->canTransitionTo($status)
                && $this->canPerformTransition($user, $execution, $status),
        );

        return array_values(array_map(
            fn (PreventiveExecutionStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'action_label' => $status->actionLabel(),
                'color' => $status->color(),
                'requires_reason' => $status->requiresReason(),
                'requires_notes' => $status === PreventiveExecutionStatus::Completed,
            ],
            $transitions,
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (PreventiveExecutionStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            PreventiveExecutionStatus::cases(),
        );
    }
}
