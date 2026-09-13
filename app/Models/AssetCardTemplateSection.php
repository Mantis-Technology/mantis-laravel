<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'asset_card_template_id',
    'version',
    'name',
    'description',
    'order',
    'columns',
    'fields',
])]
class AssetCardTemplateSection extends Model
{
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'order' => 'integer',
            'columns' => 'integer',
            'fields' => 'array',
        ];
    }

    // Relationships
    public function templateVersion(): CardTemplateVersion
    {
        return CardTemplateVersion::query()
            ->where('asset_card_template_id', $this->asset_card_template_id)
            ->where('version', $this->version)
            ->firstOrFail();
    }
}
