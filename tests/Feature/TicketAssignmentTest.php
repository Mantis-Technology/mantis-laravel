<?php

use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
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
        'name' => 'Acme Assignments',
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

    $this->chief = User::factory()->create();
    $this->chief->assignRole(Role::MAINTENANCE_CHIEF->value);

    $this->technician = User::factory()->create();
    $this->technician->assignRole(Role::TECHNICIAN->value);

    $this->otherTechnician = User::factory()->create();
    $this->otherTechnician->assignRole(Role::TECHNICIAN->value);

    $this->operator = User::factory()->create();
    $this->operator->assignRole(Role::OPERATOR->value);
});

afterEach(function () {
    tenancy()->end();

    $this->tenant?->delete();

    @unlink(database_path('testing_central.sqlite'));
});

/**
 * Builds a case assigned through the lifecycle, so the assignment history
 * row is opened exactly like it is in production.
 */
function makeAssignedTicket(object $test, string $note = 'Asignación inicial'): Ticket
{
    $ticket = Ticket::factory()->categorized()->create([
        'asset_card_id' => $test->assetCard->id,
        'reported_by' => $test->operator->id,
    ]);

    $ticket->assigned_to = $test->technician->id;
    $ticket->save();

    expect($ticket->transitionTo(TicketStatus::Assigned, $test->chief->id, $note))->toBeNull();

    return $ticket->fresh();
}

test('assigning a case records the responsible, who assigned it and when', function () {
    $ticket = makeAssignedTicket($this);

    $assignment = $ticket->assignments()->get();

    expect($assignment)->toHaveCount(1)
        ->and($assignment[0]->assigned_to)->toBe($this->technician->id)
        ->and($assignment[0]->assigned_by)->toBe($this->chief->id)
        ->and($assignment[0]->assigned_at)->not->toBeNull()
        ->and($assignment[0]->ended_at)->toBeNull()
        ->and($assignment[0]->note)->toBe('Asignación inicial')
        ->and($assignment[0]->isActive())->toBeTrue();
});

test('reassigning a case keeps the previous responsible in the history', function () {
    $ticket = makeAssignedTicket($this);

    $error = $ticket->reassignTo($this->otherTechnician, $this->chief->id, 'Cambio de turno');

    expect($error)->toBeNull();

    $assignments = $ticket->assignments()->get();

    expect($assignments)->toHaveCount(2)
        ->and($assignments[0]->assigned_to)->toBe($this->technician->id)
        ->and($assignments[0]->ended_at)->not->toBeNull()
        ->and($assignments[1]->assigned_to)->toBe($this->otherTechnician->id)
        ->and($assignments[1]->ended_at)->toBeNull()
        ->and($assignments[1]->note)->toBe('Cambio de turno')
        ->and($ticket->fresh()->assigned_to)->toBe($this->otherTechnician->id)
        ->and($ticket->currentAssignment?->assigned_to)->toBe($this->otherTechnician->id);
});

