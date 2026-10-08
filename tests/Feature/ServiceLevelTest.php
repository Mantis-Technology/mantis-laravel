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
use App\Models\ServiceLevel;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ResolveTicketServiceLevel;
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
        'name' => 'Acme Service Levels',
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
        'code' => 'IDS-EC-300',
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

/**
 * Creates a classified case ready to be evaluated against a service level.
 */
function classifiedTicket(MaintenanceCategory $category, User $operator, array $attributes = []): Ticket
{
    $ticket = Ticket::factory()->create([
        'asset_card_id' => test()->assetCard->id,
        'reported_by' => $operator->id,
        ...$attributes,
    ]);

    $ticket->categorize(
        $category,
        MaintenanceType::Corrective,
        MaintenancePriority::High,
        $operator->id,
    );

    return $ticket;
}

test('the resolver picks the most specific active rule', function () {
    $generic = ServiceLevel::query()->create([
        'response_hours' => 8,
        'resolution_hours' => 48,
    ]);

    $byType = ServiceLevel::query()->create([
        'maintenance_type' => MaintenanceType::Corrective,
        'response_hours' => 4,
        'resolution_hours' => 24,
    ]);

    $specific = ServiceLevel::query()->create([
        'maintenance_category_id' => $this->category->id,
        'maintenance_type' => MaintenanceType::Corrective,
        'priority' => MaintenancePriority::High,
        'response_hours' => 2,
        'resolution_hours' => 8,
    ]);

    $ticket = classifiedTicket($this->category, $this->operator);

    $resolved = app(ResolveTicketServiceLevel::class)->for($ticket);

    expect($resolved?->id)->toBe($specific->id);

    $specific->update(['is_active' => false]);

    $resolved = app(ResolveTicketServiceLevel::class)->for($ticket->refresh());

    expect($resolved?->id)->toBe($byType->id);

    $byType->update(['is_active' => false]);

    $resolved = app(ResolveTicketServiceLevel::class)->for($ticket->refresh());

    expect($resolved?->id)->toBe($generic->id);
});

test('a rule that does not match the classification is ignored', function () {
    ServiceLevel::query()->create([
        'maintenance_type' => MaintenanceType::Preventive,
        'response_hours' => 4,
        'resolution_hours' => 24,
    ]);

    $ticket = classifiedTicket($this->category, $this->operator);

    expect(app(ResolveTicketServiceLevel::class)->for($ticket))->toBeNull();
});

test('inactive rules are excluded from the default scope', function () {
    $active = ServiceLevel::query()->create([
        'response_hours' => 4,
        'resolution_hours' => 24,
    ]);

    $inactive = ServiceLevel::query()->create([
        'response_hours' => 8,
        'resolution_hours' => 48,
        'is_active' => false,
    ]);

    expect(ServiceLevel::query()->pluck('id')->all())->toBe([$active->id])
        ->and(ServiceLevel::query()->withInactive()->pluck('id')->all())
        ->toBe([$active->id, $inactive->id]);
});

test('the case screen computes the response and resolution targets', function () {
    ServiceLevel::query()->create([
        'maintenance_category_id' => $this->category->id,
        'maintenance_type' => MaintenanceType::Corrective,
        'priority' => MaintenancePriority::High,
        'response_hours' => 4,
        'resolution_hours' => 24,
    ]);

    $ticket = classifiedTicket($this->category, $this->operator, [
        'created_at' => now()->subHours(2),
        'assigned_to' => $this->technician->id,
    ]);

    $ticket->refresh();
    $ticket->transitionTo(TicketStatus::Assigned, $this->chief->id);
    $ticket->transitionTo(TicketStatus::InProgress, $this->technician->id);

    $this->actingAs($this->chief)
        ->get("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tickets/Show/index')
            ->where('ticket.sla.has_service_level', true)
            ->where('ticket.sla.response.target_hours', 4)
            ->where('ticket.sla.response.state', 'met')
            ->where('ticket.sla.resolution.target_hours', 24)
            ->where('ticket.sla.resolution.state', 'on_track')
        );
});

