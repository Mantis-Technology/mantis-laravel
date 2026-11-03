<?php

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Enums\TicketClosureResult;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Models\MaintenanceCategory;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    $centralPath = database_path('testing_central.sqlite');

    if (file_exists($centralPath)) {
        unlink($centralPath);
    }

    touch($centralPath);

    config([
        'database.default' => 'testing_central',
        'database.connections.testing_central' => [
            'driver' => 'sqlite',
            'database' => $centralPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'tenancy.database.central_connection' => 'testing_central',
    ]);

    $this->artisan('migrate', [
        '--database' => 'testing_central',
        '--force' => true,
    ])->assertSuccessful();

    $this->tenant = Tenant::create([
        'name' => 'Acme Closure',
        'contact_email' => 'ops@acme.test',
    ]);

    $this->tenant->update(['status' => TenantStatus::Active->value]);

    tenancy()->initialize($this->tenant);

    foreach (Role::cases() as $role) {
        SpatieRole::findOrCreate($role->value, 'web');
    }

    $template = AssetCardTemplate::query()->create([
        'name' => 'Equipo de computo',
        'version' => 1,
    ]);

    CardTemplateVersion::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
    ]);

    $this->assetCard = AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'IDS-EC-200',
    ]);

    $this->category = MaintenanceCategory::query()->create([
        'name' => 'Eléctrica',
    ]);

    $this->chief = User::factory()->create();
    $this->chief->assignRole(Role::MAINTENANCE_CHIEF->value);

    $this->technician = User::factory()->create();
    $this->technician->assignRole(Role::TECHNICIAN->value);

    $this->operator = User::factory()->create();
    $this->operator->assignRole(Role::OPERATOR->value);
});

afterEach(function () {
    tenancy()->end();

    $this->tenant?->delete();

    @unlink(database_path('testing_central.sqlite'));
});

function resolvedTicketForClosure(object $context): Ticket
{
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $context->assetCard->id,
        'reported_by' => $context->operator->id,
    ]);

    $ticket->categorize($context->category, MaintenanceType::Corrective, MaintenancePriority::High, $context->chief->id);

    $ticket->assigned_to = $context->technician->id;
    $ticket->save();

    $ticket->transitionTo(TicketStatus::Assigned, $context->chief->id);
    $ticket->transitionTo(TicketStatus::InProgress, $context->technician->id);
    $ticket->transitionTo(TicketStatus::Resolved, $context->technician->id);

    return $ticket;
}

test('a resolved case is closed together with its documented closure', function () {
    $ticket = resolvedTicketForClosure($this);

    $error = $ticket->close(
        'Se reemplazó la fuente de poder y se probó el equipo.',
        TicketClosureResult::Solved,
        null,
        $this->chief->id,
    );

    $closure = $ticket->fresh()->closure;

    expect($error)->toBeNull()
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Closed)
        ->and($closure)->not->toBeNull()
        ->and($closure->work_performed)->toBe('Se reemplazó la fuente de poder y se probó el equipo.')
        ->and($closure->result)->toBe(TicketClosureResult::Solved)
        ->and($closure->observations)->toBeNull()
        ->and($closure->closed_by)->toBe($this->chief->id)
        ->and($closure->closed_at)->not->toBeNull()
        ->and($ticket->currentAssignment()->exists())->toBeFalse();
});

test('closing adds an event to the history without erasing anything', function () {
    $ticket = resolvedTicketForClosure($this);

    $ticket->close('Se ajustó el cableado del tablero principal.', TicketClosureResult::Solved, null, $this->chief->id);

    $last = $ticket->statusTransitions()->get()->last();

    expect($ticket->statusTransitions()->count())->toBe(6)
        ->and($last->from_status)->toBe(TicketStatus::Resolved)
        ->and($last->to_status)->toBe(TicketStatus::Closed)
        ->and($last->changed_by)->toBe($this->chief->id)
        ->and($ticket->assignments()->count())->toBe(1);
});

