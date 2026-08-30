<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Block every request on a tenant domain whose tenant is not active,
     * rendering the "tenant not activated" page instead of the login or the app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (in_array($host, config('tenancy.central_domains'), true)) {
            return $next($request);
        }

        $tenantId = Domain::query()->where('domain', $host)->value('tenant_id');

        $tenant = $tenantId
            ? Tenant::query()->whereKey($tenantId)->first()
            : null;

        if ($tenant && $tenant->getAttribute('status') !== TenantStatus::Active) {
            $appUrl = parse_url((string) config('app.url'));
            $scheme = $appUrl['scheme'] ?? 'http';
            $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';
            $centralHost = (string) config('tenancy.central_domains')[0];

            return Inertia::render('tenant-not-activated', [
                'tenantName' => (string) $tenant->getAttribute('name'),
                'centralUrl' => $scheme.'://'.$centralHost.$port.'/',
            ])
                ->toResponse($request);
        }

        return $next($request);
    }
}
