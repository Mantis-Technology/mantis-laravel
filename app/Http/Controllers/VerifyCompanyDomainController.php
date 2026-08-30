<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;

class VerifyCompanyDomainController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $slug = strtolower(trim($slug));

        if (! $this->isValidSlug($slug)) {
            return response()->json(['available' => false]);
        }

        $domain = Domain::query()
            ->where('domain', "$slug.".config('tenancy.central_domains')[0])
            ->first();

        if ($domain === null) {
            return response()->json(['available' => false]);
        }

        $tenant = Tenant::query()->find($domain->tenant_id);

        $available = $tenant !== null
            && $tenant->status === TenantStatus::Active;

        return response()->json(['available' => $available]);
    }

    private function isValidSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', $slug) === 1;
    }
}
