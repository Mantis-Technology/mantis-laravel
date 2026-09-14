<?php

use App\Models\AssetCardTemplate;
use App\Models\AssetCardTemplateSection;
use App\Models\CardTemplateVersion;
use App\Services\SyncAssetCardTemplateSections;

test('syncs template sections and normalizes their fields', function () {
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

    $template = AssetCardTemplate::query()->create([
        'name' => 'Plantilla de activos',
        'description' => 'Demo',
        'version' => 1,
    ]);

    CardTemplateVersion::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
    ]);

    $existing = AssetCardTemplateSection::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'name' => 'Original',
        'columns' => 12,
        'order' => 1,
    ]);

    $removed = AssetCardTemplateSection::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'name' => 'Se elimina',
        'columns' => 12,
        'order' => 2,
    ]);

    app(SyncAssetCardTemplateSections::class)->execute($template, 1, [
        [
            'id' => $existing->getKey(),
            'name' => 'Datos generales',
            'description' => 'Sección principal',
            'columns' => 6,
            'fields' => [
                [
                    'name' => 'title',
                    'label' => 'Título',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Ingresa el título',
                    'order' => 9,
                ],
                [
                    'name' => 'amount',
                    'label' => 'Monto',
                    'type' => 'number',
                    'required' => false,
                    'order' => 9,
                    'min' => 1,
                    'max' => 10,
                ],
            ],
        ],
        [
            'name' => 'Nueva sección',
            'description' => null,
            'columns' => 6,
            'fields' => [
                [
                    'name' => 'color',
                    'label' => 'Color',
                    'type' => 'select',
                    'required' => false,
                    'order' => 4,
                    'options' => [
                        ['value' => 'red', 'label' => 'Rojo'],
                    ],
                ],
            ],
        ],
    ]);

    $sections = AssetCardTemplateSection::query()
        ->where('asset_card_template_id', $template->id)
        ->where('version', 1)
        ->orderBy('order')
        ->get();

    expect($sections)->toHaveCount(2)
        ->and(
            $sections
                ->map(fn (AssetCardTemplateSection $section): string => $section->getKey())
                ->all(),
        )->not->toContain($removed->getKey())
        ->and($sections[0]->getKey())->toBe($existing->getKey())
        ->and($sections[0]->name)->toBe('Datos generales')
        ->and($sections[0]->description)->toBe('Sección principal')
        ->and($sections[0]->columns)->toBe(6)
        ->and($sections[0]->order)->toBe(1)
        ->and($sections[1]->name)->toBe('Nueva sección')
        ->and($sections[1]->order)->toBe(2)
        ->and($sections[1]->columns)->toBe(6);

    expect($sections[0]->fields)->toBe([
        [
            'name' => 'title',
            'label' => 'Título',
            'type' => 'text',
            'required' => true,
            'order' => 1,
            'placeholder' => 'Ingresa el título',
        ],
        [
            'name' => 'amount',
            'label' => 'Monto',
            'type' => 'number',
            'required' => false,
            'order' => 2,
            'min' => 1,
            'max' => 10,
        ],
    ]);

    expect($sections[1]->fields)->toBe([
        [
            'name' => 'color',
            'label' => 'Color',
            'type' => 'select',
            'required' => false,
            'order' => 1,
            'options' => [
                ['value' => 'red', 'label' => 'Rojo'],
            ],
        ],
    ]);
});
