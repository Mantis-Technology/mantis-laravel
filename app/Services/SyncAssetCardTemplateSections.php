<?php

declare(strict_types=1);

namespace App\Services;

use App\Factories\FieldFactory;
use App\Models\AssetCardTemplate;
use App\Models\AssetCardTemplateSection;
use Illuminate\Support\Facades\DB;

class SyncAssetCardTemplateSections
{
    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function execute(AssetCardTemplate $template, int $version, array $sections): void
    {
        DB::transaction(function () use ($template, $version, $sections): void {
            $existing = AssetCardTemplateSection::query()
                ->where('asset_card_template_id', $template->id)
                ->where('version', $version)
                ->get()
                ->keyBy(fn (AssetCardTemplateSection $section): string => $section->getKey());

            $keptIds = [];

            foreach ($sections as $index => $sectionData) {
                $fields = [];
                $fieldOrder = 1;
                $sectionFields = $sectionData['fields'] ?? [];

                if (is_array($sectionFields)) {
                    foreach ($sectionFields as $fieldData) {
                        if (! is_array($fieldData)) {
                            continue;
                        }

                        $fields[] = FieldFactory::createField([...$fieldData, 'order' => $fieldOrder])->toArray();
                        $fieldOrder += 1;
                    }
                }

                $attributes = [
                    'name' => $sectionData['name'],
                    'description' => $sectionData['description'] ?? null,
                    'order' => $index + 1,
                    'columns' => $sectionData['columns'],
                    'fields' => $fields,
                ];

                $id = $sectionData['id'] ?? null;
                $existingSection = is_string($id) ? $existing->get($id) : null;

                if ($existingSection instanceof AssetCardTemplateSection) {
                    $existingSection->update($attributes);
                    $keptIds[] = $existingSection->getKey();

                    continue;
                }

                $createdSection = $template->sections()->create([
                    'version' => $version,
                    ...$attributes,
                ]);

                $keptIds[] = $createdSection->getKey();
            }

            $template->sections()
                ->where('version', $version)
                ->whereNotIn('ulid', $keptIds)
                ->delete();
        });
    }
}
