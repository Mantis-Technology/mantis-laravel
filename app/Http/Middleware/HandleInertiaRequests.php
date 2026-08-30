<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenant = tenant();

        $user = $request->user();

        $authUser = null;
        if ($user instanceof User) {
            $authUser = [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar ?? null,
                'roles' => $user->getRoleNames()->all(),
                'email_verified_at' => $user->email_verified_at,
            ];
        }

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $authUser,
            ],

            'tenant' => $tenant
                ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'logo' => $tenant->meta['image'] ?? null,
                    'description' => $tenant->meta['description'] ?? null,
                ]
                : null,

            'maintenance_categories' => MaintenanceCategory::all()->map(function (MaintenanceCategory $category): array {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                        'is_active' => $category->is_active,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                }),

        ];
    }
}
