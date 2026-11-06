<?php

use App\Enums\PreventiveExecutionStatus;
use App\Enums\TenantStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\PreventiveMaintenancePlan;
use App\Models\ServiceLevel;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketClosure;
use App\Services\PredictiveDemo\GenerateTenantHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $centralPath = database_path('testing_central.sqlite');
    $tenantPath = database_path('tenant_acme-predictivo');

    if (file_exists($centralPath)) {
        unlink($centralPath);
    }

    if (file_exists($tenantPath)) {
        unlink($tenantPath);
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
        'predictive.demo.seed' => 12345,
        'predictive.demo.assets_per_tenant' => 4,
        'predictive.demo.history_start' => '2023-01-01',
        'predictive.demo.history_end' => '2024-06-30',
    ]);

    $this->artisan('migrate', [
        '--database' => 'testing_central',
        '--force' => true,
    ])->assertSuccessful();

    $this->tenant = Tenant::create([
        'name' => 'Acme Predictivo',
        'contact_email' => 'predictivo@acme.test',
    ]);

    $this->tenant->update(['status' => TenantStatus::Active->value]);

    tenancy()->initialize($this->tenant);
});

afterEach(function () {
    tenancy()->end();
    Carbon::setTestNow();

    $tenantPath = database_path('tenant_acme-predictivo');

    if (file_exists($tenantPath)) {
        unlink($tenantPath);
    }
});

it('generates a coherent predictive history for one tenant', function () {
    app(GenerateTenantHistory::class)->execute(
        $this->tenant,
        config('predictive.demo.companies.0'),
    );

    expect(AssetCard::query()->count())->toBe(4)
        ->and(AssetCardTemplate::query()->count())->toBe(1)
        ->and(PreventiveMaintenancePlan::query()->count())->toBe(4)
        ->and(PreventiveMaintenanceExecution::query()->count())->toBeGreaterThan(0)
        ->and(PreventiveMaintenanceExecution::query()
            ->where('status', PreventiveExecutionStatus::Completed)
            ->count())->toBeGreaterThan(0)
        ->and(ServiceLevel::query()->count())->toBe(4)
        ->and(Ticket::query()->count())->toBeGreaterThan(0)
        ->and(TicketClosure::query()->count())->toBeGreaterThan(0);

    $ticket = Ticket::query()->firstOrFail();

    expect($ticket->asset_card_id)->not->toBeNull()
        ->and($ticket->maintenance_category_id)->not->toBeNull()
        ->and($ticket->priority)->not->toBeNull()
        ->and($ticket->maintenance_type)->not->toBeNull()
        ->and($ticket->assigned_to)->not->toBeNull()
        ->and($ticket->statusTransitions()->count())->toBeGreaterThan(1);
});

it('does not duplicate the synthetic history when run twice', function () {
    $service = app(GenerateTenantHistory::class);
    $company = config('predictive.demo.companies.0');

    $service->execute($this->tenant, $company);
    $service->execute($this->tenant, $company);

    expect(AssetCard::query()->count())->toBe(4)
        ->and(PreventiveMaintenancePlan::query()->count())->toBe(4);
});

it('exports the raw tenant tables to csv', function () {
    app(GenerateTenantHistory::class)->execute(
        $this->tenant,
        config('predictive.demo.companies.0'),
    );

    $ticketCount = Ticket::query()->count();

    tenancy()->end();

    $path = storage_path('app/testing/predictive-raw');
    File::deleteDirectory($path);

    $this->artisan('predictive:export-raw', ['--output' => $path])
        ->assertSuccessful();

    foreach (['asset_cards', 'tickets', 'ticket_closures', 'preventive_executions', 'maintenance_categories', 'locations', 'service_levels'] as $table) {
        expect(File::exists($path.DIRECTORY_SEPARATOR.$table.'.csv'))->toBeTrue();
    }

    $assetRows = array_map('str_getcsv', file($path.DIRECTORY_SEPARATOR.'asset_cards.csv'));

    expect($assetRows[0][0])->toBe('tenant_id')
        ->and($assetRows[0][1])->toBe('id')
        ->and($assetRows)->toHaveCount(5);

    $ticketRows = array_map('str_getcsv', file($path.DIRECTORY_SEPARATOR.'tickets.csv'));

    expect($ticketRows)->toHaveCount($ticketCount + 1);

    File::deleteDirectory($path);
});
