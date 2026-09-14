<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'version',
])]
class AssetCardTemplate extends Model
{
    

    // Relationships
    public function sections(): HasMany
    {
        return $this->hasMany(AssetCardTemplateSection::class, 'asset_card_template_id', 'id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(AssetCard::class, 'asset_card_template_id', 'id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CardTemplateVersion::class, 'asset_card_template_id', 'id');
    }
}
