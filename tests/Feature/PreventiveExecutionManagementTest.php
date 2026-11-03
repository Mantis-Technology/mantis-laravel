<?php

use App\Enums\PreventiveExecutionStatus;
use App\Enums\PreventiveFrequencyType;
use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Models\MaintenanceCategory;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\PreventiveMaintenancePlan;
use App\Models\Tenant;
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
        'name' => 'Acme Execution Management',
        'contact_email' => 'ops@acme.test',
    ]);

    $this->tenant->update(['status' => TenantStatus::Active->value]);

    tenancy()->initialize($this->tenant);

    Carbon::setTestNow(Carbon::parse('2026-01-01'));

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
        'code' => 'IDS-EXE-400',
    ]);

    $this->category = MaintenanceCategory::query()->where('is_active', true)->first()
        ?? MaintenanceCategory::query()->create(['name' => 'Preventivo', 'is_active' => true]);

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

    $this->tenant->delete();

    $centralPath = database_path('testing_central.sqlite');

    if (file_exists($centralPath)) {
        unlink($centralPath);
    }
});

function executionUrl(string $path = ''): string
{
    return 'http://'.test()->tenant->id.'.localhost/preventive-executions'.$path;
}

/**
 * A scheduled execution on 2026-01-10 assigned to the technician of the test.
 *
 * @param  array<string, mixed>  $overrides
 */
function managedExecution(array $overrides = []): PreventiveMaintenanceExecution
{
    $test = test();

    return PreventiveMaintenanceExecution::factory()->create(array_merge([
        'asset_card_id' => $test->assetCard->id,
        'maintenance_category_id' => $test->category->id,
        'assigned_to_id' => $test->technician->id,
        'scheduled_at' => Carbon::parse('2026-01-10'),
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function executionPayload(array $overrides = []): array
{
    $test = test();

    return array_merge([
        'asset_card_id' => $test->assetCard->id,
        'maintenance_category_id' => $test->category->id,
        'title' => 'Limpieza de filtros',
        'description' => 'Limpieza general',
        'instructions' => 'Usar aire comprimido',
        'scheduled_at' => '2026-01-15',
        'assigned_to_id' => $test->technician->id,
    ], $overrides);
}

it('lets the maintenance chief schedule a one off execution', function () {
    $this->actingAs($this->chief)
        ->post(executionUrl(), executionPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $execution = PreventiveMaintenanceExecution::query()->firstOrFail();

    expect($execution->preventive_maintenance_plan_id)->toBeNull();
    expect($execution->status)->toBe(PreventiveExecutionStatus::Scheduled);
    expect($execution->assigned_to_id)->toBe($this->technician->id);
    expect($execution->scheduled_at->toDateString())->toBe('2026-01-15');
    expect($execution->original_scheduled_at->toDateString())->toBe('2026-01-15');
    expect($execution->statusTransitions()->count())->toBe(1);
    expect($execution->statusTransitions()->first()->changed_by)->toBe($this->chief->id);
});

it('validates a one off execution and keeps creation to management roles', function () {
    $inactive = MaintenanceCategory::query()->create(['name' => 'Inactiva', 'is_active' => false]);

    $post = fn (array $overrides) => $this->actingAs($this->chief)
        ->post(executionUrl(), executionPayload($overrides));

    $post(['title' => ''])->assertSessionHasErrors('title');
    $post(['scheduled_at' => '2025-12-31'])->assertSessionHasErrors('scheduled_at');
    $post(['assigned_to_id' => $this->operator->id])->assertSessionHasErrors('assigned_to_id');
    $post(['maintenance_category_id' => $inactive->id])->assertSessionHasErrors('maintenance_category_id');

    expect(PreventiveMaintenanceExecution::count())->toBe(0);

    foreach ([$this->technician, $this->operator] as $user) {
        $this->actingAs($user)->post(executionUrl(), executionPayload())->assertForbidden();
        $this->actingAs($user)->get(executionUrl('/create'))->assertForbidden();
    }

    expect(PreventiveMaintenanceExecution::count())->toBe(0);
});

it('lets the assigned technician start and complete their execution', function () {
    $execution = managedExecution();

    $this->actingAs($this->technician)
        ->patch(executionUrl("/{$execution->id}/status"), [
            'status' => PreventiveExecutionStatus::InProgress->value,
        ])
        ->assertSessionHas('success');

    expect($execution->refresh()->status)->toBe(PreventiveExecutionStatus::InProgress);
    expect($execution->started_at)->not->toBeNull();

    $this->actingAs($this->technician)
        ->patch(executionUrl("/{$execution->id}/status"), [
            'status' => PreventiveExecutionStatus::Completed->value,
            'execution_notes' => 'Se limpiaron los filtros.',
            'findings' => 'Un filtro con desgaste.',
        ])
        ->assertSessionHas('success');

    $execution->refresh();

    expect($execution->status)->toBe(PreventiveExecutionStatus::Completed);
    expect($execution->completed_at)->not->toBeNull();
    expect($execution->completed_by_id)->toBe($this->technician->id);
    expect($execution->execution_notes)->toBe('Se limpiaron los filtros.');
    expect($execution->findings)->toBe('Un filtro con desgaste.');
    expect($execution->statusTransitions()->count())->toBe(3);
});

it('requires notes to complete and a reason to skip or cancel', function () {
    $inProgress = managedExecution([
        'status' => PreventiveExecutionStatus::InProgress,
        'started_at' => Carbon::now(),
    ]);

    $this->actingAs($this->technician)
        ->patch(executionUrl("/{$inProgress->id}/status"), [
            'status' => PreventiveExecutionStatus::Completed->value,
        ])
        ->assertSessionHasErrors('execution_notes');

    expect($inProgress->refresh()->status)->toBe(PreventiveExecutionStatus::InProgress);

    $execution = managedExecution();

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$execution->id}/status"), [
            'status' => PreventiveExecutionStatus::Skipped->value,
        ])
        ->assertSessionHasErrors('note');

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$execution->id}/status"), [
            'status' => PreventiveExecutionStatus::Skipped->value,
            'note' => 'No hubo acceso al equipo',
        ])
        ->assertSessionHas('success');

    expect($execution->refresh()->status)->toBe(PreventiveExecutionStatus::Skipped);
    expect($execution->statusTransitions()->get()->last()->note)->toBe('No hubo acceso al equipo');

    $other = managedExecution(['scheduled_at' => Carbon::parse('2026-01-11')]);

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$other->id}/status"), [
            'status' => PreventiveExecutionStatus::Cancelled->value,
        ])
        ->assertSessionHasErrors('note');
});

