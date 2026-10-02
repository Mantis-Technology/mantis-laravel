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
        'name' => 'Acme Lifecycle',
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
        'code' => 'IDS-EC-100',
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

test('a case follows the whole lifecycle one stage at a time', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    expect($ticket->transitionTo(TicketStatus::Categorized, $this->chief->id))->toBeNull();
    expect($ticket->fresh()->status)->toBe(TicketStatus::Categorized);

    $ticket->assigned_to = $this->technician->id;
    $ticket->save();

    expect($ticket->transitionTo(TicketStatus::Assigned, $this->chief->id))->toBeNull();
    expect($ticket->transitionTo(TicketStatus::InProgress, $this->technician->id))->toBeNull();
    expect($ticket->transitionTo(TicketStatus::Resolved, $this->technician->id))->toBeNull();
    expect($ticket->transitionTo(TicketStatus::Closed, $this->chief->id))->toBeNull();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed)
        ->and($ticket->statusTransitions()->count())->toBe(6);
});

test('a case cannot jump from reported to closed', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    $error = $ticket->transitionTo(TicketStatus::Closed, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Reported)
        ->and($ticket->statusTransitions()->count())->toBe(1);
});

test('a case cannot go backwards nor skip the assignment', function () {
    $ticket = Ticket::factory()->categorized()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    expect($ticket->transitionTo(TicketStatus::Reported, $this->chief->id))->not->toBeNull();
    expect($ticket->transitionTo(TicketStatus::InProgress, $this->technician->id))->not->toBeNull();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Categorized);
});

test('a case cannot be assigned or attended without a responsible', function () {
    $ticket = Ticket::factory()->categorized()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    expect($ticket->canTransitionTo(TicketStatus::Assigned))->toBeFalse();

    $error = $ticket->transitionTo(TicketStatus::Assigned, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Categorized)
        ->and($ticket->fresh()->assigned_to)->toBeNull();

    $ticket->assigned_to = $this->technician->id;
    $ticket->save();

    expect($ticket->canTransitionTo(TicketStatus::Assigned))->toBeTrue();
    expect($ticket->transitionTo(TicketStatus::Assigned, $this->chief->id))->toBeNull();
});

test('closed and cancelled cases are final', function () {
    foreach ([TicketStatus::Closed, TicketStatus::Cancelled] as $finalStatus) {
        $ticket = Ticket::factory()->create([
            'asset_card_id' => $this->assetCard->id,
            'status' => $finalStatus,
            'assigned_to' => $this->technician->id,
        ]);

        expect($ticket->status->isFinal())->toBeTrue()
            ->and($ticket->transitionTo(TicketStatus::InProgress, $this->technician->id))->not->toBeNull()
            ->and($ticket->fresh()->status)->toBe($finalStatus);
    }
});

test('the lifecycle keeps an audit trail of every status change', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $ticket->transitionTo(TicketStatus::Categorized, $this->chief->id, 'Falla electrica');

    $transitions = $ticket->statusTransitions()->with('changedBy')->get();

    expect($transitions)->toHaveCount(2)
        ->and($transitions[0]->from_status)->toBeNull()
        ->and($transitions[0]->to_status)->toBe(TicketStatus::Reported)
        ->and($transitions[0]->changed_by)->toBe($this->operator->id)
        ->and($transitions[1]->from_status)->toBe(TicketStatus::Reported)
        ->and($transitions[1]->to_status)->toBe(TicketStatus::Categorized)
        ->and($transitions[1]->changedBy?->id)->toBe($this->chief->id)
        ->and($transitions[1]->note)->toBe('Falla electrica');
});

test('the maintenance chief can assign a categorized case to a technician', function () {
    $ticket = Ticket::factory()->categorized()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $response = $this->actingAs($this->chief)->patch(
        "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
        [
            'status' => TicketStatus::Assigned->value,
            'assigned_to' => $this->technician->id,
        ],
    );

    $response->assertRedirectContains("/tickets/{$ticket->id}")
        ->assertSessionHas('success');

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Assigned)
        ->and($ticket->assigned_to)->toBe($this->technician->id)
        ->and($ticket->statusTransitions()
            ->where('to_status', TicketStatus::Assigned->value)
            ->firstOrFail()
            ->changed_by)->toBe($this->chief->id);
});

