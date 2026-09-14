<?php

use App\Models\AssetCardTemplateSection;
use App\Models\CardTemplateVersion;
use App\Services\CreateAssetCardTemplate;
use App\Services\CreateAssetCardTemplateVersion;

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

test('creates a template with its first version and no sections', function () {
    $template = app(CreateAssetCardTemplate::class)->execute(
        'Equipo de cómputo',
        'Demo',
    );

    expect($template->version)->toBe(1)
        ->and($template->name)->toBe('Equipo de cómputo')
        ->and($template->description)->toBe('Demo')
        ->and(
            CardTemplateVersion::query()
                ->where('asset_card_template_id', $template->id)
                ->where('version', 1)
                ->exists(),
        )->toBeTrue()
        ->and(
            AssetCardTemplateSection::query()
                ->where('asset_card_template_id', $template->id)
                ->count(),
        )->toBe(0);
});

test('creates a new version cloning the current sections', function () {
    $template = app(CreateAssetCardTemplate::class)->execute('Equipo', null);

    $section = AssetCardTemplateSection::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'name' => 'Datos generales',
        'description' => 'Sección principal',
        'order' => 1,
        'columns' => 6,
        'fields' => [
            [
                'name' => 'title',
                'label' => 'Título',
                'type' => 'text',
                'required' => true,
                'order' => 1,
            ],
        ],
    ]);

    $newVersion = app(CreateAssetCardTemplateVersion::class)->execute($template);

    expect($newVersion)->toBe(2)
        ->and($template->fresh()?->version)->toBe(2)
        ->and(
            CardTemplateVersion::query()
                ->where('asset_card_template_id', $template->id)
                ->count(),
        )->toBe(2);

    $cloned = AssetCardTemplateSection::query()
        ->where('asset_card_template_id', $template->id)
        ->where('version', 2)
        ->first();

    expect($cloned)->not->toBeNull()
        ->and($cloned->name)->toBe('Datos generales')
        ->and($cloned->description)->toBe('Sección principal')
        ->and($cloned->columns)->toBe(6)
        ->and($cloned->order)->toBe(1)
        ->and($cloned->fields)->toBe($section->fields)
        ->and($cloned->getKey())->not->toBe($section->getKey());

    expect(
        AssetCardTemplateSection::query()
            ->where('asset_card_template_id', $template->id)
            ->where('version', 1)
            ->count(),
    )->toBe(1);
});
