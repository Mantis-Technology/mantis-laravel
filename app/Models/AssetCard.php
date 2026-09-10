<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'asset_card_template_id',
    'version',
    'code',
    'data',
])]
class AssetCard extends Model
{

    // Relationships
    public function template(): BelongsTo
    {
        return $this->belongsTo(
            AssetCardTemplate::class,
            'asset_card_template_id'
        );
    }
}