test('assigning a case requires a user with the technician role', function () {
    $ticket = Ticket::factory()->categorized()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    $url = "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status";

    $this->actingAs($this->chief)
        ->patch($url, ['status' => TicketStatus::Assigned->value])
        ->assertSessionHasErrors('assigned_to');

    $this->actingAs($this->chief)
        ->patch($url, [
            'status' => TicketStatus::Assigned->value,
            'assigned_to' => $this->operator->id,
        ])
        ->assertSessionHasErrors('assigned_to');

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Categorized)
        ->and($ticket->assigned_to)->toBeNull();
});

test('an invalid lifecycle jump is rejected and leaves the case untouched', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    $this->actingAs($this->chief)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
            ['status' => TicketStatus::Closed->value],
        )
        ->assertRedirectContains("/tickets/{$ticket->id}")
        ->assertSessionHas('error');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Reported)
        ->and($ticket->statusTransitions()->count())->toBe(1);
});

test('the assigned technician can start and resolve the case', function () {
    $ticket = Ticket::factory()->assigned()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
        'assigned_to' => $this->technician->id,
    ]);

    $url = "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status";

    $this->actingAs($this->technician)
        ->patch($url, ['status' => TicketStatus::InProgress->value])
        ->assertSessionHas('success');

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);

    $this->actingAs($this->technician)
        ->patch($url, ['status' => TicketStatus::Resolved->value])
        ->assertSessionHas('success');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

test('a technician cannot manage a case that is not assigned to them', function () {
    $ticket = Ticket::factory()->assigned()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $this->actingAs($this->technician)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
            ['status' => TicketStatus::InProgress->value],
        )
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Assigned);
});

test('operators cannot change the lifecycle of a case', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $this->actingAs($this->operator)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
            ['status' => TicketStatus::Categorized->value],
        )
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Reported);
});

test('guests cannot access the case management screens', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    $this->get("http://{$this->tenant->id}.localhost/tickets")
        ->assertRedirectContains('/login');

    $this->patch(
        "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
        ['status' => TicketStatus::Categorized->value],
    )->assertRedirectContains('/login');
});

test('the case screen shows the status history and the allowed actions', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $ticket->transitionTo(TicketStatus::Categorized, $this->chief->id);
    $ticket->assigned_to = $this->technician->id;
    $ticket->save();
    $ticket->transitionTo(TicketStatus::Assigned, $this->chief->id);

    $this->actingAs($this->technician)
        ->get("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tickets/Show/index')
            ->where('ticket.status', TicketStatus::Assigned->value)
            ->where('ticket.status_label', 'Asignado')
            ->where('ticket.assignee.id', $this->technician->id)
            ->has('ticket.status_transitions', 3)
            ->where('ticket.status_transitions.0.to_status', TicketStatus::Reported->value)
            ->where('ticket.status_transitions.2.to_status', TicketStatus::Assigned->value)
            ->where('ticket.allowed_transitions.0.value', TicketStatus::InProgress->value)
            ->has('technicians', 1)
        );
});

test('the case list only shows the cases each user may see', function () {
    $reported = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $assignedToTechnician = Ticket::factory()->assigned()->create([
        'asset_card_id' => $this->assetCard->id,
        'assigned_to' => $this->technician->id,
    ]);

    Ticket::factory()->assigned()->create([
        'asset_card_id' => $this->assetCard->id,
    ]);

    $base = "http://{$this->tenant->id}.localhost/tickets";

    $this->actingAs($this->chief)
        ->get($base)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tickets/Index/index')
            ->has('tickets', 3)
        );

    $this->actingAs($this->technician)
        ->get($base)
        ->assertInertia(fn (Assert $page) => $page
            ->has('tickets', 1)
            ->where('tickets.0.id', $assignedToTechnician->id)
        );

    $this->actingAs($this->operator)
        ->get($base)
        ->assertInertia(fn (Assert $page) => $page
            ->has('tickets', 1)
            ->where('tickets.0.id', $reported->id)
        );

    $this->actingAs($this->chief)
        ->get("{$base}?status={$assignedToTechnician->status->value}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('tickets', 2)
        );
});
