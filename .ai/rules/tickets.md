---
paths:
  - 'app/Http/Controllers/tickets/**'
---

# Tickets

## Ticket reports start in Reported state from QR or asset picker
GET /tickets/create (?asset=CODE) resolves AssetCard by code and 404s unknown codes; without ?asset it lists assets. POST /tickets validates asset_card_id/title/description/location_id inline and stores reported_by + TicketStatus::Reported in the tenant DB (tenant = company). Redirects to dashboard with plain session flash key 'success'. REQ-13/14/15 build on this flow.

## Ticket case visibility and transition roles
REQ-14 routes: GET /tickets (index, optional ?status= filter), GET /tickets/{ticket} (show), PATCH /tickets/{ticket}/status. tenant_admin + maintenance_chief see/manage every case; technician sees assigned_to them or reported by them and may only do Assigned→InProgress and InProgress→Resolved on their own case; everyone else only sees own reports. Moving to `assigned` requires `assigned_to` (Rule::requiredIf) pointing to a user with the technician role, so a case can never advance without a responsible. Domain errors redirect back with the 'error' session flash, success with 'success'.
