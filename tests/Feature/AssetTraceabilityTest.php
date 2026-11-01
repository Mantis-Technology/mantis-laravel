<?php

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Enums\PreventiveExecutionStatus;
use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Enums\TicketClosureResult;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Models\MaintenanceCategory;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
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
        'name' => 'Acme Traceability',
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
        'code' => 'IDS-TRZ-100',
    ]);

    $this->otherAssetCard = AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'IDS-TRZ-200',
    ]);

    $this->category = MaintenanceCategory::query()->create([
        'name' => 'Eléctrica',
    ]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::TENANT_ADMINISTRATOR->value);

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
    Carbon::setTestNow();

    tenancy()->end();

    $this->tenant?->delete();

    @unlink(database_path('testing_central.sqlite'));
});

function traceabilityUrl(AssetCard $assetCard, string $query = ''): string
{
    return 'http://'.test()->tenant->id.'.localhost/asset-cards/'.$assetCard->id.'/traceability'.$query;
}

function atTraceabilityTime(string $time): void
{
    Carbon::setTestNow(Carbon::parse('2026-02-01 '.$time));
}

test('the traceability shows who did what and when across the whole ticket lifecycle', function () {
    atTraceabilityTime('08:00:00');
    $ticket = Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    atTraceabilityTime('08:10:00');
    $ticket->categorize($this->category, MaintenanceType::Corrective, MaintenancePriority::High, $this->chief->id);

    atTraceabilityTime('08:20:00');
    $ticket->assigned_to = $this->technician->id;
    $ticket->save();
    $ticket->transitionTo(TicketStatus::Assigned, $this->chief->id, 'Prioridad alta');

    atTraceabilityTime('08:30:00');
    $ticket->transitionTo(TicketStatus::InProgress, $this->technician->id);

    atTraceabilityTime('08:40:00');
    $ticket->transitionTo(TicketStatus::Resolved, $this->technician->id);

    atTraceabilityTime('08:50:00');
    $ticket->close('Se reemplazó la fuente de poder del equipo.', TicketClosureResult::Solved, null, $this->chief->id);

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AssetCards/Traceability/Index')
            ->has('events', 6)
            ->where('events.0.action', 'Cerrado')
            ->where('events.0.detail', 'Solucionado')
            ->where('events.0.user.name', $this->chief->name)
            ->where('events.1.action', 'Resuelto')
            ->where('events.1.user.name', $this->technician->name)
            ->where('events.2.action', 'Atención iniciada')
            ->where('events.3.action', 'Asignado')
            ->where('events.3.detail', 'Responsable: '.$this->technician->name)
            ->where('events.3.note', 'Prioridad alta')
            ->where('events.4.action', 'Clasificado')
            ->where('events.4.detail', 'Categoría: Eléctrica')
            ->where('events.5.action', 'Reportado')
            ->where('events.5.user.name', $this->operator->name)
            ->where('events.5.source', 'ticket'));
});

test('a reassignment appears as its own event with who did it and the new responsible', function () {
    atTraceabilityTime('08:00:00');
    $ticket = Ticket::factory()->categorized()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    atTraceabilityTime('08:10:00');
    $ticket->assigned_to = $this->technician->id;
    $ticket->save();
    $ticket->transitionTo(TicketStatus::Assigned, $this->chief->id);

    atTraceabilityTime('08:20:00');
    expect($ticket->reassignTo($this->otherTechnician, $this->chief->id, 'Rotación de turno'))->toBeNull();

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard))
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.0.action', 'Reasignado')
            ->where('events.0.detail', 'Nuevo responsable: '.$this->otherTechnician->name)
            ->where('events.0.note', 'Rotación de turno')
            ->where('events.0.user.name', $this->chief->name)
            ->where('events.1.action', 'Asignado')
            ->where('events.1.detail', 'Responsable: '.$this->technician->name));
});

test('preventive executions leave postponements, reassignments and completion in the timeline', function () {
    atTraceabilityTime('08:00:00');
    $execution = PreventiveMaintenanceExecution::query()->create([
        'asset_card_id' => $this->assetCard->id,
        'maintenance_category_id' => $this->category->id,
        'title' => 'Revisión trimestral',
        'scheduled_at' => '2026-03-01',
        'assigned_to_id' => $this->technician->id,
    ]);

    atTraceabilityTime('08:30:00');
    expect($execution->postpone('2026-03-10', $this->chief->id, 'Falta de repuestos'))->toBeNull();

    atTraceabilityTime('08:40:00');
    expect($execution->reassignTo($this->otherTechnician, $this->chief->id, 'Cobertura'))->toBeNull();

    atTraceabilityTime('08:50:00');
    $execution->transitionTo(PreventiveExecutionStatus::InProgress, $this->otherTechnician->id);

    atTraceabilityTime('09:00:00');
    $execution->execution_notes = 'Se limpiaron los filtros.';
    $execution->save();
    $execution->transitionTo(PreventiveExecutionStatus::Completed, $this->otherTechnician->id);

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 5)
            ->where('events.0.action', 'Completada')
            ->where('events.0.user.name', $this->otherTechnician->name)
            ->where('events.1.action', 'Iniciada')
            ->where('events.2.action', 'Reasignada')
            ->where('events.2.user.name', $this->chief->name)
            ->where('events.3.action', 'Aplazada')
            ->where('events.3.user.name', $this->chief->name)
            ->where('events.4.action', 'Programada')
            ->where('events.4.user', null)
            ->where('events.4.source', 'preventive'));
});

test('only the events of the requested asset are shown', function () {
    Ticket::factory()->create([
        'asset_card_id' => $this->otherAssetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 0)
            ->has('users', 0));
});

test('the timeline can be filtered by source and by user', function () {
    Ticket::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    PreventiveMaintenanceExecution::query()->create([
        'asset_card_id' => $this->assetCard->id,
        'maintenance_category_id' => $this->category->id,
        'title' => 'Revisión trimestral',
        'scheduled_at' => '2026-03-01',
        'assigned_to_id' => $this->technician->id,
    ]);

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard))
        ->assertInertia(fn (Assert $page) => $page->has('events', 2));

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard, '?source=ticket'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)
            ->where('events.0.source', 'ticket'));

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard, '?source=preventive'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)
            ->where('events.0.source', 'preventive'));

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard, '?user='.$this->operator->id))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)
            ->where('events.0.action', 'Reportado')
            ->has('users', 1)
            ->where('users.0.id', $this->operator->id));
});

test('the timeline is paginated', function () {
    Ticket::factory()->count(30)->create([
        'asset_card_id' => $this->assetCard->id,
        'reported_by' => $this->operator->id,
    ]);

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 25)
            ->where('pagination.total', 30)
            ->where('pagination.last_page', 2)
            ->where('pagination.prev_url', null)
            ->whereNot('pagination.next_url', null));

    $this->actingAs($this->admin)
        ->get(traceabilityUrl($this->assetCard, '?page=2'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 5)
            ->where('pagination.current_page', 2)
            ->where('pagination.next_url', null));
});

test('only the tenant administrator can open the traceability', function () {
    $this->actingAs($this->technician)
        ->get(traceabilityUrl($this->assetCard))
        ->assertForbidden();

    $this->actingAs($this->operator)
        ->get(traceabilityUrl($this->assetCard))
        ->assertForbidden();
});

test('the asset card links to its traceability', function () {
    $this->actingAs($this->admin)
        ->get('http://'.$this->tenant->id.'.localhost/asset-cards/'.$this->assetCard->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('traceabilityUrl', route('asset-cards.traceability', $this->assetCard)));
});
