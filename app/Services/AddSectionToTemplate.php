<?php

namespace App\Services;

use App\Dto\AddSectionToTemplateDto;
use App\Factories\Field;
use App\Models\AssetCardTemplate;

class AddSectionToTemplate
{
    public function execute(int $templateId, int $version, AddSectionToTemplateDto $sectionData): void
    {
        $template = AssetCardTemplate::query()->findOrFail($templateId);

        if ($template->versions()->where('version', $version)->doesntExist()) {
            throw new \Exception("Version {$version} does not exist for template ID {$templateId}");
        }

        $template->sections()->create([
            'version' => $version,
            'name' => $sectionData->name,
            'description' => $sectionData->description,
            'order' => $sectionData->order->value,
            'columns' => $sectionData->columns->value,
            'fields' => array_map(
                static fn (Field $field): array => $field->toArray(),
                $sectionData->fields
            ),
        ]);
    }
}
