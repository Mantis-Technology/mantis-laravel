<?php

namespace App\Http\Controllers\parameterization;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceCategoryController extends Controller
{
    /**
     * Nests the given categories into a tree keyed by their `parent_id`.
     *
     * @param  Collection<int, MaintenanceCategory>  $categories
     * @param  callable(MaintenanceCategory): array<string, mixed>  $node
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(Collection $categories, callable $node): array
    {
        $childrenMap = $categories->groupBy(
            fn (MaintenanceCategory $category) => $category->parent_id ?? 0
        );

        $build = function (array $parents) use (&$build, $childrenMap, $node): array {
            $nodes = [];

            foreach ($parents as $category) {
                $nodes[] = $node($category) + [
                    'children' => $build(
                        $childrenMap->get($category->id, collect())->all()
                    ),
                ];
            }

            return $nodes;
        };

        return $build($childrenMap->get(0, collect())->all());
    }

    /**
     * Only active categories are offered as parents (inactive ones are
     * excluded by the model's global scope, like soft deletes).
     *
     * @return array<int, array<string, mixed>>
     */
    private function parentCategories(): array
    {
        return $this->buildTree(
            MaintenanceCategory::query()
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
            static fn (MaintenanceCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
            ],
        );
    }

    /**
     * @return callable(MaintenanceCategory): array<string, mixed>
     */
    private function categoryNode(): callable
    {
        return static fn (MaintenanceCategory $category): array => [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'description' => $category->description,
            'is_active' => $category->is_active,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];
    }

    /**
     * The index is an administration list, so it includes inactive
     * ("soft deleted") categories to allow reactivation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function indexTree(): array
    {
        return $this->buildTree(
            MaintenanceCategory::query()
                ->withInactive()
                ->orderBy('name')
                ->get(),
            $this->categoryNode(),
        );
    }

    /**
     * Árbol con solo categorías activas (comportamiento por defecto del
     * soft delete), para usuarios sin acceso a las desactivadas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function activeTree(): array
    {
        return $this->buildTree(
            MaintenanceCategory::query()->orderBy('name')->get(),
            $this->categoryNode(),
        );
    }

    /**
     * Quién puede ver las categorías desactivadas ("soft-deleted"): el
     * administrador del tenant y el líder de mantenimientos.
     */
    private function canViewInactiveCategories(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasAnyRole([
            Role::TENANT_ADMINISTRATOR->value,
            Role::MAINTENANCE_CHIEF->value,
        ]);
    }

    public function index(Request $request): Response
    {
        return Inertia::render(
            'Parameterization/MaintenanceCategories/index',
            [
                'maintenance_categories' => $this->canViewInactiveCategories()
                    ? $this->indexTree()
                    : $this->activeTree(),
                'can_toggle_active' => $this->canViewInactiveCategories(),
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'Parameterization/MaintenanceCategories/Create/index',
            [
                'action' => route(
                    'parameterization.maintenance-categories.store'
                ),

                'parent_categories' => $this->parentCategories(),

                'disabled_parent_ids' => [],
            ]
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('maintenance_categories', 'id')->where(
                    'is_active',
                    true
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        MaintenanceCategory::create($validated);

        return redirect()
            ->route('parameterization.maintenance-categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(int $id): Response
    {
        $maintenanceCategory = MaintenanceCategory::withInactive()->findOrFail($id);

        $maintenanceCategory->load('children');

        $disabledParentIds = [
            $maintenanceCategory->id,
            ...$maintenanceCategory->descendantIds(),
        ];

        return Inertia::render(
            'Parameterization/MaintenanceCategories/Edit/index',
            [
                'maintenance_category' => $maintenanceCategory,

                'action' => route(
                    'parameterization.maintenance-categories.update',
                    $maintenanceCategory->id
                ),

                'parent_categories' => $this->parentCategories(),

                'disabled_parent_ids' => array_values(
                    array_unique($disabledParentIds)
                ),
            ]
        );
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $maintenanceCategory = MaintenanceCategory::withInactive()->findOrFail($id);

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('maintenance_categories', 'id')->where(
                    'is_active',
                    true
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if ($error = $maintenanceCategory->validateParent(
            $validated['parent_id'] ?? null
        )) {
            return back()
                ->withErrors([
                    'parent_id' => $error,
                ])
                ->withInput();
        }

        $maintenanceCategory->update($validated);

        return redirect()
            ->route('parameterization.maintenance-categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $maintenanceCategory = MaintenanceCategory::withInactive()->findOrFail($id);

        $maintenanceCategory->update([
            'is_active' => ! $maintenanceCategory->is_active,
        ]);

        return back()->with(
            'success',
            $maintenanceCategory->is_active
                ? 'Categoría activada correctamente.'
                : 'Categoría desactivada correctamente.'
        );
    }

    public function destroy(int $id): RedirectResponse
    {
        $maintenanceCategory = MaintenanceCategory::withInactive()->findOrFail($id);

        $maintenanceCategory->delete();

        return redirect()
            ->route('parameterization.maintenance-categories.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}
