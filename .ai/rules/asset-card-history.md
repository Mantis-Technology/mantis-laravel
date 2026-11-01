---
paths:
  - 'app/Services/BuildAssetCardHistory.php'
  - 'resources/js/Pages/AssetCards/Show/**'
---

# Asset card history

## One timeline for incidents and preventive work
`AssetCardController::show` sends the `history` prop (camelCase, like the rest of that page) built by `App\Services\BuildAssetCardHistory`: the latest 50 events of the asset, mixing tickets (dated by `created_at`, with time) and preventive executions (dated by `scheduled_at`, date only), newest first. A future execution therefore appears above today's tickets. Each item carries `key`, `type` (`ticket` or `preventive`), `title`, `status_label`, `status_color`, `occurred_at`, `has_time`, `is_overdue` and `url`. `AssetCard::tickets()` is the relation behind it.

## No per-user filtering, on purpose
The service does not filter by user because every `asset-cards` route sits inside the `role:tenant_admin` group in `routes/tenant.php`. If the asset card is ever opened to other roles, apply the ticket visibility rules (`TicketController::visibleTickets`) and the preventive ones (`ResolvesPreventiveAccess`) inside the service before exposing it.

## Frontend
`Pages/AssetCards/Show/Partials/Asset-History.tsx` renders the list; `has_time` decides between date-time and date-only formatting, and the type `AssetHistoryItem` lives in `Types/AssetCards/AssetCard.ts`. Covered by `tests/Feature/AssetCardHistoryTest.php`.
