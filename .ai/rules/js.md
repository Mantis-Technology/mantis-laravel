---
paths:
  - 'resources/js/**'
---

# Js

## Do not import the bare @/routes aggregate
routes/web.php registers central routes once per central_domains value (localhost, 127.0.0.1), so Wayfinder's aggregate resources/js/routes/index.ts contains duplicate identifiers (home, access, etc.) and is invalid JS/TS. Importing it breaks Vite/Rolldown dependency scanning ('Identifier home has already been declared', 'Outdated Optimize Dep'). Import per-prefix modules instead (e.g. '@/routes/tickets', '@/routes/parameterization/locations') or pass server-generated URLs as props; for /dashboard use the literal path.

## Keep the page names in App.tsx aligned with Inertia::render
`resources/js/App.tsx` decides which pages skip the app layout by comparing the page name with literals (`'Welcome'`, `'Access'`, `'Company-Portal'`, `'Tenant-Register'`, `'Tenant-Register-Success'`, `'Tenant-Not-Activated'` and the `Auth/` prefix). Those literals must match the name passed to `Inertia::render()` and the file under `resources/js/Pages` exactly, including capitalization and hyphens. When a public page is added or renamed, update that list too, or it renders inside the sidebar layout.
