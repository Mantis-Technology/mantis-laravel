<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssetCardTemplate;
use App\Models\AssetCardTemplateSection;

class TemplateSections
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forVersion(AssetCardTemplate $template, int $version): array
    {
        return AssetCardTemplateSection::query()
            ->where('asset_card_template_id', $template->id)
            ->where('version', $version)
            ->orderBy('order')
            ->get()
            ->map(fn (AssetCardTemplateSection $section): array => [
                'id' => $section->getKey(),
                'name' => $section->name,
                'description' => $section->description,
                'order' => $section->order,
                'columns' => $section->columns,
                'fields' => $section->fields ?? [],
            ])
            ->values()
            ->all();
    }
}
