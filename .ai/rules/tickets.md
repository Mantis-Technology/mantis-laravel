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

## Assignment history keeps every responsible per ticket
REQ-15 adds the tenant table `ticket_assignments` (ticket_id, assigned_to, assigned_by, assigned_at, ended_at, note). `Ticket::transitionTo(Assigned)` opens an assignment from `tickets.assigned_to` and any final status (Closed/Cancelled) closes the active one. `tickets.assigned_to` stays the current responsible, so visibility and transition permissions keep using it; the history is append-only and only written by the model (`transitionTo`, `reassignTo`), never by controllers. show() eager-loads `assignments.technician` and `assignments.assignedBy` and exposes `ticket.assignments` (id, technician, assigned_by, assigned_at, ended_at, is_active, note) plus `ticket.can_reassign`.

## Reassign through its own endpoint, management roles only
PATCH /tickets/{ticket}/assignment (`tickets.assignment.update`) calls `TicketController::reassign`. Only canManageAllCases (tenant_admin, maintenance_chief) may use it; technicians get 403 before validation. It validates `assigned_to` (must be a technician through technicianId(), otherwise a validation error on assigned_to) and an optional note up to 500 chars. `Ticket::reassignTo` only works while the case is Assigned or InProgress and rejects the technician who already holds it, returning an error shown with the 'error' flash; success flashes 'success'. Reassigning never changes the status and writes no status transition. GET /tickets?assigned=me limits the list to cases assigned to the user and is echoed in `filters.assigned`.