test('a case cannot be reassigned to the technician who already has it', function () {
    $ticket = makeAssignedTicket($this);

    $error = $ticket->reassignTo($this->technician, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($ticket->assignments()->count())->toBe(1)
        ->and($ticket->fresh()->assigned_to)->toBe($this->technician->id);
});

test('a case that is not assigned yet cannot be reassigned', function () {
    $ticket = Ticket::factory()->categorized()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    $error = $ticket->reassignTo($this->technician, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($ticket->assignments()->count())->toBe(0)
        ->and($ticket->fresh()->assigned_to)->toBeNull();
});

test('a resolved case cannot be reassigned', function () {
    $ticket = makeAssignedTicket($this);

    expect($ticket->transitionTo(TicketStatus::InProgress, $this->technician->id))->toBeNull();
    expect($ticket->transitionTo(TicketStatus::Resolved, $this->technician->id))->toBeNull();

    $error = $ticket->reassignTo($this->otherTechnician, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($ticket->assignments()->count())->toBe(1)
        ->and($ticket->fresh()->assigned_to)->toBe($this->technician->id);
});

test('the responsibility continues while the case is in progress', function () {
    $ticket = makeAssignedTicket($this);

    expect($ticket->transitionTo(TicketStatus::InProgress, $this->technician->id))->toBeNull();

    $assignments = $ticket->assignments()->get();

    expect($assignments)->toHaveCount(1)
        ->and($assignments[0]->ended_at)->toBeNull()
        ->and($ticket->currentAssignment?->assigned_to)->toBe($this->technician->id);
});

test('closing a case ends its current assignment', function () {
    $ticket = makeAssignedTicket($this);

    expect($ticket->transitionTo(TicketStatus::InProgress, $this->technician->id))->toBeNull();
    expect($ticket->transitionTo(TicketStatus::Resolved, $this->technician->id))->toBeNull();
    expect($ticket->transitionTo(TicketStatus::Closed, $this->chief->id))->toBeNull();

    $ticket = $ticket->fresh();

    expect($ticket->assignments()->first()->ended_at)->not->toBeNull()
        ->and($ticket->currentAssignment)->toBeNull();
});

test('cancelling a case ends its current assignment', function () {
    $ticket = makeAssignedTicket($this);

    expect($ticket->transitionTo(TicketStatus::Cancelled, $this->chief->id))->toBeNull();

    $ticket = $ticket->fresh();

    expect($ticket->assignments()->first()->ended_at)->not->toBeNull()
        ->and($ticket->currentAssignment)->toBeNull();
});

test('the maintenance chief reassigns a case from the case screen', function () {
    $ticket = makeAssignedTicket($this);

    $this->actingAs($this->chief)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/assignment",
            [
                'assigned_to' => $this->otherTechnician->id,
                'note' => 'Cambio de turno',
            ],
        )
        ->assertRedirectContains("/tickets/{$ticket->id}")
        ->assertSessionHas('success');

    expect($ticket->fresh()->assigned_to)->toBe($this->otherTechnician->id)
        ->and($ticket->assignments()->count())->toBe(2);
});

test('a technician cannot reassign a case', function () {
    $ticket = makeAssignedTicket($this);

    $this->actingAs($this->technician)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/assignment",
            ['assigned_to' => $this->otherTechnician->id],
        )
        ->assertForbidden();

    expect($ticket->fresh()->assigned_to)->toBe($this->technician->id)
        ->and($ticket->assignments()->count())->toBe(1);
});

test('reassigning requires a user with the technician role', function () {
    $ticket = makeAssignedTicket($this);

    $this->actingAs($this->chief)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/assignment",
            ['assigned_to' => $this->operator->id],
        )
        ->assertSessionHasErrors('assigned_to');

    expect($ticket->fresh()->assigned_to)->toBe($this->technician->id)
        ->and($ticket->assignments()->count())->toBe(1);
});

test('the case screen exposes the assignment history', function () {
    $ticket = makeAssignedTicket($this);

    expect($ticket->reassignTo($this->otherTechnician, $this->chief->id))->toBeNull();

    $this->actingAs($this->chief)
        ->get("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tickets/Show/index')
            ->has('ticket.assignments', 2)
            ->where('ticket.assignments.0.is_active', false)
            ->where('ticket.assignments.1.is_active', true)
            ->where('ticket.can_reassign', true));
});

test('a technician can filter the list down to their own cases', function () {
    makeAssignedTicket($this);

    Ticket::factory()->assigned()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->technician->id,
        'assigned_to' => $this->otherTechnician->id,
    ]);

    $base = "http://{$this->tenant->id}.localhost/tickets";

    $this->actingAs($this->technician)
        ->get($base)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('tickets', 2));

    $this->actingAs($this->technician)
        ->get("{$base}?assigned=me")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tickets', 1)
            ->where('filters.assigned', 'me'));
});
