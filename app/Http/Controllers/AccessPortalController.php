<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessPortalController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $appUrl = parse_url((string) config('app.url'));

        $domains = array_values(array_filter(
            config('tenancy.central_domains', []),
            fn ($domain) => ! in_array($domain, ['localhost', '127.0.0.1'], true)
        ));

        $centralHost = $domains[0]
            ?? (in_array($request->getHost(), ['localhost', '127.0.0.1'], true)
                ? config('app.central_domain', $request->getHost())
                : $request->getHost());

        $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';

        $scheme = str_ends_with($centralHost, '.onrender.com')
            ? 'https'
            : ($request->getScheme() ?: ($appUrl['scheme'] ?? 'http'));

        return Inertia::render('Access', [
            'adminUrl' => $scheme.'://'.$centralHost.$port.'/admin',
        ]);
    }
}