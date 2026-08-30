<?php

declare(strict_types=1);

namespace App\Http\Controllers\permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class PermissionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render(
            'Permissions/index',
            [
                'permissions' => Permission::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Permission $permission): array => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'guard_name' => $permission->guard_name,
                        'roles_count' => $permission->roles()->count(),
                    ])
                    ->values()
                    ->all(),
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render('Permissions/Create/index', [
            'action' => route('permissions.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')->where('guard_name', 'web'),
            ],
        ]);

        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso creado correctamente.');
    }

    public function edit(int $id): Response
    {
        $permission = Permission::findOrFail($id);

        return Inertia::render('Permissions/Edit/index', [
            'action' => route('permissions.update', $permission->id),
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
            ],
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->ignore($permission->id)
                    ->where('guard_name', 'web'),
            ],
        ]);

        $permission->update(['name' => $validated['name']]);

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso actualizado correctamente.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $permission = Permission::findOrFail($id);

        SpatieRole::query()->each(function (SpatieRole $role) use ($permission): void {
            $role->permissions()->detach($permission->id);
        });

        DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();

        $permission->delete();

        return back()->with('success', 'Permiso eliminado correctamente.');
    }
}
