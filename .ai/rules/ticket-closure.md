---
paths:
  - 'app/Enums/TicketClosureResult.php'
  - 'app/Models/TicketClosure.php'
  - 'resources/js/Pages/Tickets/Show/Partials/Close-Form.tsx'
  - 'resources/js/Pages/Tickets/Show/Partials/Closure-Summary.tsx'
  - 'resources/js/Types/Tickets/Closure.ts'
---

# Ticket closure (REQ-18)

## Closing is documented and atomic
A case is closed only through `Ticket::close()`, which stores the `TicketClosure` row and applies the `Resolved -> Closed` transition inside one transaction, so a case never ends up closed without its documentation. The transition still lands in `ticket_status_transitions`; the closure is one more event of the history and nothing is erased or overwritten. `Ticket::closure()` (HasOne) is what REQ-19 (traceability) and REQ-21 (historical data) read.

## The closure is immutable
`ticket_closures` has one row per ticket (`ticket_id` is unique) and no `updated_at`. There is no edit or delete flow: do not add one without a requirement.

## Documentation rules
- `work_performed` is required (10 to 5000 characters) and is what lets another user understand the case without the technician.
- `result` is a `TicketClosureResult`: `solved` or `solved_with_recommendations`. There is no "partial" result: a case with pending work stays `Resolved` instead of being closed.
- `observations` are required when `TicketClosureResult::requiresObservations()` is true (recommendations), optional otherwise.
- `no_pending_actions` must be accepted in the request; it is a confirmation and is not stored.

## Who can close
Only administrators and the maintenance chief (`canManageAllCases`), through `POST /tickets/{ticket}/closure` (`tickets.closure.store`). Technicians stop at `Resolved`. `updateStatus` rejects `closed`, and `transitionOptions` never offers it, so the generic status route cannot bypass the documentation.

## Frontend
The case screen receives `closure_results` and, inside `ticket`, `closure` (snake_case, `null` until closed) and `can_close`. `Close-Form.tsx` keeps its button disabled until the work, the result, the required observations and the confirmation are filled; `Closure-Summary.tsx` shows the read-only closure once it exists. Types live in `Types/Tickets/Closure.ts`. Covered by `tests/Feature/TicketClosureTest.php`.
