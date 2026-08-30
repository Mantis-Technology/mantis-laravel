<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class CompanyRegistrationController extends Controller
{
    /**
     * Show the public company registration form.
     */
    public function create(): Response
    {
        $appUrl = parse_url((string) config('app.url'));

        return Inertia::render('tenant-register', [
            'domainBase' => config('tenancy.central_domains')[0],
            'scheme' => $appUrl['scheme'] ?? 'http',
            'port' => isset($appUrl['port']) ? ':'.$appUrl['port'] : '',
        ]);
    }

    /**
     * Validate and create the pending company/tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:20'],
            'contact_email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'contact_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ])->validate();

        if (! empty($data['logo'])) {
            $path = $data['logo']->store('tenants/logos', 'local');
            $data['meta'] = ['image' => $path];
        }

        unset($data['logo']);

        Tenant::create($data);

        return redirect()
            ->route('registration.success')
            ->with('status', 'Solicitud recibida. La empresa será revisada y activada en breve.');
    }

    /**
     * Show the registration confirmation page.
     */
    public function success(): Response
    {
        $appUrl = parse_url((string) config('app.url'));

        return Inertia::render('tenant-register-success', [
            'domainBase' => config('tenancy.central_domains')[0],
            'scheme' => $appUrl['scheme'] ?? 'http',
            'port' => isset($appUrl['port']) ? ':'.$appUrl['port'] : '',
        ]);
    }
}
