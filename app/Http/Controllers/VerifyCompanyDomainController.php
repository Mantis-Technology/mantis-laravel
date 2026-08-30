<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $connection = (string) config('tenancy.database.central_connection');

        $domain = DB::connection($connection)
            ->table('domains')
            ->where('domain', "$slug.".config('tenancy.central_domains')[0])
            ->first();

        if ($domain === null) {
            return response()->json(['available' => false]);
        }

        $status = DB::connection($connection)
            ->table('tenants')
            ->where('id', $domain->tenant_id)
            ->value('status');

        return response()->json([
            'available' => $status === TenantStatus::Active->value,
        ]);
    }

    private function isValidSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', $slug) === 1;
    }
}
