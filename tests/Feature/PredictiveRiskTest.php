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
        'name' => 'Acme Predictive Risk',
        'contact_email' => 'predictive-risk@acme.test',
    ]);

    $this->tenant->update(['status' => TenantStatus::Active->value]);

    tenancy()->initialize($this->tenant);

    foreach (Role::cases() as $role) {
        SpatieRole::findOrCreate($role->value, 'web');
    }

    $template = AssetCardTemplate::query()->create([
        'name' => 'Equipo de prueba',
        'version' => 1,
    ]);

    CardTemplateVersion::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
    ]);

    $this->assetCard = AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'RISK-001',
    ]);

    $this->otherAssetCard = AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'RISK-002',
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

    $this->operator = User::factory()->create();
    $this->operator->assignRole(Role::OPERATOR->value);
});

afterEach(function () {
    Carbon::setTestNow();

    tenancy()->end();

    $this->tenant?->delete();

    @unlink(database_path('testing_central.sqlite'));
});

function predictiveRiskUrl(string $path = '/dashboard'): string
{
    return 'http://'.test()->tenant->id.'.localhost'.$path;
}

/**
 * @param  array<string, mixed>  $overrides
 */
function predictiveRiskTicket(array $overrides = []): Ticket
{
    $test = test();

    return Ticket::factory()->create(array_merge([
        'asset_card_id' => $test->assetCard->id,
        'maintenance_category_id' => $test->category->id,
        'maintenance_type' => MaintenanceType::Corrective,
        'priority' => MaintenancePriority::High,
        'reported_by' => $test->operator->id,
        'assigned_to' => $test->technician->id,
        'status' => TicketStatus::InProgress,
    ], $overrides));
}

it('shares the predictive risk with the statistics dashboard', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00'));

    predictiveRiskTicket();

    $this->actingAs($this->admin)
        ->get(predictiveRiskUrl())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('predictive_risk.summary.assets', 2)
            ->where('predictive_risk.window_days', 30)
            ->where('predictive_risk.model', 'LogisticRegression')
            ->has('predictive_risk.assets', 2));
});

it('returns the predictive risk to management roles', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00'));

    predictiveRiskTicket();

    $this->actingAs($this->chief)
        ->getJson(predictiveRiskUrl('/dashboard/predictive-risk'))
        ->assertOk()
        ->assertJsonPath('summary.assets', 2)
        ->assertJsonCount(2, 'assets')
        ->assertJsonStructure([
            'generated_at',
            'cutoff',
            'window_days',
            'model',
            'threshold',
            'summary' => [
                'assets',
                'high',
                'medium',
                'low',
                'average_probability',
            ],
            'assets' => [
                [
                    'asset_id',
                    'code',
                    'type',
                    'site',
                    'probability',
                    'risk',
                    'risk_color',
                ],
            ],
        ]);
});

it('forbids non-management roles from reading the predictive risk', function () {
    $this->actingAs($this->technician)
        ->getJson(predictiveRiskUrl('/dashboard/predictive-risk'))
        ->assertForbidden();

    $this->actingAs($this->operator)
        ->getJson(predictiveRiskUrl('/dashboard/predictive-risk'))
        ->assertForbidden();
});

it('refreshes predictions after the tenant data changes', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-01 09:00:00'));

    predictiveRiskTicket();

    $first = $this->actingAs($this->admin)
        ->getJson(predictiveRiskUrl('/dashboard/predictive-risk'))
        ->assertOk()
        ->json('generated_at');

    Carbon::setTestNow(Carbon::parse('2026-03-01 09:01:00'));

    predictiveRiskTicket();

    $second = $this->actingAs($this->admin)
        ->getJson(predictiveRiskUrl('/dashboard/predictive-risk'))
        ->assertOk()
        ->json('generated_at');

    expect($second)->not->toBe($first);
});
