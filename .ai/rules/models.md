---
paths:
  - app/Models/Tenant.php
  - app/Models/Ticket.php
  - app/Models/ServiceLevel.php
---

# Models

## Tenant ids are slug-derived; stancl UUID is overwritten
stancl's GeneratesIds trait assigns a UUID on `creating` BEFORE any booted()-registered hook, so the old `blank($tenant->id)` slug check never fired and tenants got UUID ids/domains. The creating hook now overwrites auto-generated UUID ids with `uniqueSlug($name)` (explicitly-provided ids like Filament's subdomain field are preserved since they're not UUIDs). Always derive domains from `$tenant->id` (model creates `{id}.localhost`).

## Tenant status is an enum cast: compare enums, not values
The `status` column is cast to the `TenantStatus` enum, so `$tenant->getAttribute('status')` returns a `TenantStatus` enum INSTANCE, not the raw string. Compare against `TenantStatus::Active` (enum), never `TenantStatus::Active->value` — the string comparison is always false (the origin of the PHPStan "always false" errors on activate/deactivate). Stored DB value is the enum value string ('pending'/'active'/…).

## Ticket lifecycle goes through transitionTo, never mass assignment
REQ-14: `status` is intentionally NOT in #[Fillable]; changing it must go through transitionTo(status, changedBy, note), which enforces TicketStatus::allowedTransitions() (Reported→Categorized→Assigned→InProgress→Resolved→Closed, plus Cancelled before attention starts) and requires assigned_to before Assigned/InProgress. The model's created hook writes the initial entry and transitionTo writes every change to ticket_status_transitions (append-only audit, no UPDATED_AT). New statuses start 'reported' via $attributes; factories bypass guards on purpose.

## REQ-13 classification fields and the Categorized guard
Ticket carries maintenance_category_id, maintenance_type (MaintenanceType enum), priority (MaintenancePriority enum), categorized_by and categorized_at. Status stays out of #[Fillable]; classify through categorize(MaintenanceCategory, MaintenanceType, MaintenancePriority, changedBy, note), which sets the fields then calls transitionTo(Categorized). TicketStatus::requiresClassification() makes Categorized reject a transition when isClassified() (all three fields set) is false, and Ticket::canTransitionTo mirrors that guard.

## Service levels are wildcard rules resolved most-specific-first
REQ-16: service_levels rows store response_hours and resolution_hours plus three optional dimensions (maintenance_category_id, maintenance_type, priority) where null means wildcard. is_active is a global scope: use withInactive()/onlyInactive() to include disabled rules. ResolveTicketServiceLevel::for($ticket) filters active rules with matches() and sorts by [-specificity(), id], returning the most specific rule or null. SLA is computed on read (app/Dto/TicketSlaStatus) from the ticket created_at plus the InProgress/Resolved transitions; there are no snapshot columns, so editing a rule retroactively changes compliance.
