<?php

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Models\MaintenanceCategory;
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
        'name' => 'Acme Classification',
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

    $this->inactiveCategory = MaintenanceCategory::query()->create([
        'name' => 'Obsoleta',
        'is_active' => false,
    ]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::TENANT_ADMINISTRATOR->value);

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

test('a maintenance chief classifies a reported case', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $response = $this->actingAs($this->chief)->patch(
        "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
        [
            'status' => TicketStatus::Categorized->value,
            'maintenance_category_id' => $this->category->id,
            'maintenance_type' => MaintenanceType::Corrective->value,
            'priority' => MaintenancePriority::High->value,
            'note' => 'Falla eléctrica',
        ],
    );

    $response->assertRedirectContains("/tickets/{$ticket->id}")
        ->assertSessionHas('success');

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Categorized)
        ->and($ticket->maintenance_category_id)->toBe($this->category->id)
        ->and($ticket->maintenance_type)->toBe(MaintenanceType::Corrective)
        ->and($ticket->priority)->toBe(MaintenancePriority::High)
        ->and($ticket->categorized_by)->toBe($this->chief->id)
        ->and($ticket->categorized_at)->not->toBeNull()
        ->and($ticket->isClassified())->toBeTrue();

    $transition = $ticket->statusTransitions()
        ->where('to_status', TicketStatus::Categorized->value)
        ->firstOrFail();

    expect($transition->changed_by)->toBe($this->chief->id)
        ->and($transition->note)->toBe('Falla eléctrica');
});

test('classification requires a category, a type and a priority', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $this->actingAs($this->chief)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
            ['status' => TicketStatus::Categorized->value],
        )
        ->assertSessionHasErrors([
            'maintenance_category_id',
            'maintenance_type',
            'priority',
        ]);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Reported)
        ->and($ticket->fresh()->isClassified())->toBeFalse();
});

test('an inactive category cannot be used to classify a case', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $this->actingAs($this->chief)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
            [
                'status' => TicketStatus::Categorized->value,
                'maintenance_category_id' => $this->inactiveCategory->id,
                'maintenance_type' => MaintenanceType::Corrective->value,
                'priority' => MaintenancePriority::High->value,
            ],
        )
        ->assertSessionHasErrors('maintenance_category_id');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Reported);
});

test('only management roles can classify a case', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $payload = [
        'status' => TicketStatus::Categorized->value,
        'maintenance_category_id' => $this->category->id,
        'maintenance_type' => MaintenanceType::Corrective->value,
        'priority' => MaintenancePriority::High->value,
    ];

    $this->actingAs($this->operator)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
            $payload,
        )
        ->assertForbidden();

    $this->actingAs($this->technician)
        ->patch(
            "http://{$this->tenant->id}.localhost/tickets/{$ticket->id}/status",
            $payload,
        )
        ->assertForbidden();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Reported);
});

test('a case cannot advance to categorized without a classification', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    expect($ticket->canTransitionTo(TicketStatus::Categorized))->toBeFalse();

    $error = $ticket->transitionTo(TicketStatus::Categorized, $this->chief->id);

    expect($error)->not->toBeNull()
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Reported)
        ->and($ticket->statusTransitions()->count())->toBe(1);
});

test('the case screen exposes the classification options and data', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $ticket->categorize(
        $this->category,
        MaintenanceType::Corrective,
        MaintenancePriority::High,
        $this->chief->id,
    );

    $this->actingAs($this->chief)
        ->get("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tickets/Show/index')
            ->where('ticket.category.id', $this->category->id)
            ->where('ticket.category.name', 'Eléctrica')
            ->where('ticket.maintenance_type.value', MaintenanceType::Corrective->value)
            ->where('ticket.priority.value', MaintenancePriority::High->value)
            ->where('ticket.categorized_by.id', $this->chief->id)
            ->has('maintenance_categories')
            ->has('maintenance_types', 2)
            ->has('priorities', 4)
        );
});
