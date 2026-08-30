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
class MaintenanceCategory extends Model
{
    protected static function booted(): void
    {
        // `is_active` behaves like a soft delete: inactive categories are
        // excluded from every query unless explicitly included.
        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('is_active', true);
        });

        static::updated(function (MaintenanceCategory $category) {
            if ($category->wasChanged('is_active') && ! $category->is_active) {
                $category->loadMissing('children');

                static::query()
                    ->whereIn('id', $category->descendantIds())
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
     * Includes inactive ("soft deleted") categories in the query, like
     * SoftDeletes' `withTrashed`.
     *
     * @param  Builder<MaintenanceCategory>  $query
     * @return Builder<MaintenanceCategory>
     */
    public function scopeWithInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active');
    }

    /**
     * Retrieves only inactive ("soft deleted") categories, like SoftDeletes'
     * `onlyTrashed`.
     *
     * @param  Builder<MaintenanceCategory>  $query
     * @return Builder<MaintenanceCategory>
     */
    public function scopeOnlyInactive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('active')->where('is_active', false);
    }

    /**
     * @return BelongsTo<MaintenanceCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MaintenanceCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->with('children');
    }

    /**
     * Ids of every descendant of this category (all levels deep).
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [];

        $collect = function (self $category) use (&$collect, &$ids): void {
            foreach ($category->children as $child) {
                $ids[$child->id] = $child->id;

                $collect($child);
            }
        };

        $collect($this);

        return array_values($ids);
    }

    /**
     * Valida que una categoría pueda ser asignada como padre.
     */
    public function validateParent(?int $parentId): ?string
    {
        if ($parentId === null) {
            return null;
        }

        // Una categoría no puede ser su propio padre.
        if ($parentId === $this->id) {
            return 'Una categoría no puede ser su propia categoría padre.';
        }

        $parent = self::find($parentId);

        if (! $parent) {
            return 'La categoría padre seleccionada no existe.';
        }

        // El padre no puede ser un descendiente de esta categoría.
        $current = $parent;

        while ($current !== null && $current->parent_id !== null) {
            if ($current->parent_id === $this->id) {
                return 'No puedes seleccionar una categoría descendiente como categoría padre.';
            }

            $current = $current->parent;
        }

        return null;
    }
}
