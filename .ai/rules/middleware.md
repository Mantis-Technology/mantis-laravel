---
paths:
  - app/Http/Middleware/EnsureTenantIsActive.php
---

# Middleware

## Tenant inactivity gate is host-based, global on web group
`EnsureTenantIsActive` is appended to the global `web` group so it also guards Fortify routes like `/login`. It cannot rely on `tenant()`/tenancy being initialized (Fortify routes aren't in routes/tenant.php), so it resolves the tenant by looking up the request host in the central `domains` table and checks status. Returns the Inertia `tenant-not-activated` page for any non-Active tenant domain request; central domains are skipped.
