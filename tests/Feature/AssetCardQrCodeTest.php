<?php

use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use App\Services\GenerateAssetCardQrCode;
use Illuminate\Support\Facades\Storage;

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

test('generates and stores a qr code for an asset card', function () {
    Storage::fake('local');

    $template = AssetCardTemplate::query()->create([
        'name' => 'Equipo',
        'version' => 1,
    ]);

    CardTemplateVersion::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
    ]);

    $assetCard = AssetCard::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'code' => 'AC-001',
    ]);

    $path = app(GenerateAssetCardQrCode::class)->execute($assetCard);

    expect($path)->toBe("asset-cards/{$assetCard->id}/qr.svg");

    Storage::disk('local')->assertExists($path);

    expect(Storage::disk('local')->get($path))
        ->toContain('<svg')
        ->and($assetCard->fresh()?->qr_path)->toBe($path);
});
