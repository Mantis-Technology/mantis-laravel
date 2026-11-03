<?php

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Enums\PreventiveExecutionStatus;
use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Models\MaintenanceCategory;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\ServiceLevel;
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
        'name' => 'Acme Statistics',
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
        'code' => 'IDS-STA-100',
    ]);

    $this->otherAssetCard = AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'IDS-STA-200',
    ]);

    $this->category = MaintenanceCategory::query()->create([
        'name' => 'Eléctrica',
    ]);

    $this->otherCategory = MaintenanceCategory::query()->create([
        'name' => 'Mecánica',
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

function statisticsUrl(string $query = ''): string
{
    return 'http://'.test()->tenant->id.'.localhost/dashboard'.$query;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function statisticsTicket(array $overrides = []): Ticket
{
    $test = test();

    return Ticket::factory()->create(array_merge([
        'asset_card_id' => $test->assetCard->id,
        'maintenance_category_id' => $test->category->id,
        'maintenance_type' => MaintenanceType::Corrective,
        'priority' => MaintenancePriority::Medium,
        'reported_by' => $test->operator->id,
        'assigned_to' => $test->technician->id,
        'status' => TicketStatus::InProgress,
    ], $overrides));
}

test('the report summarizes cases, attention times and worked hours', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-01 08:00:00'));

    $ticket = statisticsTicket();

    Carbon::setTestNow(Carbon::parse('2026-03-01 10:30:00'));
    $ticket->transitionTo(TicketStatus::Resolved, $this->technician->id);

    Carbon::setTestNow(Carbon::parse('2026-03-01 11:00:00'));

    statisticsTicket([
        'status' => TicketStatus::Reported,
        'assigned_to' => null,
    ]);

    $this->actingAs($this->chief)
        ->get(statisticsUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('summary.total_tickets', 2)
            ->where('summary.open_tickets', 2)
            ->where('summary.resolved_tickets', 1)
            ->where('summary.closed_tickets', 0)
            ->where('summary.average_response_hours', 0)
            ->where('summary.average_resolution_hours', 2.5)
            ->where('summary.ticket_worked_hours', 2.5)
            ->where('summary.total_worked_hours', 2.5)
            ->where('category_distribution.0.name', 'Eléctrica')
            ->where('category_distribution.0.tickets', 2)
            ->where('technicians.0.assigned_tickets', 1)
            ->where('technicians.0.resolved_tickets', 1)
            ->where('technicians.0.worked_hours', 2.5));
});

test('the report calculates service level compliance from the case lifecycle', function () {
    ServiceLevel::query()->create([
        'maintenance_category_id' => null,
        'maintenance_type' => null,
        'priority' => null,
        'response_hours' => 1,
        'resolution_hours' => 1,
        'is_active' => true,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-03-01 08:00:00'));

    $met = statisticsTicket();

    Carbon::setTestNow(Carbon::parse('2026-03-01 08:30:00'));
    $met->transitionTo(TicketStatus::Resolved, $this->technician->id);

    Carbon::setTestNow(Carbon::parse('2026-03-01 08:00:00'));

    $missed = statisticsTicket();

    Carbon::setTestNow(Carbon::parse('2026-03-01 10:00:00'));
    $missed->transitionTo(TicketStatus::Resolved, $this->technician->id);

    $this->actingAs($this->admin)
        ->get(statisticsUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.sla_evaluated', 2)
            ->where('summary.sla_met', 1)
            ->where('summary.sla_missed', 1)
            ->where('summary.sla_compliance_rate', 50)
            ->where('technicians.0.sla_compliance_rate', 50));
});

test('the report filters cases and preventive executions by date, category, asset, technician and status', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00'));

    $ticket = statisticsTicket();

    statisticsTicket([
        'asset_card_id' => $this->otherAssetCard->id,
        'maintenance_category_id' => $this->otherCategory->id,
        'assigned_to' => $this->otherTechnician->id,
        'status' => TicketStatus::Reported,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-02-01 09:00:00'));

    statisticsTicket([
        'status' => TicketStatus::Reported,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00'));

    PreventiveMaintenanceExecution::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'maintenance_category_id' => $this->category->id,
        'assigned_to_id' => $this->technician->id,
        'scheduled_at' => Carbon::parse('2026-03-02'),
        'status' => PreventiveExecutionStatus::Scheduled,
    ]);

    PreventiveMaintenanceExecution::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'maintenance_category_id' => $this->category->id,
        'assigned_to_id' => $this->technician->id,
        'scheduled_at' => Carbon::parse('2026-03-03'),
        'status' => PreventiveExecutionStatus::Completed,
        'started_at' => Carbon::parse('2026-03-03 08:00:00'),
        'completed_at' => Carbon::parse('2026-03-03 09:00:00'),
        'completed_by_id' => $this->technician->id,
        'execution_notes' => 'Trabajo realizado.',
    ]);

    $query = http_build_query([
        'date_from' => '2026-03-01',
        'date_to' => '2026-03-02',
        'maintenance_category_id' => $this->category->id,
        'asset_card_id' => $this->assetCard->id,
        'technician_id' => $this->technician->id,
        'status' => TicketStatus::InProgress->value,
        'preventive_status' => PreventiveExecutionStatus::Scheduled->value,
    ]);

    $this->actingAs($this->chief)
        ->get(statisticsUrl('?'.$query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.date_from', '2026-03-01')
            ->where('filters.date_to', '2026-03-02')
            ->where('summary.total_tickets', 1)
            ->where('summary.total_preventive', 1)
            ->where('summary.overdue_preventive', 0));

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

test('the report measures preventive work and highlights overdue executions', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-01 08:00:00'));

    $execution = PreventiveMaintenanceExecution::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'maintenance_category_id' => $this->category->id,
        'assigned_to_id' => $this->technician->id,
        'scheduled_at' => Carbon::parse('2026-03-01'),
    ]);

    $execution->transitionTo(PreventiveExecutionStatus::InProgress, $this->technician->id);

    Carbon::setTestNow(Carbon::parse('2026-03-01 09:30:00'));

    $execution->execution_notes = 'Revisión completa.';
    $execution->save();
    $execution->transitionTo(PreventiveExecutionStatus::Completed, $this->technician->id);

    $overdue = PreventiveMaintenanceExecution::factory()->create([
        'asset_card_id' => $this->otherAssetCard->id,
        'maintenance_category_id' => $this->otherCategory->id,
        'assigned_to_id' => $this->otherTechnician->id,
        'scheduled_at' => Carbon::parse('2026-02-27'),
        'status' => PreventiveExecutionStatus::Scheduled,
    ]);

    $this->actingAs($this->chief)
        ->get(statisticsUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total_preventive', 2)
            ->where('summary.completed_preventive', 1)
            ->where('summary.overdue_preventive', 1)
            ->where('summary.preventive_worked_hours', 1.5)
            ->where('summary.total_worked_hours', 1.5)
            ->where('attention.overdue_preventive.0.id', $overdue->id)
            ->where('technicians.0.completed_preventive', 1));
});

test('the dashboard only exposes operational statistics to management roles', function () {
    $this->actingAs($this->operator)
        ->get(statisticsUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('summary'));

    $this->actingAs($this->technician)
        ->get(statisticsUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('summary'));
});
