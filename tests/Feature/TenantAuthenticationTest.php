<?php

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

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
        'session.driver' => 'database',
        'session.connection' => null,
    ]);

    $this->artisan('migrate', [
        '--database' => 'testing_central',
        '--force' => true,
    ])->assertSuccessful();

    $this->tenant = Tenant::create([
        'name' => 'Acme Auth',
        'contact_email' => 'ops@acme.test',
    ]);

    $this->tenant->update(['status' => TenantStatus::Active->value]);
});

afterEach(function () {
    tenancy()->end();

    $this->tenant?->delete();

    @unlink(database_path('testing_central.sqlite'));

    foreach (glob(database_path('tenant_*')) as $tenantDatabase) {
        @unlink($tenantDatabase);
    }
});

test('a tenant user logs in and reaches the dashboard without a redirect loop', function () {
    $domain = "http://{$this->tenant->id}.localhost";

    $login = $this->post("{$domain}/login", [
        'username' => 'admin',
        'password' => 'admin',
    ]);

    $login->assertRedirectContains('/dashboard');

    $sessionCookie = $login->getCookie(config('session.cookie'));

    expect($sessionCookie)->not->toBeNull()
        ->and(DB::connection('testing_central')->table('sessions')->count())->toBe(0)
        ->and(DB::connection('tenant')->table('sessions')->count())->toBe(1);

    $this->withCookie(config('session.cookie'), $sessionCookie->getValue())
        ->get("{$domain}/dashboard")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));

    $this->withCookie(config('session.cookie'), $sessionCookie->getValue())
        ->get("{$domain}/login")
        ->assertRedirectContains('/dashboard');
});
