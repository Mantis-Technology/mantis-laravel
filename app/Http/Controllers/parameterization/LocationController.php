<?php

namespace App\Http\Controllers\parameterization;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * @param  Collection<int, Location>  $locations
     * @param  callable(Location): array<string, mixed>  $node
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(Collection $locations, callable $node): array
    {
        $childrenMap = $locations->groupBy(
            fn (Location $location) => $location->parent_id ?? 0
        );

        $build = function (array $parents) use (&$build, $childrenMap, $node): array {
            $nodes = [];

            foreach ($parents as $location) {
                $nodes[] = $node($location) + [
                    'children' => $build(
                        $childrenMap->get($location->id, collect())->all()
                    ),
                ];
            }

            return $nodes;
        };

        return $build($childrenMap->get(0, collect())->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parentLocations(): array
    {
        return $this->buildTree(
            Location::query()
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
            static fn (Location $location): array => [
                'id' => $location->id,
                'name' => $location->name,
                'parent_id' => $location->parent_id,
            ],
        );
    }

    /**
     * @return callable(Location): array<string, mixed>
     */
    private function locationNode(): callable
    {
        return static fn (Location $location): array => [
            'id' => $location->id,
            'parent_id' => $location->parent_id,
            'name' => $location->name,
            'description' => $location->description,
            'is_active' => $location->is_active,
            'created_at' => $location->created_at,
            'updated_at' => $location->updated_at,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function indexTree(): array
    {
        return $this->buildTree(
            Location::query()
                ->withInactive()
                ->orderBy('name')
                ->get(),
            $this->locationNode(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activeTree(): array
    {
        return $this->buildTree(
            Location::query()->orderBy('name')->get(),
            $this->locationNode(),
        );
    }

    private function canViewInactiveLocations(): bool
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
            'Parameterization/Locations/index',
            [
                'locations' => $this->canViewInactiveLocations()
                    ? $this->indexTree()
                    : $this->activeTree(),
                'can_toggle_active' => $this->canViewInactiveLocations(),
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render(
            'Parameterization/Locations/Create/index',
            [
                'action' => route('parameterization.locations.store'),
                'parent_locations' => $this->parentLocations(),
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
                Rule::exists('locations', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Location::create($validated);

        return redirect()
            ->route('parameterization.locations.index')
            ->with('success', 'Ubicación creada correctamente.');
    }

    public function edit(int $id): Response
    {
        $location = Location::withInactive()->findOrFail($id);

        $location->load('children');

        $disabledParentIds = [
            $location->id,
            ...$location->descendantIds(),
        ];

        return Inertia::render(
            'Parameterization/Locations/Edit/index',
            [
                'location' => $location,
                'action' => route('parameterization.locations.update', $location->id),
                'parent_locations' => $this->parentLocations(),
                'disabled_parent_ids' => array_values(array_unique($disabledParentIds)),
            ]
        );
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $location = Location::withInactive()->findOrFail($id);

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if ($error = $location->validateParent($validated['parent_id'] ?? null)) {
            return back()->withErrors(['parent_id' => $error])->withInput();
        }

        $location->update($validated);

        return redirect()
            ->route('parameterization.locations.index')
            ->with('success', 'Ubicación actualizada correctamente.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $location = Location::withInactive()->findOrFail($id);

        $location->update(['is_active' => ! $location->is_active]);

        return back()->with(
            'success',
            $location->is_active
                ? 'Ubicación activada correctamente.'
                : 'Ubicación desactivada correctamente.'
        );
    }

    public function destroy(int $id): RedirectResponse
    {
        $location = Location::withInactive()->findOrFail($id);

        $location->delete();

        return redirect()
            ->route('parameterization.locations.index')
            ->with('success', 'Ubicación eliminada correctamente.');
    }
}