test('the case screen reports an overdue target when the window has passed', function () {
    ServiceLevel::query()->create([
        'maintenance_category_id' => $this->category->id,
        'maintenance_type' => MaintenanceType::Corrective,
        'priority' => MaintenancePriority::High,
        'response_hours' => 4,
        'resolution_hours' => 24,
    ]);

    $ticket = classifiedTicket($this->category, $this->operator, [
        'created_at' => now()->subHours(10),
    ]);

    $this->actingAs($this->chief)
        ->get("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ticket.sla.has_service_level', true)
            ->where('ticket.sla.response.state', 'overdue')
            ->where('ticket.sla.resolution.state', 'on_track')
        );
});

test('a case without a matching service level has no sla', function () {
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $this->actingAs($this->chief)
        ->get("http://{$this->tenant->id}.localhost/tickets/{$ticket->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ticket.sla.has_service_level', false)
            ->where('ticket.sla.response', null)
            ->where('ticket.sla.resolution', null)
        );
});

test('a maintenance chief manages the service levels', function () {
    $base = "http://{$this->tenant->id}.localhost/parameterization/service-levels";

    $this->actingAs($this->chief)
        ->get($base)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Parameterization/ServiceLevels/index')
            ->has('service_levels', 0)
            ->where('can_toggle_active', true)
        );

    $this->actingAs($this->chief)
        ->get("{$base}/create")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Parameterization/ServiceLevels/Create/index')
        );

    $this->actingAs($this->chief)
        ->post($base, [
            'maintenance_category_id' => $this->category->id,
            'maintenance_type' => MaintenanceType::Corrective->value,
            'priority' => MaintenancePriority::High->value,
            'response_hours' => 4,
            'resolution_hours' => 24,
            'is_active' => '1',
        ])
        ->assertRedirectContains('/parameterization/service-levels');

    $level = ServiceLevel::query()->firstOrFail();

    expect($level->maintenance_category_id)->toBe($this->category->id)
        ->and($level->response_hours)->toBe(4)
        ->and($level->resolution_hours)->toBe(24)
        ->and($level->is_active)->toBeTrue();

    $this->actingAs($this->chief)
        ->put("{$base}/{$level->id}", [
            'maintenance_category_id' => $this->category->id,
            'maintenance_type' => MaintenanceType::Corrective->value,
            'priority' => MaintenancePriority::Critical->value,
            'response_hours' => 2,
            'resolution_hours' => 8,
            'is_active' => '1',
        ])
        ->assertRedirectContains('/parameterization/service-levels');

    $level->refresh();

    expect($level->priority)->toBe(MaintenancePriority::Critical)
        ->and($level->response_hours)->toBe(2);

    $this->actingAs($this->chief)
        ->patch("{$base}/{$level->id}/active")
        ->assertSessionHas('success');

    expect($level->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->chief)
        ->delete("{$base}/{$level->id}")
        ->assertSessionHas('success');

    expect(ServiceLevel::query()->withInactive()->count())->toBe(0);
});

test('service levels require valid attention times', function () {
    $this->actingAs($this->chief)
        ->post(
            "http://{$this->tenant->id}.localhost/parameterization/service-levels",
            [
                'response_hours' => 0,
                'resolution_hours' => 99999,
            ],
        )
        ->assertSessionHasErrors(['response_hours', 'resolution_hours']);

    expect(ServiceLevel::query()->count())->toBe(0);
});

test('technicians and operators cannot manage service levels', function () {
    $url = "http://{$this->tenant->id}.localhost/parameterization/service-levels";

    $this->actingAs($this->technician)->get($url)->assertForbidden();
    $this->actingAs($this->operator)->get($url)->assertForbidden();

    $this->actingAs($this->technician)
        ->post($url, [
            'response_hours' => 4,
            'resolution_hours' => 24,
        ])
        ->assertForbidden();
});
