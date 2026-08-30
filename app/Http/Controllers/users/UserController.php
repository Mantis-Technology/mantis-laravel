<?php

declare(strict_types=1);

namespace App\Http\Controllers\users;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render(
            'Users/index',
            [
                'users' => User::withTrashed()
                    ->with('roles')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (User $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'roles' => $user->roles->pluck('name')->values()->all(),
                        'is_active' => ! $user->trashed(),
                        'created_at' => $user->created_at,
                    ])
                    ->values()
                    ->all(),
            ]
        );
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create/index', [
            'action' => route('users.store'),
            'roles' => $this->rolesList(),
            'permissions' => $this->permissionsList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'confirmed', Password::default()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles(array_map('intval', $validated['roles'] ?? []));
        $user->syncPermissions(array_map('intval', $validated['permissions'] ?? []));

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(int $id): Response
    {
        $user = User::withTrashed()->findOrFail($id);

        return Inertia::render('Users/Edit/index', [
            'action' => route('users.update', $user->id),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_active' => ! $user->trashed(),
            ],
            'role_ids' => $user->roles->pluck('id')->all(),
            'permission_ids' => $user->getAllPermissions()->pluck('id')->all(),
            'roles' => $this->rolesList(),
            'permissions' => $this->permissionsList(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed', Password::default()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $isSelf = $request->user()->id === $user->id;
        $roles = array_map('intval', $validated['roles'] ?? []);

        if ($isSelf && ! in_array($this->adminRoleId(), $roles)) {
            return back()->withErrors([
                'roles' => 'No puedes quitarte el rol de administrador a ti mismo.',
            ]);
        }

        $user->fill([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $user->syncRoles($roles);
        $user->syncPermissions(array_map('intval', $validated['permissions'] ?? []));

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($request->user()->id === $user->id) {
            return back()->with(
                'error',
                'No puedes desactivar tu propia cuenta.'
            );
        }

        $user->delete();

        return back()->with('success', 'Usuario desactivado correctamente.');
    }

    public function restore(int $id): RedirectResponse
    {
        User::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Usuario reactivado correctamente.');
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function rolesList(): array
    {
        return \Spatie\Permission\Models\Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($role): array => [
                'id' => $role->id,
                'name' => $role->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function permissionsList(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
            ])
            ->all();
    }

    private function adminRoleId(): int
    {
        return (int) \Spatie\Permission\Models\Role::query()
            ->where('name', Role::TENANT_ADMINISTRATOR->value)
            ->value('id');
    }
}
