<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\Role;
use App\Enums\TenantStatus;
use App\Mail\TenantWelcomeMail;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Mail;

class SendTenantWelcomeMail
{
    public function __construct(private Tenant $tenant) {}

    /**
     * Point the default administrator at the tenant's contact email and
     * send the onboarding email with the access domain and credentials.
     */
    public function handle(): void
    {
        $contactEmail = (string) $this->tenant->getAttribute('contact_email');
        $tenantId = (string) $this->tenant->getKey();
        $fallbackEmail = "admin@{$tenantId}.localhost";
        $recipient = filled($contactEmail) ? $contactEmail : $fallbackEmail;

        $this->tenant->run(function (Tenant $tenant) use ($contactEmail, $fallbackEmail): void {
            $admin = User::withTrashed()->firstOrNew(['username' => DatabaseSeeder::DEFAULT_ADMIN_USERNAME]);

            $admin->fill([
                'name' => 'Administrador',
                'email' => filled($contactEmail) ? $contactEmail : $fallbackEmail,
            ]);

            if (! $admin->exists) {
                $admin->fill(['password' => DatabaseSeeder::DEFAULT_ADMIN_PASSWORD]);
            }

            $admin->save();
            $admin->syncRoles([Role::TENANT_ADMINISTRATOR->value]);
        });

        $appUrl = parse_url((string) config('app.url'));
        $scheme = $appUrl['scheme'] ?? 'http';
        $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';
        $domain = "{$tenantId}.localhost";

        Mail::to($recipient)->send(new TenantWelcomeMail(
            companyName: (string) $this->tenant->getAttribute('name'),
            domain: $domain,
            loginUrl: $scheme.'://'.$domain.$port.'/login',
            username: DatabaseSeeder::DEFAULT_ADMIN_USERNAME,
            password: DatabaseSeeder::DEFAULT_ADMIN_PASSWORD,
            requiresActivation: $this->tenant->getAttribute('status') !== TenantStatus::Active->value,
        ));
    }
}