test('a case that is not resolved cannot be closed', function () {
    $reported = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $error = $reported->close('Intento de cierre sin haber resuelto el caso.', TicketClosureResult::Solved, null, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($reported->fresh()->status)->toBe(TicketStatus::Reported)
        ->and($reported->closure()->exists())->toBeFalse()
        ->and($reported->statusTransitions()->count())->toBe(1);
});

test('closing requires the work performed', function () {
    $ticket = resolvedTicketForClosure($this);

    $error = $ticket->close('   ', TicketClosureResult::Solved, null, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Resolved)
        ->and($ticket->closure()->exists())->toBeFalse();
});

test('a result with recommendations requires observations', function () {
    $ticket = resolvedTicketForClosure($this);

    $error = $ticket->close(
        'Se cambió el sensor de temperatura del equipo.',
        TicketClosureResult::SolvedWithRecommendations,
        '  ',
        $this->chief->id,
    );

    expect($error)->not->toBeNull()
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Resolved)
        ->and($ticket->closure()->exists())->toBeFalse();

    $error = $ticket->close(
        'Se cambió el sensor de temperatura del equipo.',
        TicketClosureResult::SolvedWithRecommendations,
        '  Revisar la ventilación en la próxima visita.  ',
        $this->chief->id,
    );

    expect($error)->toBeNull()
        ->and($ticket->fresh()->closure->observations)->toBe('Revisar la ventilación en la próxima visita.');
});

test('a closed case cannot be closed twice', function () {
    $ticket = resolvedTicketForClosure($this);

    $ticket->close('Se reemplazó la fuente de poder del equipo.', TicketClosureResult::Solved, null, $this->chief->id);

    $error = $ticket->fresh()->close('Segundo intento de cierre del caso.', TicketClosureResult::Solved, null, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($ticket->closure()->count())->toBe(1)
        ->and($ticket->statusTransitions()->count())->toBe(6);
});

test('the maintenance chief closes a resolved case from the case screen', function () {
    $ticket = resolvedTicketForClosure($this);

    $this->actingAs($this->chief)
        ->post("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/closure", [
            'work_performed' => 'Se reemplazó la fuente de poder y se probó el equipo.',
            'result' => TicketClosureResult::Solved->value,
            'no_pending_actions' => true,
        ])
        ->assertRedirectContains("/tickets/{$ticket->id}")
        ->assertSessionHas('success');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed)
        ->and($ticket->fresh()->closure->closed_by)->toBe($this->chief->id);
});

test('a technician cannot close a case', function () {
    $ticket = resolvedTicketForClosure($this);

    $this->actingAs($this->technician)
        ->post("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/closure", [
            'work_performed' => 'Intento de cierre por parte del técnico.',
            'result' => TicketClosureResult::Solved->value,
            'no_pending_actions' => true,
        ])
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

test('closing requires the documentation and the pending actions confirmation', function () {
    $ticket = resolvedTicketForClosure($this);

    $url = "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/closure";

    $this->actingAs($this->chief)
        ->post($url, [])
        ->assertSessionHasErrors(['work_performed', 'result', 'no_pending_actions']);

    $this->actingAs($this->chief)
        ->post($url, [
            'work_performed' => 'Se cambió el sensor de temperatura del equipo.',
            'result' => TicketClosureResult::SolvedWithRecommendations->value,
            'no_pending_actions' => true,
        ])
        ->assertSessionHasErrors('observations');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved)
        ->and($ticket->closure()->exists())->toBeFalse();
});

test('the generic status route cannot close a case', function () {
    $ticket = resolvedTicketForClosure($this);

    $this->actingAs($this->chief)
        ->patch("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status", [
            'status' => TicketStatus::Closed->value,
        ])
        ->assertSessionHas('error');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved)
        ->and($ticket->closure()->exists())->toBeFalse();
});

test('the case screen exposes the closure form and then the documented closure', function () {
    $ticket = resolvedTicketForClosure($this);

    $url = "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}";

    $this->actingAs($this->chief)
        ->get($url)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Tickets/Show/Index')
            ->where('ticket.can_close', true)
            ->where('ticket.closure', null)
            ->has('closure_results', 2)
            ->where('ticket.allowed_transitions', []));

    $this->actingAs($this->technician)
        ->get($url)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ticket.can_close', false));

    $ticket->close('Se reemplazó la fuente de poder del equipo.', TicketClosureResult::Solved, null, $this->chief->id);

    $this->actingAs($this->chief)
        ->get($url)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ticket.can_close', false)
            ->where('ticket.closure.result', 'solved')
            ->where('ticket.closure.result_label', 'Solucionado')
            ->where('ticket.closure.closed_by.name', $this->chief->name));
});
