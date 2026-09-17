<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'parent_id',
    'name',
    'description',
    'is_active',
])]
class Location extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('is_active', true);
        });

        static::updated(function (Location $location) {
            if ($location->wasChanged('is_active') && ! $location->is_active) {
                $location->loadMissing('children');

                static::query()
                    ->whereIn('id', $location->descendantIds())
                    ->update(['is_active' => false]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Location>  $query
     * @return Builder<Location>
     */
    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    /**
     * @param  Builder<Location>  $query
     * @return Builder<Location>
     */
    public function scopeOnlyInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active')->where('is_active', false);
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Location, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->with('children');
    }

    /**
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];

        $collect = function (self $location) use (&$collect, &$ids): void {
            foreach ($location->children as $child) {
                $ids[$child->id] = $child->id;

                $collect($child);
            }
        };

        $collect($this);

        return array_values($ids);
    }

    /**
     * Valida que una ubicación pueda ser asignada como padre.
     */
    public function validateParent(?int $parentId): ?string
    {
        if ($parentId === null) {
            return null;
        }

        if ($parentId === $this->id) {
            return 'Una ubicación no puede ser su propia ubicación padre.';
        }

        $parent = self::find($parentId);

        if (! $parent) {
            return 'La ubicación padre seleccionada no existe.';
        }

        $current = $parent;

        while ($current !== null && $current->parent_id !== null) {
            if ($current->parent_id === $this->id) {
                return 'No puedes seleccionar una ubicación descendiente como ubicación padre.';
            }

            $current = $current->parent;
        }

        return null;
    }
}