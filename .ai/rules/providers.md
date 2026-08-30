---
paths:
  - app/Providers/TenancyServiceProvider.php
---

# Providers

## Tenant welcome email is sent from the TenantCreated pipeline job
A `SendTenantWelcomeMail` job runs in the TenantCreated JobPipeline right after `SeedDatabase` (both Filament admin and public self-register flows go through `Tenant::create()`). It points the default admin (username/password constants on `DatabaseSeeder::DEFAULT_ADMIN_USERNAME/PASSWORD`) at the tenant's `contact_email` and mails the access domain + default credentials via `App\Mail\TenantWelcomeMail` (`resources/views/emails/tenant-welcome.blade.php`). Any onboarding email must be added to this pipeline, not to controllers.
