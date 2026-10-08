---
paths:
  - 'app/Http/Controllers/tickets/**'
---

# Tickets

## Ticket reports start in Reported state from QR or asset picker
GET /tickets/create (?asset=CODE) resolves AssetCard by code and 404s unknown codes; without ?asset it lists assets. POST /tickets validates asset_card_id/title/description/location_id inline and stores reported_by + TicketStatus::Reported in the tenant DB (tenant = company). Redirects to dashboard with plain session flash key 'success'. REQ-13/14/15 build on this flow.

## Ticket case visibility and transition roles
REQ-14 routes: GET /tickets (index, optional ?status= filter), GET /tickets/{ticket} (show), PATCH /tickets/{ticket}/status. tenant_admin + maintenance_chief see/manage every case; technician sees assigned_to them or reported by them and may only do Assigned→InProgress and InProgress→Resolved on their own case; everyone else only sees own reports. Moving to `assigned` requires `assigned_to` (Rule::requiredIf) pointing to a user with the technician role, so a case can never advance without a responsible. Domain errors redirect back with the 'error' session flash, success with 'success'.

## Classify through the status endpoint with the classification payload
updateStatus authorizes (canPerformTransition) BEFORE validation. Moving to Categorized requires maintenance_category_id (an active category), maintenance_type and priority via Rule::requiredIf, then calls $ticket->categorize(). Categorizing is limited to tenant_admin/maintenance_chief (same canManageAllCases as assignment). show() passes the active maintenance_categories tree, maintenance_types and priorities options, plus ticket.category/maintenance_type/priority and ticket.sla.