it('keeps technicians to their own executions and the operator read only', function () {
    $foreign = managedExecution(['assigned_to_id' => $this->otherTechnician->id]);
    $own = managedExecution(['scheduled_at' => Carbon::parse('2026-01-11')]);

    $this->actingAs($this->technician)->get(executionUrl("/{$foreign->id}"))->assertForbidden();
    $this->actingAs($this->technician)
        ->patch(executionUrl("/{$foreign->id}/status"), [
            'status' => PreventiveExecutionStatus::InProgress->value,
        ])
        ->assertForbidden();

    $this->actingAs($this->technician)
        ->patch(executionUrl("/{$own->id}/status"), [
            'status' => PreventiveExecutionStatus::Skipped->value,
            'note' => 'Prefiero omitirla',
        ])
        ->assertForbidden();

    expect($own->refresh()->status)->toBe(PreventiveExecutionStatus::Scheduled);

    $this->actingAs($this->operator)->get(executionUrl("/{$foreign->id}"))->assertOk();
    $this->actingAs($this->operator)
        ->patch(executionUrl("/{$foreign->id}/status"), [
            'status' => PreventiveExecutionStatus::InProgress->value,
        ])
        ->assertForbidden();
});

it('postpones a scheduled execution keeping its original date and an audit trail', function () {
    $execution = managedExecution();

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$execution->id}/schedule"), ['scheduled_at' => '2026-01-20'])
        ->assertSessionHasErrors('note');

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$execution->id}/schedule"), [
            'scheduled_at' => '2026-01-20',
            'note' => 'Repuesto pendiente',
        ])
        ->assertSessionHas('success');

    $execution->refresh();

    expect($execution->scheduled_at->toDateString())->toBe('2026-01-20');
    expect($execution->original_scheduled_at->toDateString())->toBe('2026-01-10');
    expect($execution->statusTransitions()->count())->toBe(2);
    expect($execution->statusTransitions()->get()->last()->note)->toContain('Repuesto pendiente');

    $this->actingAs($this->technician)
        ->patch(executionUrl("/{$execution->id}/schedule"), [
            'scheduled_at' => '2026-01-25',
            'note' => 'Intento',
        ])
        ->assertForbidden();

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$execution->id}/schedule"), [
            'scheduled_at' => '2026-01-05',
            'note' => 'Fecha anterior',
        ])
        ->assertSessionHas('error');
});

