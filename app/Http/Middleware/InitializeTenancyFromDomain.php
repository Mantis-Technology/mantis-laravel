<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyFromDomain
{
    public function __construct(private Tenancy $tenancy) {}

    /**
     * Initialize tenancy before the router runs, so controllers resolved while
     * gathering route middleware (e.g. Fortify's session-backed guard) already
     * use the tenant connection.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tenancy->initialized || in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            return $next($request);
        }

        $tenant = Domain::query()->where('domain', $request->getHost())->first()?->tenant;

        abort_unless($tenant instanceof Tenant, 404);

        $this->tenancy->initialize($tenant);

        return $next($request);
    }
}
