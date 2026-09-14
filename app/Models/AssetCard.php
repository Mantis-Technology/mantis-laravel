<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'asset_card_template_id',
    'version',
    'code',
    'qr_path',
    'data',
])]
class AssetCard extends Model
{
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<AssetCardTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AssetCardTemplate::class, 'asset_card_template_id');
    }

    public function templateVersion(): CardTemplateVersion
    {
        return CardTemplateVersion::query()
            ->where('asset_card_template_id', $this->asset_card_template_id)
            ->where('version', $this->version)
            ->firstOrFail();
    }
}
