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

        $scheme = $appUrl['scheme'] ?? 'http';
        $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';
        $centralHost = config('tenancy.central_domains')[0];

        return Inertia::render('access', [
            'adminUrl' => $scheme.'://'.$centralHost.$port.'/admin',
        ]);
    }
}
