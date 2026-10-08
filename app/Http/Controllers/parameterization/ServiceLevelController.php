<?php

namespace App\Http\Controllers\parameterization;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceCategory;
use App\Models\ServiceLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceLevelController extends Controller
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function serviceLevels(bool $includeInactive): array
    {
        $query = ServiceLevel::query()->with('maintenanceCategory:id,name');

        if ($includeInactive) {
            $query->withInactive();
        }

        return $query
            ->orderBy('maintenance_category_id')
            ->orderBy('maintenance_type')
            ->orderBy('priority')
            ->get()
            ->map(fn (ServiceLevel $level): array => $this->payload($level))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ServiceLevel $level): array
    {
        return [
            'id' => $level->id,
            'maintenance_category_id' => $level->maintenance_category_id,
            'maintenance_category' => $level->maintenanceCategory ? [
                'id' => $level->maintenanceCategory->id,
                'name' => $level->maintenanceCategory->name,
            ] : null,
            'maintenance_type' => $level->maintenance_type?->value,
            'maintenance_type_label' => $level->maintenance_type?->label(),
            'priority' => $level->priority?->value,
            'priority_label' => $level->priority?->label(),
            'response_hours' => $level->response_hours,
            'resolution_hours' => $level->resolution_hours,
            'is_active' => $level->is_active,
            'created_at' => $level->created_at?->toIso8601String(),
            'updated_at' => $level->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Active categories, nested by parent for an indented select.
     *
     * @return array<int, array<string, mixed>>
     */
    private function maintenanceCategoryOptions(): array
    {
        $categories = MaintenanceCategory::query()
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $childrenMap = $categories->groupBy(
            fn (MaintenanceCategory $category) => $category->parent_id ?? 0
        );

        $build = function (array $parents) use (&$build, $childrenMap): array {
            $nodes = [];

            foreach ($parents as $category) {
                $nodes[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'children' => $build(
                        $childrenMap->get($category->id, new Collection)->all()
                    ),
                ];
            }

            return $nodes;
        };

        return $build($childrenMap->get(0, new Collection)->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function maintenanceTypeOptions(): array
    {
        return array_map(
            fn (MaintenanceType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            MaintenanceType::cases(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function priorityOptions(): array
    {
        return array_map(
            fn (MaintenancePriority $priority): array => [
                'value' => $priority->value,
                'label' => $priority->label(),
            ],
            MaintenancePriority::cases(),
        );
    }

    /**
     * Quién puede ver las reglas desactivadas: el administrador del tenant y
     * el líder de mantenimientos.
     */
    private function canViewInactive(): bool
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

    public function index(): Response
    {
        return Inertia::render('Parameterization/ServiceLevels/index', [
            'service_levels' => $this->serviceLevels($this->canViewInactive()),
            'can_toggle_active' => $this->canViewInactive(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Parameterization/ServiceLevels/Create/index', [
            'action' => route('parameterization.service-levels.store'),
            'maintenance_categories' => $this->maintenanceCategoryOptions(),
            'maintenance_types' => $this->maintenanceTypeOptions(),
            'priorities' => $this->priorityOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        ServiceLevel::query()->create($validated);

        return redirect()
            ->route('parameterization.service-levels.index')
            ->with('success', 'Nivel de servicio creado correctamente.');
    }

    public function edit(int $id): Response
    {
        $serviceLevel = ServiceLevel::query()->withInactive()->findOrFail($id);

        return Inertia::render('Parameterization/ServiceLevels/Edit/index', [
            'service_level' => $this->payload($serviceLevel),
            'action' => route('parameterization.service-levels.update', $serviceLevel->id),
            'maintenance_categories' => $this->maintenanceCategoryOptions(),
            'maintenance_types' => $this->maintenanceTypeOptions(),
            'priorities' => $this->priorityOptions(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $serviceLevel = ServiceLevel::query()->withInactive()->findOrFail($id);

        $serviceLevel->update($this->validated($request));

        return redirect()
            ->route('parameterization.service-levels.index')
            ->with('success', 'Nivel de servicio actualizado correctamente.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $serviceLevel = ServiceLevel::query()->withInactive()->findOrFail($id);

        $serviceLevel->update([
            'is_active' => ! $serviceLevel->is_active,
        ]);

        return back()->with(
            'success',
            $serviceLevel->is_active
                ? 'Nivel de servicio activado correctamente.'
                : 'Nivel de servicio desactivado correctamente.'
        );
    }

    public function destroy(int $id): RedirectResponse
    {
        $serviceLevel = ServiceLevel::query()->withInactive()->findOrFail($id);

        $serviceLevel->delete();

        return redirect()
            ->route('parameterization.service-levels.index')
            ->with('success', 'Nivel de servicio eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'maintenance_category_id' => [
                'nullable',
                'integer',
                Rule::exists('maintenance_categories', 'id')->where(
                    'is_active',
                    true
                ),
            ],
            'maintenance_type' => ['nullable', Rule::enum(MaintenanceType::class)],
            'priority' => ['nullable', Rule::enum(MaintenancePriority::class)],
            'response_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'resolution_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
