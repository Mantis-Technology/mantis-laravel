<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyPortalController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $appUrl = parse_url((string) config('app.url'));

        $scheme = $appUrl['scheme'] ?? 'http';
        $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';
        $baseDomain = config('tenancy.central_domains')[0];

        return Inertia::render('company-portal', [
            'domainBase' => $baseDomain,
            'scheme' => $scheme,
            'port' => $port,
        ]);
    }
}
