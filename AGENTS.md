<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>

# Mantis — Application Blueprint

Multi-tenant Laravel 13 app (PHP 8.5, Inertia v3 + React 19, Filament v5, Tailwind v4). Project-specific context not covered by the generic guidelines above.

## Multi-tenancy (stancl/tenancy, DB-per-tenant)

This is the single most important thing to get right — nearly everything lives under tenancy.

- There are TWO apps in one repo:
  - **Central** app, served only on `central_domains` (`localhost`, `127.0.0.1`, see `config/tenancy.php`). Data lives in the central DB. Models: `Admin`, `Tenant`, `Domain`. Hosts the Filament panel.
  - **Tenant** app, served on `{tenant-slug}.localhost` via domain-based tenancy. Tenant routes live in **`routes/tenant.php`** (`InitializeTenancyByDomain` + `PreventAccessFromCentralDomains` middleware), NOT `routes/web.php`. Models: `User`, `MaintenanceCategory`.
- `routes/web.php` only maps the central domain home page. New customer-facing routes go in `routes/tenant.php`.
- **Migrations split by database:** `php artisan migrate` only runs CENTRAL migrations. Per-tenant migrations live in **`database/migrations/tenant/`** and run via **`php artisan tenants:migrate`**. Put new per-tenant tables there.
- Tenant DBs are auto-created + migrated synchronously on tenant creation (`TenancyServiceProvider` event pipelines). Creating a `Tenant` also auto-creates its `{slug}.localhost` domain; the tenant id is derived from the name slug and renames cascade to the domain on update.
- PostgreSQL (prod) uses schema-based tenancy (`PostgreSQLSchemaManager`); local dev uses SQLite.

## Auth: two independent surfaces

- **Tenant app auth = Fortify** (guard `web`, `User` model with Spatie `HasRoles`). Fortify renders Inertia pages (`Auth/login`, `Auth/register`, `Auth/forgot-password`, `Auth/reset-password`, `Auth/verify-email`); 2FA and passkeys are enabled. See `app/Providers/FortifyServiceProvider.php` and `app/Actions/Fortify/`.
- **Central admin = Filament v5 panel at `/admin`** (`AdminPanelProvider`, guard `admin`, `Admin` model) — currently exposes the `Tenant` resource for provisioning tenants (activate/suspend/deactivate via `Tenant` model methods). Never authenticate or scope a `User` against the central DB or an `Admin` against tenant data.
- Roles/permissions tables are per-tenant (`database/migrations/tenant/..._permission_tables.php`).

## Verification commands

- `composer dev` — runs `php artisan serve` + queue listener + Vite concurrently (container uses polling watching).
- `composer test` — full gate: `pint --test` → `phpstan analyse` → `php artisan test`. May be slow; use `composer ci:check` for the same on CI.
- Focused: `php artisan test --compact --filter=Name`, `npm run types:check` (tsc), `npm run lint:check` (eslint), `npm run format:check` (prettier), `vendor/bin/pint --dirty` for PHP style only.
- CI (`.github/workflows/tests.yml`): PHP 8.5 + Node 22, runs `composer setup` then `composer ci:check`.
- PHPStan runs at **level 7** (larastan) over `app/`, `config/`, `database/`, `routes/`.

## Frontend gotchas

- **Wayfinder route helpers are NOT committed.** `@laravel/vite-plugin-wayfinder` generates `resources/js/actions/`, `resources/js/routes/`, `resources/js/wayfinder/` (all gitignored) whenever Vite runs (`npm run dev` or `npm run build`). After adding/changing Laravel routes, regenerate them before relying on `@/actions` / `@/routes` imports; `php artisan route:list` alone won't refresh them. `formVariants: true` is enabled.
- React **Compiler** is enabled (babel plugin in `vite.config.ts`) — follow its rules (no manual memo unless needed).
- Tailwind v4 (CSS-first) with shadcn/ui primitives from `resources/js/components` (base-ui, `components.json`); prefill fields with `data-slot` attributes.
- `HandleInertiaRequests` shares `auth.user`, `tenant`, and **all `maintenance_categories`** as global props on every tenant request — be mindful of the eager-loaded query; don't duplicate it.

## Code conventions

- Models use PHP 8 attribute-based fillable/hidden: `#[Fillable(['name', 'email', 'password'])]` / `#[Hidden(['password', ...])]` instead of `$fillable` / `$hidden` properties (see `app/Models/*`).
- `declare(strict_types=1)` is used in some app/config/routes files — match the sibling file.
- Domain request validation: this project uses inline validation in controllers (e.g. `MaintenanceCategoryController`) rather than FormRequest classes — follow that unless you add one deliberately.
- `app/Actions/Fortify/` holds registration/profile actions; enums live in `app/Enums/` (UI strings are Spanish).

## Skills

Activate the matching local skill (`.claude/skills` / `.agents/skills`) when working in its domain: `fortify-development`, `inertia-react-development`, `wayfinder-development`, `pest-testing`, `laravel-best-practices`, `tailwindcss-development`.

## Rules

Before planning or editing, check `.ai/rules/` (`index.md` maps globs → rule files, plus `.ai/rules/boost/`) for committed, area-specific rules, and use Boost `record-rule` to persist new settled decisions there.
