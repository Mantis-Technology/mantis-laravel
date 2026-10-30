<?php

use App\Enums\PreventiveFrequencyType;
use App\Enums\PreventivePlanStatus;
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
use App\Services\PreventiveMaintenance\RecurrenceGenerator;
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
        'name' => 'Acme Plan Management',
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
        'code' => 'IDS-PLAN-300',
    ]);

    $this->category = MaintenanceCategory::query()->where('is_active', true)->first()
        ?? MaintenanceCategory::query()->create(['name' => 'Preventivo', 'is_active' => true]);

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

    $this->tenant->delete();

    $centralPath = database_path('testing_central.sqlite');

    if (file_exists($centralPath)) {
        unlink($centralPath);
    }
});

function planUrl(string $path = ''): string
{
    return 'http://'.test()->tenant->id.'.localhost/preventive-plans'.$path;
}

/**
 * Valid body for creating a weekly plan starting on 2026-01-05.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function planPayload(array $overrides = []): array
{
    $test = test();

    return array_merge([
        'asset_card_id' => $test->assetCard->id,
        'maintenance_category_id' => $test->category->id,
        'title' => 'Revisión semanal de UPS',
        'description' => 'Inspección general',
        'instructions' => 'Seguir el checklist',
        'frequency_type' => PreventiveFrequencyType::Weekly->value,
        'interval' => 1,
        'starts_at' => '2026-01-05',
        'ends_at' => null,
        'responsible_id' => $test->technician->id,
    ], $overrides);
}

/**
 * A daily plan starting on 2026-01-01 (no executions generated yet).
 *
 * @param  array<string, mixed>  $overrides
 */
function managedPlan(array $overrides = []): PreventiveMaintenancePlan
{
    $test = test();

    return PreventiveMaintenancePlan::factory()->create(array_merge([
        'asset_card_id' => $test->assetCard->id,
        'maintenance_category_id' => $test->category->id,
        'responsible_id' => $test->technician->id,
        'frequency_type' => PreventiveFrequencyType::Daily,
        'interval' => 1,
        'starts_at' => Carbon::parse('2026-01-01'),
    ], $overrides));
}

