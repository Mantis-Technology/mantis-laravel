<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantHomeController extends Controller
{
    /**
     * Redirect the tenant homepage to the dashboard when authenticated,
     * or to the login page for guests.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    }
}
