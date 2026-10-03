---
paths:
  - tests/Feature/TicketRegistrationTest.php
---

# Feature

## Tenant feature tests provision a real tenant per test
beforeEach recreates database/testing_central.sqlite, runs central migrations, creates a Tenant (auto-provisions + migrates its DB) and calls tenancy()->initialize(); afterEach ends tenancy and deletes the tenant. The app shares plain session flash keys ('success'/'error') via HandleInertiaRequests, so assert flash with assertSessionHas, not assertInertiaFlash.
