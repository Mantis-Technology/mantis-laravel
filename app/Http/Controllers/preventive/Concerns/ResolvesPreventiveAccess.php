<?php

declare(strict_types=1);

namespace App\Http\Controllers\preventive\Concerns;

use App\Enums\Role;
use App\Models\AssetCard;
use App\Models\MaintenanceCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Access rules and form options shared by the REQ-17 controllers: the
 * administrator and the maintenance chief manage everything, technicians only
 * work on what is assigned to them and operators have read-only access.
 */
trait ResolvesPreventiveAccess
{
    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::TENANT_ADMINISTRATOR->value,
            Role::MAINTENANCE_CHIEF->value,
        ]);
    }

    private function canView(User $user): bool
    {
        return $user->hasAnyRole(array_map(
            fn (Role $role): string => $role->value,
            Role::cases(),
        ));
    }

    /**
     * Who sees every plan and execution: managers and the read-only operator.
     */
    private function canSeeAll(User $user): bool
    {
        return $this->canManage($user) || $user->hasRole(Role::OPERATOR->value);
    }

    private function technicianId(int $userId, string $field): int
    {
        $technician = User::query()
            ->role(Role::TECHNICIAN->value)
            ->find($userId);

        if (! $technician instanceof User) {
            throw ValidationException::withMessages([
                $field => 'Selecciona un usuario con el rol de técnico.',
            ]);
        }

        return $technician->id;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function technicians(): array
    {
        return User::query()
            ->role(Role::TECHNICIAN->value)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Active maintenance categories only: an inactive one cannot be used.
     *
     * @return array<int, array<string, mixed>>
     */
    private function maintenanceCategories(): array
    {
        return MaintenanceCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (MaintenanceCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assetCards(): array
    {
        return AssetCard::query()
            ->orderBy('code')
            ->get(['id', 'code'])
            ->map(fn (AssetCard $assetCard): array => [
                'id' => $assetCard->id,
                'code' => $assetCard->code,
            ])
            ->values()
            ->all();
    }
}