it('rejects postponing onto a date that another execution of the plan already holds', function () {
    $plan = PreventiveMaintenancePlan::factory()->create([
        'asset_card_id' => $this->assetCard->id,
        'maintenance_category_id' => $this->category->id,
        'responsible_id' => $this->technician->id,
        'frequency_type' => PreventiveFrequencyType::Daily,
        'starts_at' => Carbon::parse('2026-01-10'),
    ]);

    $first = managedExecution(['preventive_maintenance_plan_id' => $plan->id]);
    managedExecution([
        'preventive_maintenance_plan_id' => $plan->id,
        'scheduled_at' => Carbon::parse('2026-01-11'),
    ]);

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$first->id}/schedule"), [
            'scheduled_at' => '2026-01-11',
            'note' => 'Choca con la siguiente',
        ])
        ->assertSessionHas('error', 'Ya existe una ejecución programada para ese plan en la fecha indicada.');

    expect($first->refresh()->scheduled_at->toDateString())->toBe('2026-01-10');
});

it('reassigns an execution to another technician and records it', function () {
    $execution = managedExecution();

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$execution->id}/assignment"), [
            'assigned_to_id' => $this->otherTechnician->id,
            'note' => 'Cambio de turno',
        ])
        ->assertSessionHas('success');

    $execution->refresh();

    expect($execution->assigned_to_id)->toBe($this->otherTechnician->id);
    expect($execution->statusTransitions()->count())->toBe(2);
    expect($execution->statusTransitions()->get()->last()->note)->toContain('Reasignado a');

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$execution->id}/assignment"), [
            'assigned_to_id' => $this->otherTechnician->id,
        ])
        ->assertSessionHas('error', 'La ejecución ya está asignada a ese técnico.');

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$execution->id}/assignment"), [
            'assigned_to_id' => $this->operator->id,
        ])
        ->assertSessionHasErrors('assigned_to_id');

    $this->actingAs($this->technician)
        ->patch(executionUrl("/{$execution->id}/assignment"), [
            'assigned_to_id' => $this->technician->id,
        ])
        ->assertForbidden();

    $completed = PreventiveMaintenanceExecution::factory()->completed()->create([
        'asset_card_id' => $this->assetCard->id,
        'maintenance_category_id' => $this->category->id,
        'assigned_to_id' => $this->technician->id,
    ]);

    $this->actingAs($this->chief)
        ->patch(executionUrl("/{$completed->id}/assignment"), [
            'assigned_to_id' => $this->otherTechnician->id,
        ])
        ->assertSessionHas('error', 'Solo se puede reasignar una ejecución programada o en proceso.');
});

it('lists the executions each role may see and applies the filters', function () {
    managedExecution(['title' => 'Mía']);
    managedExecution(['title' => 'Ajena', 'assigned_to_id' => $this->otherTechnician->id]);
    PreventiveMaintenanceExecution::factory()->completed()->create([
        'title' => 'Hecha',
        'asset_card_id' => $this->assetCard->id,
        'maintenance_category_id' => $this->category->id,
        'assigned_to_id' => $this->technician->id,
        'scheduled_at' => Carbon::parse('2026-01-12'),
    ]);

    $this->actingAs($this->technician)
        ->get(executionUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('executions', 2)
            ->where('can_manage', false)
            ->etc());

    $this->actingAs($this->technician)
        ->get(executionUrl('?assigned=me'))
        ->assertInertia(fn (Assert $page) => $page->has('executions', 2)->etc());

    $this->actingAs($this->chief)
        ->get(executionUrl())
        ->assertInertia(fn (Assert $page) => $page
            ->has('executions', 3)
            ->where('can_manage', true)
            ->etc());

    $this->actingAs($this->chief)
        ->get(executionUrl('?status='.PreventiveExecutionStatus::Completed->value))
        ->assertInertia(fn (Assert $page) => $page
            ->has('executions', 1)
            ->where('executions.0.title', 'Hecha')
            ->etc());

    $this->actingAs($this->operator)
        ->get(executionUrl())
        ->assertInertia(fn (Assert $page) => $page
            ->has('executions', 3)
            ->where('can_manage', false)
            ->etc());
});

it('shows the history, the overdue flag and the allowed actions of each role', function () {
    $execution = managedExecution(['scheduled_at' => Carbon::parse('2025-12-30')]);

    $this->actingAs($this->chief)
        ->get(executionUrl("/{$execution->id}"))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('execution.status_transitions', 1)
            ->has('execution.allowed_transitions')
            ->where('execution.is_overdue', true)
            ->where('execution.can_postpone', true)
            ->where('execution.can_reassign', true)
            ->etc());

    $this->actingAs($this->technician)
        ->get(executionUrl("/{$execution->id}"))
        ->assertInertia(fn (Assert $page) => $page
            ->where('execution.allowed_transitions.0.value', PreventiveExecutionStatus::InProgress->value)
            ->where('execution.can_postpone', false)
            ->where('execution.can_reassign', false)
            ->etc());

    $this->actingAs($this->operator)
        ->get(executionUrl("/{$execution->id}"))
        ->assertInertia(fn (Assert $page) => $page
            ->has('execution.allowed_transitions', 0)
            ->etc());
});
