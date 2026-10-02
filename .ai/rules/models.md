---
paths:
  - app/Models/Tenant.php
  - app/Models/Ticket.php
---

# Models

## Tenant ids are slug-derived; stancl UUID is overwritten
stancl's GeneratesIds trait assigns a UUID on `creating` BEFORE any booted()-registered hook, so the old `blank($tenant->id)` slug check never fired and tenants got UUID ids/domains. The creating hook now overwrites auto-generated UUID ids with `uniqueSlug($name)` (explicitly-provided ids like Filament's subdomain field are preserved since they're not UUIDs). Always derive domains from `$tenant->id` (model creates `{id}.localhost`).

## Tenant status is an enum cast: compare enums, not values
The `status` column is cast to the `TenantStatus` enum, so `$tenant->getAttribute('status')` returns a `TenantStatus` enum INSTANCE, not the raw string. Compare against `TenantStatus::Active` (enum), never `TenantStatus::Active->value` — the string comparison is always false (the origin of the PHPStan "always false" errors on activate/deactivate). Stored DB value is the enum value string ('pending'/'active'/…).

## Ticket lifecycle goes through transitionTo, never mass assignment
REQ-14: `status` is intentionally NOT in #[Fillable]; changing it must go through transitionTo(status, changedBy, note), which enforces TicketStatus::allowedTransitions() (Reported→Categorized→Assigned→InProgress→Resolved→Closed, plus Cancelled before attention starts) and requires assigned_to before Assigned/InProgress. The model's created hook writes the initial entry and transitionTo writes every change to ticket_status_transitions (append-only audit, no UPDATED_AT). New statuses start 'reported' via $attributes; factories bypass guards on purpose.