it('lets the maintenance chief schedule a recurring plan and generates its executions', function () {
    $this->actingAs($this->chief)
        ->post(planUrl(), planPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $plan = PreventiveMaintenancePlan::query()->firstOrFail();

    expect($plan->status)->toBe(PreventivePlanStatus::Active);
    expect($plan->created_by_id)->toBe($this->chief->id);
    expect($plan->responsible_id)->toBe($this->technician->id);
    expect($plan->asset_card_id)->toBe($this->assetCard->id);

    // Weekly from 2026-01-05 up to the 90 day horizon (2026-04-01): 13 dates.
    expect($plan->executions()->count())->toBe(13);
});

it('creates a one time plan that ends on its start date with a single execution', function () {
    $this->actingAs($this->chief)
        ->post(planUrl(), planPayload([
            'frequency_type' => PreventiveFrequencyType::Once->value,
            'interval' => null,
            'starts_at' => '2026-02-10',
            'ends_at' => '2026-03-01',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $plan = PreventiveMaintenancePlan::query()->firstOrFail();

    expect($plan->interval)->toBe(1);
    expect($plan->ends_at->toDateString())->toBe('2026-02-10');
    expect($plan->executions()->count())->toBe(1);
});

it('validates the data of a new plan', function () {
    $inactive = MaintenanceCategory::query()->create(['name' => 'Inactiva', 'is_active' => false]);

    $post = fn (array $overrides) => $this->actingAs($this->chief)
        ->post(planUrl(), planPayload($overrides));

    $post(['title' => ''])->assertSessionHasErrors('title');
    $post(['maintenance_category_id' => $inactive->id])->assertSessionHasErrors('maintenance_category_id');
    $post(['responsible_id' => $this->operator->id])->assertSessionHasErrors('responsible_id');
    $post(['starts_at' => '2025-12-31'])->assertSessionHasErrors('starts_at');
    $post(['ends_at' => '2026-01-01'])->assertSessionHasErrors('ends_at');
    $post(['interval' => null])->assertSessionHasErrors('interval');
    $post(['interval' => 0])->assertSessionHasErrors('interval');
    $post(['frequency_type' => 'hourly'])->assertSessionHasErrors('frequency_type');

    expect(PreventiveMaintenancePlan::count())->toBe(0);
});

it('only lets management roles create plans', function () {
    $this->get(planUrl())->assertRedirect();

    foreach ([$this->technician, $this->operator] as $user) {
        $this->actingAs($user)->post(planUrl(), planPayload())->assertForbidden();
        $this->actingAs($user)->get(planUrl('/create'))->assertForbidden();
    }

    expect(PreventiveMaintenancePlan::count())->toBe(0);

    $this->actingAs($this->admin)
        ->post(planUrl(), planPayload())
        ->assertSessionHas('success');

    expect(PreventiveMaintenancePlan::count())->toBe(1);
});

it('pauses a plan and generates the pending executions when it is reactivated', function () {
    $plan = managedPlan();

    $this->actingAs($this->chief)
        ->patch(planUrl("/{$plan->id}/status"), ['status' => PreventivePlanStatus::Paused->value])
        ->assertSessionHas('success');

    expect($plan->refresh()->status)->toBe(PreventivePlanStatus::Paused);
    expect($plan->executions()->count())->toBe(0);

    $this->actingAs($this->chief)
        ->patch(planUrl("/{$plan->id}/status"), ['status' => PreventivePlanStatus::Active->value])
        ->assertSessionHas('success');

    expect($plan->refresh()->status)->toBe(PreventivePlanStatus::Active);
    expect($plan->executions()->count())->toBe(91);
});

it('does not let a finished plan change state and never offers finishing by hand', function () {
    $plan = managedPlan(['status' => PreventivePlanStatus::Finished]);

    $this->actingAs($this->chief)
        ->patch(planUrl("/{$plan->id}/status"), ['status' => PreventivePlanStatus::Paused->value])
        ->assertSessionHas('error');

    expect($plan->refresh()->status)->toBe(PreventivePlanStatus::Finished);

    $this->actingAs($this->chief)
        ->patch(planUrl("/{$plan->id}/status"), ['status' => PreventivePlanStatus::Finished->value])
        ->assertSessionHasErrors('status');
});

it('generates the next occurrences on demand and only for active plans', function () {
    $plan = managedPlan();

    $this->actingAs($this->chief)
        ->post(planUrl("/{$plan->id}/occurrences"))
        ->assertSessionHas('success');

    expect($plan->executions()->count())->toBe(91);

    $this->actingAs($this->chief)
        ->post(planUrl("/{$plan->id}/occurrences"))
        ->assertSessionHas('success', 'No hay ejecuciones nuevas por programar.');

    expect($plan->executions()->count())->toBe(91);

    $this->actingAs($this->technician)
        ->post(planUrl("/{$plan->id}/occurrences"))
        ->assertForbidden();

    $plan->transitionTo(PreventivePlanStatus::Paused);

    $this->actingAs($this->chief)
        ->post(planUrl("/{$plan->id}/occurrences"))
        ->assertSessionHas('error', 'Solo un plan activo puede generar ejecuciones.');
});

it('updates only the editable data of a plan', function () {
    $plan = managedPlan(['frequency_type' => PreventiveFrequencyType::Weekly]);
    $newCategory = MaintenanceCategory::query()->create(['name' => 'Eléctrico', 'is_active' => true]);

    $this->actingAs($this->chief)
        ->put(planUrl("/{$plan->id}"), [
            'title' => 'Título nuevo',
            'description' => 'Descripción nueva',
            'instructions' => 'Instrucciones nuevas',
            'maintenance_category_id' => $newCategory->id,
            'responsible_id' => $this->otherTechnician->id,
            'ends_at' => '2026-06-30',
            'frequency_type' => PreventiveFrequencyType::Daily->value,
            'interval' => 5,
            'starts_at' => '2026-02-01',
        ])
        ->assertSessionHas('success');

    $plan->refresh();

    expect($plan->title)->toBe('Título nuevo');
    expect($plan->maintenance_category_id)->toBe($newCategory->id);
    expect($plan->responsible_id)->toBe($this->otherTechnician->id);
    expect($plan->ends_at->toDateString())->toBe('2026-06-30');
    expect($plan->frequency_type)->toBe(PreventiveFrequencyType::Weekly);
    expect($plan->interval)->toBe(1);
    expect($plan->starts_at->toDateString())->toBe('2026-01-01');

    $this->actingAs($this->chief)
        ->put(planUrl("/{$plan->id}"), [
            'title' => 'Título nuevo',
            'maintenance_category_id' => $newCategory->id,
            'responsible_id' => $this->otherTechnician->id,
            'ends_at' => '2025-12-01',
        ])
        ->assertSessionHasErrors('ends_at');

    $cancelled = managedPlan(['status' => PreventivePlanStatus::Cancelled]);

    $this->actingAs($this->chief)
        ->put(planUrl("/{$cancelled->id}"), [
            'title' => 'No debe cambiar',
            'maintenance_category_id' => $newCategory->id,
            'responsible_id' => $this->technician->id,
        ])
        ->assertSessionHas('error');

    expect($cancelled->refresh()->title)->not->toBe('No debe cambiar');
});

it('shows each role only the plans it may see and keeps the operator read only', function () {
    managedPlan(['title' => 'Mi plan']);
    $other = managedPlan(['title' => 'Plan ajeno', 'responsible_id' => $this->otherTechnician->id]);
    $own = PreventiveMaintenancePlan::query()->where('title', 'Mi plan')->firstOrFail();

    $this->actingAs($this->technician)
        ->get(planUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('plans', 1)
            ->where('plans.0.title', 'Mi plan')
            ->where('can_manage', false)
            ->etc());

    $this->actingAs($this->technician)->get(planUrl("/{$own->id}"))->assertOk();
    $this->actingAs($this->technician)->get(planUrl("/{$other->id}"))->assertForbidden();

    $this->actingAs($this->operator)
        ->get(planUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('plans', 2)
            ->where('can_manage', false)
            ->etc());

    $this->actingAs($this->operator)->get(planUrl("/{$other->id}"))->assertOk();
    $this->actingAs($this->operator)
        ->put(planUrl("/{$other->id}"), [
            'title' => 'x',
            'maintenance_category_id' => $this->category->id,
            'responsible_id' => $this->technician->id,
        ])
        ->assertForbidden();

    $this->actingAs($this->chief)
        ->get(planUrl())
        ->assertInertia(fn (Assert $page) => $page
            ->has('plans', 2)
            ->where('can_manage', true)
            ->etc());
});

it('exposes the executions and the allowed actions on the plan screen', function () {
    $plan = managedPlan();
    (new RecurrenceGenerator)->generate($plan);

    $this->actingAs($this->chief)
        ->get(planUrl("/{$plan->id}"))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('plan.executions', 91)
            ->has('plan.allowed_transitions')
            ->where('plan.frequency.label', 'Diaria')
            ->where('can_manage', true)
            ->where('can_generate', true)
            ->etc());

    $this->actingAs($this->technician)
        ->get(planUrl("/{$plan->id}"))
        ->assertInertia(fn (Assert $page) => $page
            ->has('plan.allowed_transitions', 0)
            ->where('can_manage', false)
            ->where('can_generate', false)
            ->etc());

    expect(PreventiveMaintenanceExecution::count())->toBe(91);
});
