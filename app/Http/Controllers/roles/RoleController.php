<?php

declare(strict_types=1);

namespace App\Http\Controllers\roles;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render(
            'Roles/index',
            [
                'roles' => SpatieRole::query()
                    ->with('permissions')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (SpatieRole $role): array => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'permissions' => $role->permissions
                            ->pluck('name')
                            ->values()
                            ->all(),
                        'is_protected' => $role->name === Role::TENANT_ADMINISTRATOR->value,
                    ])
                    ->values()
                    ->all(),
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create/index', [
            'action' => route('roles.store'),
            'permissions' => $this->permissionsList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $role = SpatieRole::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(array_map('intval', $validated['permissions'] ?? []));

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol creado correctamente.');
    }

    public function edit(int $id): Response
    {
        $role = SpatieRole::findOrFail($id);

        return Inertia::render('Roles/Edit/index', [
            'action' => route('roles.update', $role->id),
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_protected' => $role->name === Role::TENANT_ADMINISTRATOR->value,
            ],
            'permission_ids' => $role->permissions->pluck('id')->all(),
            'permissions' => $this->permissionsList(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $role = SpatieRole::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($role->id)
                    ->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions(array_map('intval', $validated['permissions'] ?? []));

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $role = SpatieRole::findOrFail($id);

        if ($role->name === Role::TENANT_ADMINISTRATOR->value) {
            return back()->with(
                'error',
                'El rol de administrador del tenant no puede eliminarse.'
            );
        }

        DB::table('model_has_roles')->where('role_id', $role->id)->delete();
        DB::table('role_has_permissions')->where('role_id', $role->id)->delete();

        $role->delete();

        return back()->with('success', 'Rol eliminado correctamente.');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function permissionsList(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Permission $permission): array => [
                'id' => (int) $permission->id,
                'name' => $permission->name,
            ])
            ->all();
    }
}
