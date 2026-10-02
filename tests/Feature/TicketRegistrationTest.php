<?php

use App\Enums\TenantStatus;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Models\Location;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Services\GenerateAssetCardQrCode;
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
    ]);

    $this->artisan('migrate', [
        '--database' => 'testing_central',
        '--force' => true,
    ])->assertSuccessful();

    $this->tenant = Tenant::create([
        'name' => 'Acme Test',
        'contact_email' => 'ops@acme.test',
    ]);

    $this->tenant->update(['status' => TenantStatus::Active->value]);

    tenancy()->initialize($this->tenant);

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
        'code' => 'IDS-EC-001',
    ]);

    $this->location = Location::query()->create(['name' => 'Planta Principal']);

    $this->user = User::factory()->create();
});

afterEach(function () {
    tenancy()->end();

    $this->tenant?->delete();

    @unlink(database_path('testing_central.sqlite'));
});

test('the report form pre-resolves the asset when coming from its QR code', function () {
    $response = $this
        ->actingAs($this->user)
        ->get("http://{$this->tenant->id}.localhost/tickets/create?asset=IDS-EC-001");

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Tickets/Create/index')
        ->where('assetCard.id', $this->assetCard->id)
        ->where('assetCard.code', 'IDS-EC-001')
        ->has('assetCards', 0)
        ->has('locations')
    );
});

test('the report form lists the assets to choose from when no QR code is used', function () {
    $response = $this
        ->actingAs($this->user)
        ->get("http://{$this->tenant->id}.localhost/tickets/create");

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Tickets/Create/index')
        ->where('assetCard', null)
        ->has('assetCards', 1)
        ->where('assetCards.0.id', $this->assetCard->id)
        ->where('assetCards.0.code', 'IDS-EC-001')
    );
});

test('guests cannot access the incident report form', function () {
    $this
        ->get("http://{$this->tenant->id}.localhost/tickets/create")
        ->assertRedirectContains('/login');

    $this
        ->post("http://{$this->tenant->id}.localhost/tickets", [])
        ->assertRedirectContains('/login');

    expect(Ticket::query()->count())->toBe(0);
});

test('the report form shows a 404 for an unknown asset code', function () {
    $this
        ->actingAs($this->user)
        ->get("http://{$this->tenant->id}.localhost/tickets/create?asset=NOPE-001")
        ->assertNotFound();
});

test('a user can report an incident for an asset', function () {
    $response = $this
        ->actingAs($this->user)
        ->post("http://{$this->tenant->id}.localhost/tickets", [
            'asset_card_id' => $this->assetCard->id,
            'title' => 'El equipo no enciende',
            'description' => 'Desde el turno de la manana el equipo quedó apagado.',
            'location_id' => $this->location->id,
        ]);

    $response->assertRedirectContains('/dashboard')
        ->assertSessionHas('success', 'Incidencia reportada correctamente.');

    $ticket = Ticket::query()->firstOrFail();

    expect($ticket->status)->toBe(TicketStatus::Reported)
        ->and($ticket->asset_card_id)->toBe($this->assetCard->id)
        ->and($ticket->reported_by)->toBe($this->user->id)
        ->and($ticket->location_id)->toBe($this->location->id)
        ->and($ticket->description)->toBe('Desde el turno de la manana el equipo quedó apagado.');
});

test('reporting an incident requires the asset, title and description', function () {
    $response = $this
        ->actingAs($this->user)
        ->from("http://{$this->tenant->id}.localhost/tickets/create")
        ->post("http://{$this->tenant->id}.localhost/tickets", []);

    $response->assertSessionHasErrors(['asset_card_id', 'title', 'description']);

    expect(Ticket::query()->count())->toBe(0);
});

test('the asset QR code points to the incident report form for that asset', function () {
    $url = app(GenerateAssetCardQrCode::class)->reportUrl($this->assetCard);

    expect($url)->toContain('/tickets/create')
        ->and($url)->toContain('asset=IDS-EC-001');
});
