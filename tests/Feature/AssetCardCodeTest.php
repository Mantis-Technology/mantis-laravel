<?php

use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Services\GenerateAssetCardCode;

beforeEach(function () {
    config([
        'database.default' => 'tenant_test',
        'database.connections.tenant_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    $this->artisan('migrate', [
        '--database' => 'tenant_test',
        '--path' => 'database/migrations/tenant',
    ])->assertSuccessful();
});

test('generates sequential codes from company and template initials', function () {
    $template = AssetCardTemplate::query()->create([
        'name' => 'Equipo de cómputo',
        'version' => 1,
    ]);

    CardTemplateVersion::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
    ]);

    $service = app(GenerateAssetCardCode::class);

    expect($service->execute('Inversiones Duquin SAS', $template))
        ->toBe('IDS-EC-001');

    AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'IDS-EC-001',
    ]);

    expect($service->execute('Inversiones Duquin SAS', $template))
        ->toBe('IDS-EC-002');
});
