<?php

use App\Enums\Role;
use App\Enums\TenantStatus;
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
        'name' => 'Acme Asset History',
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
        'code' => 'IDS-HIST-100',
    ]);

    $this->otherAssetCard = AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'IDS-HIST-200',
    ]);

    $this->category = MaintenanceCategory::query()->where('is_active', true)->first()
        ?? MaintenanceCategory::query()->create(['name' => 'Preventivo', 'is_active' => true]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::TENANT_ADMINISTRATOR->value);

    $this->technician = User::factory()->create();
    $this->technician->assignRole(Role::TECHNICIAN->value);

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

function historyUrl(AssetCard $assetCard): string
{
    return 'http://'.test()->tenant->id.'.localhost/asset-cards/'.$assetCard->id;
}

function historyTicket(AssetCard $assetCard, string $title, string $createdAt): Ticket
{
    return Ticket::factory()->create([
        'asset_card_id' => $assetCard->id,
        'title' => $title,
        'created_at' => Carbon::parse($createdAt),
    ]);
}

function historyExecution(AssetCard $assetCard, string $title, string $scheduledAt): PreventiveMaintenanceExecution
{
    return PreventiveMaintenanceExecution::factory()->create([
        'asset_card_id' => $assetCard->id,
        'maintenance_category_id' => test()->category->id,
        'assigned_to_id' => test()->technician->id,
        'title' => $title,
        'scheduled_at' => Carbon::parse($scheduledAt),
    ]);
}

it('mixes tickets and preventive executions ordered from newest to oldest', function () {
    historyTicket($this->assetCard, 'Falla vieja', '2025-12-20 10:00:00');
    historyTicket($this->assetCard, 'Falla reciente', '2025-12-28 10:00:00');
    historyExecution($this->assetCard, 'Preventivo futuro', '2026-01-10');
    historyExecution($this->assetCard, 'Preventivo pasado', '2025-12-25');

    $this->actingAs($this->admin)
        ->get(historyUrl($this->assetCard))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AssetCards/Show/Index')
            ->has('history', 4)
            ->where('history.0.title', 'Preventivo futuro')
            ->where('history.0.type', 'preventive')
            ->where('history.1.title', 'Falla reciente')
            ->where('history.1.type', 'ticket')
            ->where('history.2.title', 'Preventivo pasado')
            ->where('history.3.title', 'Falla vieja')
        );
});

it('only includes the events of the requested asset', function () {
    historyTicket($this->assetCard, 'Falla propia', '2025-12-20 10:00:00');
    historyExecution($this->assetCard, 'Preventivo propio', '2025-12-25');

    historyTicket($this->otherAssetCard, 'Falla ajena', '2025-12-21 10:00:00');
    historyExecution($this->otherAssetCard, 'Preventivo ajeno', '2025-12-26');

    $this->actingAs($this->admin)
        ->get(historyUrl($this->assetCard))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('history', 2)
            ->where('history.0.title', 'Preventivo propio')
            ->where('history.1.title', 'Falla propia')
        );
});

it('keeps the asset card closed to roles other than the tenant administrator', function () {
    historyTicket($this->assetCard, 'Falla', '2025-12-20 10:00:00');
    historyExecution($this->assetCard, 'Preventivo', '2025-12-25');

    $this->actingAs($this->technician)
        ->get(historyUrl($this->assetCard))
        ->assertForbidden();

    $this->actingAs($this->operator)
        ->get(historyUrl($this->assetCard))
        ->assertForbidden();
});
