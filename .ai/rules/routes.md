---
paths:
  - routes/tenant.php
---

# Routes

## Tenant root redirects to dashboard/login, not the landing
On tenant domains, `/` (route `home`) is a server redirect via `TenantHomeController`: authenticated users go to `dashboard`, guests to `login`. The landing page (`welcome.tsx`) is only served on central domains (`routes/web.php`). Don't render `welcome` from tenant routes.

## Service level routes are management-only
parameterization/service-levels (index/create/store/edit/update/destroy/toggle-active) is a route group wrapped in role:maintenance_chief|tenant_admin, handled by App\Http\Controllers\parameterization\ServiceLevelController with Inertia pages under resources/js/pages/Parameterization/ServiceLevels. Permissions query:view_service_levels / action:configure_service_levels are seeded for maintenance_chief.
