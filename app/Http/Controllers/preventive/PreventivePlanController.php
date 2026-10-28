<?php

declare(strict_types=1);

namespace App\Http\Controllers\preventive;

use App\Enums\PreventiveFrequencyType;
use App\Enums\PreventivePlanStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Controllers\preventive\Concerns\ResolvesPreventiveAccess;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\PreventiveMaintenancePlan;
use App\Models\User;
use App\Services\PreventiveMaintenance\RecurrenceGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PreventivePlanController extends Controller
{
    use ResolvesPreventiveAccess;

    public function __construct(
        private readonly RecurrenceGenerator $recurrence,
    ) {}

    /**
     * REQ-17: lists the preventive maintenance plans visible to the user. The
     * plan state can be filtered through the `status` query parameter.
     */
    public function index(Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canView($user), 403);

        $status = $this->statusFilter($request);

        $plans = $this->visiblePlans($user)
            ->with(['assetCard:id,code', 'maintenanceCategory:id,name', 'responsible:id,name'])
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where('status', $status)
            )
            ->orderBy('title')
            ->get();

        return Inertia::render('PreventiveMaintenance/Plans/Index/Index', [
            'plans' => $plans
                ->map(fn (PreventiveMaintenancePlan $plan): array => $this->planSummary($plan))
                ->values()
                ->all(),
            'filters' => ['status' => $status?->value],
            'statuses' => $this->statusOptions(),
            'can_manage' => $this->canManage($user),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        return Inertia::render('PreventiveMaintenance/Plans/Create/Index', [
            'asset_cards' => $this->assetCards(),
            'maintenance_categories' => $this->maintenanceCategories(),
            'technicians' => $this->technicians(),
            'frequencies' => $this->frequencyOptions(),
            'action' => route('preventive-plans.store'),
        ]);
    }

    /**
     * Creates an active plan and schedules its first executions up to the
     * generation horizon. A one-time plan always ends on its start date.
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
            'frequency_type' => ['required', Rule::enum(PreventiveFrequencyType::class)],
            'interval' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('frequency_type') !== PreventiveFrequencyType::Once->value
                ),
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'responsible_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $frequency = PreventiveFrequencyType::from($validated['frequency_type']);
        $isOnce = $frequency === PreventiveFrequencyType::Once;

        $plan = PreventiveMaintenancePlan::query()->create([
            'asset_card_id' => (int) $validated['asset_card_id'],
            'maintenance_category_id' => (int) $validated['maintenance_category_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'frequency_type' => $frequency,
            'interval' => $isOnce ? 1 : (int) $validated['interval'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $isOnce ? $validated['starts_at'] : ($validated['ends_at'] ?? null),
            'responsible_id' => $this->technicianId((int) $validated['responsible_id'], 'responsible_id'),
            'created_by_id' => $user->id,
        ]);

        $created = $this->recurrence->generate($plan);

        return redirect()
            ->route('preventive-plans.show', $plan)
            ->with('success', 'Plan creado. '.$this->generatedMessage($created));
    }

    public function show(Request $request, PreventiveMaintenancePlan $plan): Response
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canViewPlan($user, $plan), 403);

        $plan->load([
            'assetCard:id,code',
            'maintenanceCategory:id,name',
            'responsible:id,name',
            'createdBy:id,name',
        ]);

        $executions = $plan->executions()
            ->with('assignee:id,name')
            ->when(
                ! $this->canSeeAll($user),
                fn ($query) => $query->where('assigned_to_id', $user->id)
            )
            ->limit(100)
            ->get();

        return Inertia::render('PreventiveMaintenance/Plans/Show/Index', [
            'plan' => $this->planDetail($plan, $user, $executions->all()),
            'can_manage' => $this->canManage($user),
            'can_generate' => $this->canManage($user) && $plan->status === PreventivePlanStatus::Active,
        ]);
    }

    public function edit(Request $request, PreventiveMaintenancePlan $plan): Response
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        $plan->load([
            'assetCard:id,code',
            'maintenanceCategory:id,name',
            'responsible:id,name',
            'createdBy:id,name',
        ]);

        return Inertia::render('PreventiveMaintenance/Plans/Edit/Index', [
            'plan' => $this->planDetail($plan, $user, []),
            'maintenance_categories' => $this->maintenanceCategories(),
            'technicians' => $this->technicians(),
            'action' => route('preventive-plans.update', $plan),
        ]);
    }

    /**
     * Only the descriptive data, the category, the responsible and the end
     * date can change. The schedule (frequency, interval, start date) is
     * fixed once the plan exists; to change it, cancel the plan and create a
     * new one. A new responsible applies to the executions generated from now
     * on, not to the ones already scheduled.
     */
    public function update(Request $request, PreventiveMaintenancePlan $plan): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        if (in_array($plan->status, [PreventivePlanStatus::Finished, PreventivePlanStatus::Cancelled], true)) {
            return redirect()
                ->route('preventive-plans.show', $plan)
                ->with('error', 'No se puede editar un plan finalizado o cancelado.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'maintenance_category_id' => [
                'required',
                'integer',
                Rule::exists('maintenance_categories', 'id')->where('is_active', true),
            ],
            'responsible_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'ends_at' => ['nullable', 'date', 'after_or_equal:'.$plan->starts_at->toDateString()],
        ]);

        $attributes = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'maintenance_category_id' => (int) $validated['maintenance_category_id'],
            'responsible_id' => $this->technicianId((int) $validated['responsible_id'], 'responsible_id'),
        ];

        if ($plan->frequency_type !== PreventiveFrequencyType::Once) {
            $attributes['ends_at'] = $validated['ends_at'] ?? null;
        }

        $plan->update($attributes);

        return redirect()
            ->route('preventive-plans.show', $plan)
            ->with('success', 'Plan actualizado correctamente.');
    }

    /**
     * Pauses, reactivates or cancels a plan. Reactivating it schedules the
     * executions that were left pending while it was paused. Finishing is
     * done by the recurrence service when the end date passes, never by hand.
     */
    public function updateStatus(Request $request, PreventiveMaintenancePlan $plan): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                PreventivePlanStatus::Active->value,
                PreventivePlanStatus::Paused->value,
                PreventivePlanStatus::Cancelled->value,
            ])],
        ]);

        $status = PreventivePlanStatus::from($validated['status']);

        $error = $plan->transitionTo($status);

        if ($error !== null) {
            return redirect()
                ->route('preventive-plans.show', $plan)
                ->with('error', $error);
        }

        $message = "El plan pasó a '{$status->label()}'.";

        if ($status === PreventivePlanStatus::Active) {
            $created = $this->recurrence->generate($plan);

            if ($plan->status === PreventivePlanStatus::Finished) {
                return redirect()
                    ->route('preventive-plans.show', $plan)
                    ->with('error', 'El plan finalizó porque su fecha de fin ya pasó.');
            }

            $message .= ' '.$this->generatedMessage($created);
        }

        return redirect()
            ->route('preventive-plans.show', $plan)
            ->with('success', $message);
    }

    /**
     * The "generate next occurrences" button: schedules whatever is missing up
     * to the horizon. It never runs by itself on a GET request.
     */
    public function generateOccurrences(Request $request, PreventiveMaintenancePlan $plan): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canManage($user), 403);

        if ($plan->status !== PreventivePlanStatus::Active) {
            return redirect()
                ->route('preventive-plans.show', $plan)
                ->with('error', 'Solo un plan activo puede generar ejecuciones.');
        }

        $created = $this->recurrence->generate($plan);

        if ($plan->status === PreventivePlanStatus::Finished) {
            return redirect()
                ->route('preventive-plans.show', $plan)
                ->with('error', 'El plan finalizó porque su fecha de fin ya pasó.');
        }

        return redirect()
            ->route('preventive-plans.show', $plan)
            ->with('success', $this->generatedMessage($created));
    }

    private function generatedMessage(int $created): string
    {
        return match (true) {
            $created === 0 => 'No hay ejecuciones nuevas por programar.',
            $created === 1 => 'Se programó 1 ejecución nueva.',
            default => "Se programaron {$created} ejecuciones nuevas.",
        };
    }

    private function statusFilter(Request $request): ?PreventivePlanStatus
    {
        $status = $request->query('status');

        return is_string($status) ? PreventivePlanStatus::tryFrom($status) : null;
    }

    /**
     * @return Builder<PreventiveMaintenancePlan>
     */
    private function visiblePlans(User $user): Builder
    {
        if ($this->canSeeAll($user)) {
            return PreventiveMaintenancePlan::query();
        }

        return PreventiveMaintenancePlan::query()->where(function (Builder $query) use ($user): void {
            $query->where('responsible_id', $user->id)
                ->orWhereIn('id', PreventiveMaintenanceExecution::query()
                    ->where('assigned_to_id', $user->id)
                    ->whereNotNull('preventive_maintenance_plan_id')
                    ->select('preventive_maintenance_plan_id'));
        });
    }

    private function canViewPlan(User $user, PreventiveMaintenancePlan $plan): bool
    {
        if (! $this->canView($user)) {
            return false;
        }

        if ($this->canSeeAll($user)) {
            return true;
        }

        return $user->hasRole(Role::TECHNICIAN->value)
            && ($plan->responsible_id === $user->id
                || $plan->executions()->where('assigned_to_id', $user->id)->exists());
    }

    /**
     * @return array<string, mixed>
     */
    private function planSummary(PreventiveMaintenancePlan $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'status' => $plan->status->value,
            'status_label' => $plan->status->label(),
            'status_color' => $plan->status->color(),
            'frequency' => [
                'value' => $plan->frequency_type->value,
                'interval' => $plan->interval,
                'label' => $this->describeFrequency($plan),
            ],
            'asset_card' => $plan->assetCard ? [
                'id' => $plan->assetCard->id,
                'code' => $plan->assetCard->code,
            ] : null,
            'category' => $plan->maintenanceCategory ? [
                'id' => $plan->maintenanceCategory->id,
                'name' => $plan->maintenanceCategory->name,
            ] : null,
            'responsible' => $plan->responsible ? [
                'id' => $plan->responsible->id,
                'name' => $plan->responsible->name,
            ] : null,
            'starts_at' => $plan->starts_at->toDateString(),
            'ends_at' => $plan->ends_at?->toDateString(),
        ];
    }

    /**
     * @param  array<int, PreventiveMaintenanceExecution>  $executions
     * @return array<string, mixed>
     */
    private function planDetail(PreventiveMaintenancePlan $plan, User $user, array $executions): array
    {
        return $this->planSummary($plan) + [
            'description' => $plan->description,
            'instructions' => $plan->instructions,
            'created_by' => $plan->createdBy ? [
                'id' => $plan->createdBy->id,
                'name' => $plan->createdBy->name,
            ] : null,
            'allowed_transitions' => $this->transitionOptions($plan, $user),
            'executions' => array_map(
                fn (PreventiveMaintenanceExecution $execution): array => [
                    'id' => $execution->id,
                    'title' => $execution->title,
                    'status' => $execution->status->value,
                    'status_label' => $execution->status->label(),
                    'status_color' => $execution->status->color(),
                    'scheduled_at' => $execution->scheduled_at->toDateString(),
                    'is_overdue' => $execution->isOverdue(),
                    'assignee' => $execution->assignee ? [
                        'id' => $execution->assignee->id,
                        'name' => $execution->assignee->name,
                    ] : null,
                ],
                $executions,
            ),
        ];
    }

    /**
     * Manual state changes the user can make right now. Finished is never
     * offered: only the recurrence service sets it.
     *
     * @return list<array<string, mixed>>
     */
    private function transitionOptions(PreventiveMaintenancePlan $plan, User $user): array
    {
        if (! $this->canManage($user)) {
            return [];
        }

        $transitions = array_filter(
            PreventivePlanStatus::cases(),
            fn (PreventivePlanStatus $status): bool => $status !== PreventivePlanStatus::Finished
                && $plan->status->canTransitionTo($status),
        );

        return array_values(array_map(
            fn (PreventivePlanStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
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
            fn (PreventivePlanStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            PreventivePlanStatus::cases(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function frequencyOptions(): array
    {
        return array_map(
            fn (PreventiveFrequencyType $frequency): array => [
                'value' => $frequency->value,
                'label' => $this->frequencyLabel($frequency),
            ],
            PreventiveFrequencyType::cases(),
        );
    }

    private function frequencyLabel(PreventiveFrequencyType $frequency): string
    {
        return match ($frequency) {
            PreventiveFrequencyType::Once => 'Una sola vez',
            PreventiveFrequencyType::Daily => 'Diaria',
            PreventiveFrequencyType::Weekly => 'Semanal',
            PreventiveFrequencyType::Monthly => 'Mensual',
            PreventiveFrequencyType::Yearly => 'Anual',
        };
    }

    private function describeFrequency(PreventiveMaintenancePlan $plan): string
    {
        if ($plan->frequency_type === PreventiveFrequencyType::Once || $plan->interval === 1) {
            return $this->frequencyLabel($plan->frequency_type);
        }

        $unit = match ($plan->frequency_type) {
            PreventiveFrequencyType::Daily => 'días',
            PreventiveFrequencyType::Weekly => 'semanas',
            PreventiveFrequencyType::Monthly => 'meses',
            PreventiveFrequencyType::Yearly => 'años',
            PreventiveFrequencyType::Once => '',
        };

        return "Cada {$plan->interval} {$unit}";
    }
}
