---
paths:
  - 'resources/js/**'
---

# Js

## Do not import the bare @/routes aggregate
routes/web.php registers central routes once per central_domains value (localhost, 127.0.0.1), so Wayfinder's aggregate resources/js/routes/index.ts contains duplicate identifiers (home, access, etc.) and is invalid JS/TS. Importing it breaks Vite/Rolldown dependency scanning ('Identifier home has already been declared', 'Outdated Optimize Dep'). Import per-prefix modules instead (e.g. '@/routes/tickets', '@/routes/parameterization/locations') or pass server-generated URLs as props; for /dashboard use the literal path.
