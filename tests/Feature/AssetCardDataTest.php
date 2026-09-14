<?php

use App\Models\AssetCardTemplate;
use App\Models\AssetCardTemplateSection;
use App\Models\CardTemplateVersion;
use App\Services\AssetCardRules;
use App\Services\BuildAssetCardData;
use App\Services\TemplateSections;

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

function createTemplateSectionWith(array $fields): AssetCardTemplateSection
{
    $template = AssetCardTemplate::query()->create([
        'name' => 'Equipo',
        'version' => 1,
    ]);

    CardTemplateVersion::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
    ]);

    return AssetCardTemplateSection::query()->create([
        'asset_card_template_id' => $template->id,
        'version' => 1,
        'name' => 'Datos',
        'order' => 1,
        'columns' => 12,
        'fields' => $fields,
    ]);
}

function createTemplateSection(): AssetCardTemplateSection
{
    return createTemplateSectionWith([
        ['name' => 'title', 'label' => 'Título', 'type' => 'text', 'required' => true, 'order' => 1],
        ['name' => 'amount', 'label' => 'Monto', 'type' => 'number', 'required' => false, 'order' => 2],
        ['name' => 'active', 'label' => 'Activo', 'type' => 'checkbox', 'required' => false, 'order' => 3],
        [
            'name' => 'kind',
            'label' => 'Tipo',
            'type' => 'select',
            'required' => false,
            'order' => 4,
            'options' => [['value' => 'a', 'label' => 'A']],
        ],
    ]);
}

test('builds the asset card data with types and normalized values', function () {
    $section = createTemplateSection();
    $sections = app(TemplateSections::class)->forVersion(
        AssetCardTemplate::query()->findOrFail($section->asset_card_template_id),
        1,
    );

    $data = app(BuildAssetCardData::class)->execute($sections, [
        $section->getKey() => [
            'title' => 'Equipo A',
            'amount' => '12.5',
            'active' => true,
            'kind' => 'a',
            'unknown' => 'ignored',
        ],
    ]);

    expect($data)->toBe([
        $section->getKey() => [
            'title' => ['type' => 'text', 'value' => 'Equipo A'],
            'amount' => ['type' => 'number', 'value' => 12.5],
            'active' => ['type' => 'checkbox', 'value' => true],
            'kind' => ['type' => 'select', 'value' => 'a'],
        ],
    ]);
});

test('normalizes empty values, integers and missing fields', function () {
    $section = createTemplateSection();
    $sections = app(TemplateSections::class)->forVersion(
        AssetCardTemplate::query()->findOrFail($section->asset_card_template_id),
        1,
    );

    $data = app(BuildAssetCardData::class)->execute($sections, [
        $section->getKey() => [
            'title' => '',
            'amount' => '25',
        ],
    ]);

    expect($data[$section->getKey()]['title']['value'])->toBeNull()
        ->and($data[$section->getKey()]['amount']['value'])->toBe(25)
        ->and($data[$section->getKey()]['active']['value'])->toBeFalse()
        ->and($data[$section->getKey()]['kind']['value'])->toBeNull();
});

test('builds validation rules from the template sections', function () {
    $section = createTemplateSection();
    $sections = app(TemplateSections::class)->forVersion(
        AssetCardTemplate::query()->findOrFail($section->asset_card_template_id),
        1,
    );

    $rules = app(AssetCardRules::class)->forSections($sections);
    $prefix = "values.{$section->getKey()}";

    expect($rules)->toHaveKey("{$prefix}.title")
        ->and($rules["{$prefix}.title"])->toContain('required')
        ->and($rules["{$prefix}.title"])->toContain('string')
        ->and($rules["{$prefix}.amount"])->toContain('numeric')
        ->and($rules["{$prefix}.active"])->toContain('boolean')
        ->and($rules["{$prefix}.kind"])->toContain('string');
});

test('builds file rules and stores the uploaded path as value', function () {
    $section = createTemplateSectionWith([
        [
            'name' => 'attachment',
            'label' => 'Adjunto',
            'type' => 'file',
            'required' => true,
            'order' => 1,
            'mimeTypes' => ['application/pdf'],
            'maxFileSize' => 1048576,
        ],
    ]);

    $sections = app(TemplateSections::class)->forVersion(
        AssetCardTemplate::query()->findOrFail($section->asset_card_template_id),
        1,
    );

    $sectionId = $section->getKey();
    $rules = app(AssetCardRules::class)->forSections($sections);

    expect($rules)->toHaveKey("files.{$sectionId}.attachment")
        ->and($rules["files.{$sectionId}.attachment"])->toContain('file')
        ->and($rules["files.{$sectionId}.attachment"])->toContain('mimetypes:application/pdf')
        ->and($rules["files.{$sectionId}.attachment"])->toContain('max:1024')
        ->and($rules)->toHaveKey("remove_files.{$sectionId}.attachment");

    $data = app(BuildAssetCardData::class)->execute($sections, [
        $sectionId => ['attachment' => 'asset-cards/abc/document.pdf'],
    ]);

    expect($data[$sectionId]['attachment'])->toBe([
        'type' => 'file',
        'value' => 'asset-cards/abc/document.pdf',
    ]);
});
