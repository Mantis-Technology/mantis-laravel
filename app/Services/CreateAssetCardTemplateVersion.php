<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssetCardTemplate;
use App\Models\AssetCardTemplateSection;
use App\Models\CardTemplateVersion;
use Illuminate\Support\Facades\DB;

class CreateAssetCardTemplateVersion
{
    public function execute(AssetCardTemplate $template): int
    {
        return DB::transaction(function () use ($template): int {
            $nextVersion = (int) CardTemplateVersion::query()
                ->where('asset_card_template_id', $template->id)
                ->max('version') + 1;

            CardTemplateVersion::query()->create([
                'asset_card_template_id' => $template->id,
                'version' => $nextVersion,
            ]);

            $sections = AssetCardTemplateSection::query()
                ->where('asset_card_template_id', $template->id)
                ->where('version', $template->version)
                ->orderBy('order')
                ->get();

            foreach ($sections as $section) {
                AssetCardTemplateSection::query()->create([
                    'asset_card_template_id' => $template->id,
                    'version' => $nextVersion,
                    'name' => $section->name,
                    'description' => $section->description,
                    'order' => $section->order,
                    'columns' => $section->columns,
                    'fields' => $section->fields ?? [],
                ]);
            }

            $template->update(['version' => $nextVersion]);

            return $nextVersion;
        });
    }
}
