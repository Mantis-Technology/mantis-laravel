---
paths:
  - 'resources/js/Pages/PreventiveMaintenance/**'
  - 'resources/js/Types/PreventiveMaintenance/**'
  - 'resources/js/Lib/Preventive-Date.ts'
  - 'resources/js/Lib/Form-Classes.ts'
---

# Pages Preventive Maintenance

## Pages render what the controllers compute
REQ-17 pages live in `Pages/PreventiveMaintenance/Plans/{Index,Create,Show,Edit}/Index.tsx` and `Pages/PreventiveMaintenance/Executions/{Index,Create,Show}/Index.tsx`, with subcomponents in a `Partials/` folder next to the page. Unlike Tickets and AssetCards, these controllers send props in snake_case (`can_manage`, `asset_cards`, `maintenance_categories`), so the page `Props` and the types in `Types/PreventiveMaintenance/` use snake_case too. `allowed_transitions`, `can_manage`, `can_generate`, `can_postpone` and `can_reassign` are computed by the server and already filtered by role; React only renders them and never recomputes the lifecycle.

## Routes and payloads
Use the Wayfinder routes `@/routes/preventive-plans` and `@/routes/preventive-executions`. Nested routes have their own shape: `status.update` (PATCH, plans and executions), `occurrences.store` (POST), `schedule.update` (PATCH, postpone) and `assignment.update` (PATCH, reassign). Field names follow the controllers: `assigned_to_id` and `note` for reassigning, `scheduled_at` and `note` for postponing, `status`, `note`, `execution_notes` and `findings` for execution status. Flash messages use the `success` and `error` keys that `FlashToasts` already shows.

## Required data is enforced in the UI too
`Executions/Show/Partials/Status-Actions.tsx` disables a transition until the field its `requires_reason` or `requires_notes` flag asks for is filled. This mirrors the backend validation, which stays the authority. Cancelling a plan asks for confirmation because its already scheduled executions are kept. The create form disables `interval` and `ends_at` for a Once plan, and the edit form hides `ends_at` for it, as the controller ignores both.

## Dates
Plan and execution dates arrive as `YYYY-MM-DD`. Never pass them to `new Date()` directly (UTC-5 shows the previous day): use `formatPreventiveDate`, `formatPreventiveDateTime` (ISO 8601 values) and `todayLocalIso` from `@/Lib/Preventive-Date`. Status badges reuse `TicketStatusBadge` with the color sent by the server, and native selects use `SELECT_CLASS` from `@/Lib/Form-Classes`. The sidebar group "Mantenimiento preventivo" in `Components/App-Sidebar.tsx` links to both lists.
