---
paths:
  - 'app/Http/Controllers/tickets/**'
---

# Tickets

## Ticket reports start in Reported state from QR or asset picker
GET /tickets/create (?asset=CODE) resolves AssetCard by code and 404s unknown codes; without ?asset it lists assets. POST /tickets validates asset_card_id/title/description/location_id inline and stores reported_by + TicketStatus::Reported in the tenant DB (tenant = company). Redirects to dashboard with plain session flash key 'success'. REQ-13/14/15 build on this flow.
