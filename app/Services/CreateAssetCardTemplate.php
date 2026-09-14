<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssetCardTemplate;
use App\Models\CardTemplateVersion;
use Illuminate\Support\Facades\DB;

class CreateAssetCardTemplate
{
    public function execute(string $name, ?string $description): AssetCardTemplate
    {
        return DB::transaction(function () use ($name, $description): AssetCardTemplate {
            $template = AssetCardTemplate::query()->create([
                'name' => $name,
                'description' => $description,
                'version' => 1,
            ]);

            CardTemplateVersion::query()->create([
                'asset_card_template_id' => $template->id,
                'version' => 1,
            ]);

            return $template;
        });
    }
}
