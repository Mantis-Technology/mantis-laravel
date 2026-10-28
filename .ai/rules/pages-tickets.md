---
paths:
  - 'resources/js/Pages/Tickets/**'
---

# Pages Tickets

## Ticket screens consume server-computed transitions
REQ-14: the controller sends ticket.allowed_transitions (already role-filtered, with action_label/color/requires_assignee) and ticket.status_transitions (audit). Tickets/Show/Partials/Status-Actions.tsx only renders those actions and PATCHes @/routes/tickets status.update; don't recompute the lifecycle in React. Case list uses a native status filter and plain Table like Users index.
