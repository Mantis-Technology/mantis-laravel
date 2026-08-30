---
paths:
  - routes/tenant.php
---

# Routes

## Tenant root redirects to dashboard/login, not the landing
On tenant domains, `/` (route `home`) is a server redirect via `TenantHomeController`: authenticated users go to `dashboard`, guests to `login`. The landing page (`welcome.tsx`) is only served on central domains (`routes/web.php`). Don't render `welcome` from tenant routes.
