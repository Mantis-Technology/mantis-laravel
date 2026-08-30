---
paths:
  - app/Models/Tenant.php
---

# Models

## Tenant ids are slug-derived; stancl UUID is overwritten
stancl's GeneratesIds trait assigns a UUID on `creating` BEFORE any booted()-registered hook, so the old `blank($tenant->id)` slug check never fired and tenants got UUID ids/domains. The creating hook now overwrites auto-generated UUID ids with `uniqueSlug($name)` (explicitly-provided ids like Filament's subdomain field are preserved since they're not UUIDs). Always derive domains from `$tenant->id` (model creates `{id}.localhost`).
