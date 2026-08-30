---
paths:
  - app/Http/Controllers/CompanyRegistrationController.php
---

# Controllers

## Public tenant registration flow
Public company registration at /register/company (central route, web.php). Creates a Tenant via Tenant::create() — the model's `creating` hook derives a unique slug `id` (Tenant::uniqueSlug) and forces status to Pending (string `'.value'` to avoid a PHPStan cast-inference error). The `created` hook creates the {id}.localhost domain and the tenancy pipeline provisions the tenant DB. New tenants are always Pending until an admin activates them; VerifyCompanyDomainController only allows /portal redirect for Active tenants.
