<?php

declare(strict_types=1);

namespace App\Http\Controllers\tickets;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\AssetCard;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\TicketStatusTransition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * Lists the maintenance cases visible to the authenticated user. The
     * lifecycle state can be filtered through the `status` query parameter.
     */
    public function index(Request $request): Response
    {
        $user = $this->authenticatedUser($request);

        $status = $this->statusFilter($request);

        $tickets = $this->visibleTickets($user)
            ->with(['assetCard:id,code', 'reporter:id,name', 'assignee:id,name'])
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where('status', $status)
            )
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Tickets/Index/index', [
            'tickets' => $tickets
                ->map(fn (Ticket $ticket): array => $this->ticketSummary($ticket))
                ->values()
                ->all(),
            'filters' => [
                'status' => $status?->value,
            ],
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Shows the incident report form. When the request comes from an asset's
     * QR code (?asset=CODE), the asset is resolved automatically and locked
     * in the form; otherwise the operator picks it from the list.
     */
    public function create(Request $request): Response
    {
        $assetCard = $this->assetCardFromRequest($request);

        return Inertia::render('Tickets/Create/index', [
            'assetCard' => $assetCard
                ? [
                    'id' => $assetCard->id,
                    'code' => $assetCard->code,
                ]
                : null,
            'assetCards' => $assetCard ? [] : $this->assetCards(),
            'locations' => $this->locations(),
            'action' => route('tickets.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asset_card_id' => ['required', 'integer', 'exists:asset_cards,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        Ticket::query()->create([
            'asset_card_id' => (int) $validated['asset_card_id'],
            'reported_by' => $request->user()->id,
            'location_id' => $validated['location_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Incidencia reportada correctamente.');
    }

    /**
     * Case detail: summary, lifecycle history and the transitions the
     * authenticated user is allowed to perform.
     */
    public function show(Request $request, Ticket $ticket): Response
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canViewTicket($user, $ticket), 403);

        $ticket->load([
            'assetCard:id,code',
            'location:id,name',
            'reporter:id,name',
            'assignee:id,name',
            'statusTransitions.changedBy:id,name',
        ]);

        return Inertia::render('Tickets/Show/index', [
            'ticket' => $this->ticketDetail($ticket, $user),
            'technicians' => $this->technicians(),
        ]);
    }

    /**
     * Applies a lifecycle transition to the case. When moving to `assigned`,
     * the responsible technician must be provided, so a case can never
     * advance without an owner.
     */
    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        abort_unless($this->canViewTicket($user, $ticket), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'assigned_to' => [
                Rule::requiredIf(
                    fn (): bool => $request->input('status') === TicketStatus::Assigned->value
                ),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $status = TicketStatus::from($validated['status']);

        abort_unless($this->canPerformTransition($user, $ticket, $status), 403);

        if (! $ticket->status->canTransitionTo($status)) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', "No se puede pasar de '{$ticket->status->label()}' a '{$status->label()}'.");
        }

        if ($status === TicketStatus::Assigned) {
            $ticket->assigned_to = $this->technicianId((int) $validated['assigned_to']);
        }

        $error = $ticket->transitionTo(
            $status,
            $user->id,
            $validated['note'] ?? null,
        );

        if ($error !== null) {
            return redirect()
                ->route('tickets.show', $ticket)
                ->with('error', $error);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "El caso pasó a '{$status->label()}'.");
    }

    /**
     * Resolves the asset card from the ?asset= query parameter (the asset
     * code embedded in the QR code). Aborts with 404 when the code does not
     * match any asset.
     */
    private function assetCardFromRequest(Request $request): ?AssetCard
    {
        $code = $request->query('asset');

        if (! is_string($code) || $code === '') {
            return null;
        }

        $assetCard = AssetCard::query()->where('code', $code)->first();

        abort_unless($assetCard instanceof AssetCard, 404);

        return $assetCard;
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function statusFilter(Request $request): ?TicketStatus
    {
        $status = $request->query('status');

        return is_string($status) ? TicketStatus::tryFrom($status) : null;
    }

    /**
     * Cases a user can see: administrators and the maintenance chief manage
     * every case; technicians see the cases assigned to them (plus the ones
     * they reported); everyone else only sees their own reports.
     *
     * @return Builder<Ticket>
     */
    private function visibleTickets(User $user): Builder
    {
        if ($this->canManageAllCases($user)) {
            return Ticket::query();
        }

        if ($user->hasRole(Role::TECHNICIAN->value)) {
            return Ticket::query()->where(function (Builder $query) use ($user): void {
                $query->where('assigned_to', $user->id)
                    ->orWhere('reported_by', $user->id);
            });
        }

        return Ticket::query()->where('reported_by', $user->id);
    }

    private function canViewTicket(User $user, Ticket $ticket): bool
    {
        if ($this->canManageAllCases($user)) {
            return true;
        }

        if ($user->hasRole(Role::TECHNICIAN->value)) {
            return $ticket->assigned_to === $user->id
                || $ticket->reported_by === $user->id;
        }

        return $ticket->reported_by === $user->id;
    }

    /**
     * Who can move a case: administrators and the maintenance chief manage
     * any valid transition; the assigned technician can only start and
     * resolve their own case.
     */
    private function canPerformTransition(User $user, Ticket $ticket, TicketStatus $status): bool
    {
        if ($this->canManageAllCases($user)) {
            return true;
        }

        if (! $user->hasRole(Role::TECHNICIAN->value) || $ticket->assigned_to !== $user->id) {
            return false;
        }

        return in_array($status, [TicketStatus::InProgress, TicketStatus::Resolved], true);
    }

    private function canManageAllCases(User $user): bool
    {
        return $user->hasAnyRole([
            Role::TENANT_ADMINISTRATOR->value,
            Role::MAINTENANCE_CHIEF->value,
        ]);
    }

    private function technicianId(int $userId): int
    {
        $technician = User::query()
            ->role(Role::TECHNICIAN->value)
            ->find($userId);

        if (! $technician instanceof User) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Selecciona un usuario con el rol de técnico.',
            ]);
        }

        return $technician->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function ticketSummary(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'title' => $ticket->title,
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label(),
            'status_color' => $ticket->status->color(),
            'asset_card' => $ticket->assetCard ? [
                'id' => $ticket->assetCard->id,
                'code' => $ticket->assetCard->code,
            ] : null,
            'reporter' => $ticket->reporter ? [
                'id' => $ticket->reporter->id,
                'name' => $ticket->reporter->name,
            ] : null,
            'assignee' => $ticket->assignee ? [
                'id' => $ticket->assignee->id,
                'name' => $ticket->assignee->name,
            ] : null,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ticketDetail(Ticket $ticket, User $user): array
    {
        return $this->ticketSummary($ticket) + [
            'description' => $ticket->description,
            'location' => $ticket->location ? [
                'id' => $ticket->location->id,
                'name' => $ticket->location->name,
            ] : null,
            'updated_at' => $ticket->updated_at?->toIso8601String(),
            'is_final' => $ticket->status->isFinal(),
            'status_transitions' => $ticket->statusTransitions
                ->map(fn (TicketStatusTransition $transition): array => [
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
            'allowed_transitions' => $this->transitionOptions($ticket, $user),
        ];
    }

    /**
     * Transitions the authenticated user can perform right now, ready for
     * the case screen actions.
     *
     * @return list<array<string, mixed>>
     */
    private function transitionOptions(Ticket $ticket, User $user): array
    {
        $transitions = array_filter(
            $ticket->status->allowedTransitions(),
            fn (TicketStatus $status): bool => $this->canPerformTransition($user, $ticket, $status),
        );

        return array_values(array_map(
            fn (TicketStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'action_label' => $status->actionLabel(),
                'color' => $status->color(),
                'requires_assignee' => $status === TicketStatus::Assigned,
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
            fn (TicketStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            TicketStatus::cases(),
        );
    }

    /**
     * Users with the technician role, offered as responsibles when a case is
     * assigned.
     *
     * @return array<int, array<string, mixed>>
     */
    private function technicians(): array
    {
        return User::query()
            ->role(Role::TECHNICIAN->value)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assetCards(): array
    {
        return AssetCard::query()
            ->orderBy('code')
            ->get(['id', 'code'])
            ->map(fn (AssetCard $assetCard): array => [
                'id' => $assetCard->id,
                'code' => $assetCard->code,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function locations(): array
    {
        $locations = Location::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $childrenMap = $locations->groupBy(
            fn (Location $location) => $location->parent_id ?? 0
        );

        $build = function (array $parents) use (&$build, $childrenMap): array {
            $nodes = [];

            foreach ($parents as $location) {
                $nodes[] = [
                    'id' => $location->id,
                    'name' => $location->name,
                    'children' => $build(
                        $childrenMap->get($location->id, new Collection)->all()
                    ),
                ];
            }

            return $nodes;
        };

        return $build($childrenMap->get(0, new Collection)->all());
    }
}
