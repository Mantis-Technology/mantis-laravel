<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'asset_card_template_id',
    'version',
])]
class CardTemplateVersion extends Model
{

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(AssetCardTemplate::class, 'asset_card_template_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(AssetCard::class, 'asset_card_template_id', 'asset_card_template_id')
            ->where('version', $this->version);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AssetCardTemplateSection::class, 'asset_card_template_id', 'asset_card_template_id')
            ->where('version', $this->version);
    }
}
