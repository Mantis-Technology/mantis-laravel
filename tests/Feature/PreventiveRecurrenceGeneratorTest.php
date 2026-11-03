<?php

use App\Enums\PreventiveExecutionStatus;
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
        'name' => 'Acme Preventive',
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
        'code' => 'IDS-PREV-100',
    ]);

    $this->technician = User::factory()->create();
    $this->technician->assignRole(Role::TECHNICIAN->value);

    $this->category = MaintenanceCategory::query()->first();
    if (! $this->category) {
        $this->category = MaintenanceCategory::query()->create([
            'name' => 'Preventivo',
            'is_active' => true,
        ]);
    } elseif (! ($this->category->is_active ?? true)) {
        $this->category->update(['is_active' => true]);
    }
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

/**
 * A daily plan starting on 2026-01-01 for the asset, category and technician
 * of the current test. Pass overrides to change any attribute.
 *
 * @param  array<string, mixed>  $overrides
 */
function recurrencePlan(array $overrides = []): PreventiveMaintenancePlan
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

// A 90 day horizon includes both today and today + 90, so 91 dates.
const RECURRENCE_HORIZON_COUNT = 91;

it('generates executions up to the horizon with the plan data and its responsible', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan([
        'title' => 'Cambio de filtros',
        'description' => 'Descripción',
        'instructions' => 'Instrucciones',
    ]);

    $created = (new RecurrenceGenerator)->generate($plan);

    expect($created)->toBe(RECURRENCE_HORIZON_COUNT);
    expect(PreventiveMaintenanceExecution::count())->toBe(RECURRENCE_HORIZON_COUNT);

    $first = PreventiveMaintenanceExecution::query()->orderBy('scheduled_at')->first();
    expect($first->scheduled_at->toDateString())->toBe('2026-01-01');
    expect($first->asset_card_id)->toBe($this->assetCard->id);
    expect($first->maintenance_category_id)->toBe($this->category->id);
    expect($first->title)->toBe('Cambio de filtros');
    expect($first->description)->toBe('Descripción');
    expect($first->instructions)->toBe('Instrucciones');
    expect($first->assigned_to_id)->toBe($this->technician->id);
    expect($first->status)->toBe(PreventiveExecutionStatus::Scheduled);

    $last = PreventiveMaintenanceExecution::query()->orderByDesc('scheduled_at')->first();
    expect($last->scheduled_at->toDateString())->toBe('2026-04-01');
});

it('records the initial audit row of every execution it creates', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan([
        'frequency_type' => PreventiveFrequencyType::Once,
        'starts_at' => Carbon::parse('2026-01-05'),
    ]);

    (new RecurrenceGenerator)->generate($plan);

    $execution = PreventiveMaintenanceExecution::first();
    expect($execution->statusTransitions()->count())->toBe(1);
});

it('does not duplicate executions when generating twice', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan();
    $generator = new RecurrenceGenerator;

    expect($generator->generate($plan))->toBe(RECURRENCE_HORIZON_COUNT);
    expect($generator->generate($plan))->toBe(0);
    expect(PreventiveMaintenanceExecution::count())->toBe(RECURRENCE_HORIZON_COUNT);
});

it('does not generate while paused and generates after reactivating', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan();
    $generator = new RecurrenceGenerator;

    expect($plan->transitionTo(PreventivePlanStatus::Paused))->toBeNull();
    expect($generator->generate($plan))->toBe(0);
    expect(PreventiveMaintenanceExecution::count())->toBe(0);

    expect($plan->transitionTo(PreventivePlanStatus::Active))->toBeNull();
    expect($generator->generate($plan))->toBe(RECURRENCE_HORIZON_COUNT);
});

it('does not regenerate the original date of a postponed execution', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan();
    $generator = new RecurrenceGenerator;
    $generator->generate($plan);

    $execution = PreventiveMaintenanceExecution::whereDate('scheduled_at', '2026-01-02')->first();

    // 2026-06-01 is beyond the horizon, so no other execution holds that date.
    expect($execution->postpone(Carbon::parse('2026-06-01'), $this->technician->id, 'Repuesto pendiente'))->toBeNull();

    expect($generator->generate($plan))->toBe(0);
    expect(PreventiveMaintenanceExecution::whereDate('original_scheduled_at', '2026-01-02')->count())->toBe(1);
    expect(PreventiveMaintenanceExecution::whereDate('scheduled_at', '2026-01-02')->count())->toBe(0);
});

it('does not regenerate skipped or cancelled executions', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan();
    $generator = new RecurrenceGenerator;
    $generator->generate($plan);

    $skipped = PreventiveMaintenanceExecution::whereDate('scheduled_at', '2026-01-03')->first();
    expect($skipped->transitionTo(PreventiveExecutionStatus::Skipped, $this->technician->id, 'No hubo acceso'))->toBeNull();

    $cancelled = PreventiveMaintenanceExecution::whereDate('scheduled_at', '2026-01-04')->first();
    expect($cancelled->transitionTo(PreventiveExecutionStatus::Cancelled, $this->technician->id, 'Equipo dado de baja'))->toBeNull();

    expect($generator->generate($plan))->toBe(0);
    expect(PreventiveMaintenanceExecution::whereDate('original_scheduled_at', '2026-01-03')->count())->toBe(1);
    expect(PreventiveMaintenanceExecution::whereDate('original_scheduled_at', '2026-01-04')->count())->toBe(1);
});

it('finishes a plan whose end date has passed and generates nothing', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10'));
    $plan = recurrencePlan(['ends_at' => Carbon::parse('2026-01-05')]);

    expect((new RecurrenceGenerator)->generate($plan))->toBe(0);
    expect($plan->refresh()->status)->toBe(PreventivePlanStatus::Finished);
    expect(PreventiveMaintenanceExecution::count())->toBe(0);
});

it('still generates on the end date of the plan and keeps it active', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10'));
    $plan = recurrencePlan(['ends_at' => Carbon::parse('2026-01-10')]);

    expect((new RecurrenceGenerator)->generate($plan))->toBe(1);
    expect($plan->refresh()->status)->toBe(PreventivePlanStatus::Active);
});

it('keeps a plan without end date active', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan(['ends_at' => null]);

    (new RecurrenceGenerator)->generate($plan);

    expect($plan->refresh()->status)->toBe(PreventivePlanStatus::Active);
});

it('does not create executions before today when the plan started in the past', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10'));
    $plan = recurrencePlan(['starts_at' => Carbon::parse('2026-01-01')]);

    expect((new RecurrenceGenerator)->generate($plan))->toBe(RECURRENCE_HORIZON_COUNT);
    expect(PreventiveMaintenanceExecution::whereDate('scheduled_at', '<', '2026-01-10')->count())->toBe(0);
});

it('creates exactly one execution for a future one time plan', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan([
        'frequency_type' => PreventiveFrequencyType::Once,
        'starts_at' => Carbon::parse('2026-02-10'),
    ]);

    expect((new RecurrenceGenerator)->generate($plan))->toBe(1);
    expect(PreventiveMaintenanceExecution::count())->toBe(1);
    expect(PreventiveMaintenanceExecution::first()->scheduled_at->toDateString())->toBe('2026-02-10');
});

it('uses the date it is given instead of the clock', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01'));
    $plan = recurrencePlan();

    $created = (new RecurrenceGenerator)->generate($plan, Carbon::parse('2026-03-01'));

    expect($created)->toBe(RECURRENCE_HORIZON_COUNT);
    expect(PreventiveMaintenanceExecution::query()->orderBy('scheduled_at')->first()->scheduled_at->toDateString())->toBe('2026-03-01');
});
