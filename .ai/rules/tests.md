---
paths:
  - 'tests/**'
---

# Tests

## Run tenant tests with the sqlite default connection
CI copies .env.example (DB_CONNECTION=sqlite) so phpunit's RefreshDatabase wraps sqlite. This container's .env uses pgsql (sail); running multiple tests/Feature files that switch database.default to the file-based testing_central.sqlite under pgsql causes 'readonly database' / 'relation migrations does not exist' errors. For local runs, force the CI setup: `DB_CONNECTION=sqlite DB_DATABASE=$(pwd)/database/database.sqlite php artisan test`. Also delete orphaned database/tenant_* files after any killed run — beforeEach throws before assigning $this->tenant, so afterEach cannot clean them and every later run fails with 'Database tenant_<slug> already exists'.
