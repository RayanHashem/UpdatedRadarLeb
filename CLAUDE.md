# RadarLeb — Project Context for AI Assistants

This file is the entry point for any AI working on this codebase (Claude Code,
Cursor, OpenAI Codex). It's also the entry point for any **human** new to the
project — start here, then dive into the linked docs.

If you change anything fundamental in this repo, update this file first.

---

## TL;DR for an agent

- **What it is:** RadarLeb — a Lebanese-themed sweepstakes web app. Users scan to play one of five games. Admins manage everything via a Filament panel at `/admin`.
- **Stack:** Laravel 12 + Filament 3 (admin) + Inertia 2 + Vue 3 + Tailwind 4 + Vite 6.
- **DB:** SQLite locally, PostgreSQL (AWS RDS) in production. Migrations must run on both.
- **PHP:** 8.4 — do not use 8.5, three deps (`nette/schema`, `nette/utils`, `openspout/openspout`) cap at 8.4.
- **Setup:** `bash scripts/dev-setup.sh` (idempotent).
- **Run:** `composer run dev` — boots Laravel + Vite + queue + log tail.
- **Test:** `composer test` (PHPUnit). Money-path coverage is in `tests/Feature/{ScanFlow,WalletTransactionInvariants,AuthFlow,Api/ApiEndpoints,MassAssignmentGuard,HealthAndLocale}Test.php` — keep these green.
- **Lint:** `npm run lint && ./vendor/bin/pint`.
- **Deeper context:** see `docs/architecture.md`, `docs/security.md`, `docs/deploy.md`, `docs/history.md`.

---

## Where to look first when…

| Goal | File |
|---|---|
| Understand the domain (scans, wallets, draws, prizes) | `docs/architecture.md` |
| Touch anything security-sensitive (auth, wallets, secrets) | `docs/security.md` |
| Deploy to production / understand prod setup | `docs/deploy.md` |
| Wonder "why is the code shaped this way?" | `docs/history.md` |
| Write a migration | `.cursor/rules/migrations.mdc` |
| Write a Filament resource | `.cursor/rules/filament.mdc` |
| Write a test | `.cursor/rules/tests.mdc` |
| Touch backend code | `.cursor/rules/backend.mdc` |
| Touch frontend code | `.cursor/rules/frontend.mdc` |

---

## Repository layout

The project went through a major cleanup (commit `cleanup-root` on this
branch) — 249 stray files at the project root were deleted, 9 misplaced
config files were moved into `config/`. Don't add files at the project root.
The whitelist of legitimate root files is in `scripts/cleanup-phase-2.sh`.

```
app/
  Actions/                      Single-purpose invokable classes (the "service layer")
    Game/                        AttemptScan, BuildGameProgress, CheckWinEligibility
    Wallet/                      TopOffWallet
    User/                        RegisterUser
  Console/Commands/             Custom artisan commands
  Filament/
    Pages/                       UsersByGamePage (abstract base) + 5 thin per-game subclasses, Profile
    Resources/                   Filament resources for User, Game, Draw, Scan, Winner, WalletTransaction
    Widgets/                     LatestWinners, ScansChart, StatsOverview
  Helpers/functions.php          Global helpers
  Http/
    Controllers/
      Api/                       JSON endpoints for the SPA (GameController, UserController)
      Auth/                      Standard Laravel breeze-style auth controllers
      RadarController.php        Inertia entry for the radar/scan UI
      SettingsController.php
    Middleware/
      DevAutoAuth.php            ⚠ DEV-ONLY auto-login (defense in depth — see docs/security.md)
      HandleInertiaRequests.php  Inertia bootstrap middleware
      PortBasedSessionIsolation.php   Misnamed — implementation is path-based (see docs/history.md)
    Requests/Auth/LoginRequest.php
    Responses/Filament/          Custom Filament login/logout responses
  Models/                        Eloquent models
  Providers/
    AppServiceProvider.php
    Filament/AdminPanelProvider.php

bootstrap/
  app.php                        Laravel 11+ app bootstrap (middleware, exceptions, routing)
  cache/                         Auto-generated, gitignored

config/                          Laravel config (app, auth, cache, database, ...)

database/
  factories/                     Model factories — use these in tests
  migrations/                    Schema migrations — keep portable (SQLite + Postgres)
  seeders/                       Game seeder, admin seeder
  database.sqlite                Local dev SQLite (gitignored)

docs/                            Project docs (architecture, security, deploy, history)

public/                          Web root. Filament publishes built JS/CSS into public/{js,css}/filament/

resources/
  css/app.css
  js/                            Vue/Inertia frontend
    app.ts                       Inertia entry point
    components/                  Reusable Vue components (UI primitives in components/ui/)
    composables/                 useAppearance, useInitials, etc.
    layouts/                     AppLayout, AuthLayout, settings/
    lib/                         utils.ts, helpers
    pages/                       Inertia pages (Dashboard, Login, Register, etc.)
    types/                       TypeScript type defs
  views/                         Blade templates — minimal: just app.blade.php and Filament partials

routes/
  api.php                        /api/* — JSON endpoints
  auth.php                       Auth routes (login, register, etc.)
  console.php                    Scheduled tasks
  web.php                        Public + Inertia routes

scripts/
  dev-doctor.sh                  Reports what's installed
  dev-setup.sh                   One-shot local setup (idempotent)
  cleanup-phase-2.sh             Historical: deleted 249 stray root files
  smoke-test.sh                  Post-deploy sanity check

storage/                         Runtime: logs, sessions, framework cache. All gitignored except .gitignore stubs.

tests/
  Feature/                       HTTP/integration tests
  Unit/                          Pure-logic tests
```

---

## Common commands (junior-friendly cheat-sheet)

```bash
# First-time setup or after pulling a confusing branch
bash scripts/dev-doctor.sh        # what's installed?
bash scripts/dev-setup.sh         # one-shot setup (idempotent)

# Daily dev
composer run dev                  # starts everything
# → http://localhost:8000        public app
# → http://localhost:8000/admin  Filament admin
# → http://localhost:5173        Vite dev server (used by the app, you don't open this directly)

# Or run pieces in separate terminals
php artisan serve                 # :8000
npm run dev                       # Vite :5173
php artisan queue:listen --tries=1
php artisan pail                  # tail logs

# Tests
composer test                     # full PHPUnit suite (clears config first)
php artisan test --filter=Auth    # subset by name

# Database
php artisan migrate               # run new migrations
php artisan migrate:fresh --seed  # nuke + reseed (LOCAL ONLY — never in prod)
php artisan db:seed --class=GameSeeder

# Lint / format
npm run lint                      # eslint --fix on JS/TS/Vue
npm run format                    # prettier on resources/
./vendor/bin/pint                 # Laravel Pint (PHP code style)

# Filament
php artisan filament:upgrade      # republish admin assets (after composer update)
php artisan make:filament-resource ModelName

# Generators worth knowing
php artisan make:model Foo -mf               # model + migration + factory
php artisan make:migration add_x_to_y_table  # raw migration
php artisan make:controller FooController --resource
php artisan make:request StoreFooRequest     # form-request for validation
```

---

## The 10 things you must know about this codebase

(Each is detailed elsewhere — this is the headline + link.)

1. **`games.price_to_play` is radar units, not USD** — so `1, 4, 8, 24, 32`. Mobile scans debit 1 Radar Cash. → `docs/history.md`
2. **`PortBasedSessionIsolation` is actually path-based** — class name is misleading. Don't refactor based on the name. → `docs/history.md`
3. **`DevAutoAuth` has 3 layers of defense** — env check + host check + not registered. Keep all three. → `docs/security.md`
4. **`wallet_transactions.amount` MUST equal `scans.cost`** — 1¢ tolerance, validated in `WalletTransaction::boot()`. Don't bypass. → `docs/architecture.md`
5. **Migrations must run on both SQLite and Postgres** — no Postgres-only raw SQL. We just rewrote 4 of them. → `.cursor/rules/migrations.mdc`
6. **Two auth guards: `web` (public) and `admin` (Filament)** — keep them separate. → `docs/architecture.md`
7. **Public UI = Inertia/Vue. Admin UI = Filament/Livewire.** Don't cross-render. → `docs/architecture.md`
8. **Service Worker is intentionally disabled** in `resources/js/app.ts` — re-enabling it requires resolving a Vite cache-invalidation conflict. → `docs/history.md`
9. **Money-path tests live in `tests/Feature/`** — `ScanFlowTest`, `WalletTransactionInvariantsTest`, `AuthFlowTest`, `Api/ApiEndpointsTest`, `MassAssignmentGuardTest`, `HealthAndLocaleTest`. Keep these green; if you add money-touching code, add tests. → `.cursor/rules/tests.mdc`
10. **PHP 8.4, not 8.5** — three deps cap at 8.4. → `scripts/dev-setup.sh` enforces this.

---

## Junior-friendly idioms — what each Laravel thing actually is

If you're new to Laravel:

- **Artisan** — Laravel's CLI tool (`php artisan <command>`). Most generators and runners live here.
- **Eloquent** — Laravel's ORM. `User::find(1)->wallet_transactions` instead of writing SQL.
- **Migration** — a versioned schema change. PHP file in `database/migrations/`.
- **Seeder** — a script that fills the DB with starter data (e.g. the five games).
- **Factory** — a fixture generator. Use in tests: `User::factory()->create()`.
- **Service Provider** — Laravel's DI bootstrap. Stuff in `app/Providers/` runs at boot.
- **Middleware** — request-pipeline filter. Runs before/after a route handler.
- **Form Request** — a validation class. `php artisan make:request StoreFooRequest`. Lets controllers be `function store(StoreFooRequest $req)` instead of validating inline.
- **Inertia** — bridge between Laravel routes and Vue pages. Routes return `Inertia::render('PageName', $props)` instead of JSON or HTML; the client mounts the matching Vue component.
- **Filament** — admin panel framework on top of Livewire. You write PHP "resources" (one per model) and Filament generates a CRUD UI.
- **Livewire** — server-rendered components with reactive interactivity. Filament uses it.
- **Queue** — async job runner. `dispatch(new SomeJob)` instead of running heavy work inline.
- **Pail** — the log tail (`php artisan pail`). Real-time, color-coded.

If you're new to Vue 3 / Inertia:

- **Inertia page** — a `.vue` file in `resources/js/pages/` that's the destination of `Inertia::render('PageName')`. Default-export.
- **`useForm`** — `@inertiajs/vue3`'s helper for forms. Wraps state + submit + errors. Don't roll your own.
- **`<script setup lang="ts">`** — the modern Vue Composition API style. Everything new should use it.
- **`@/`** — alias for `resources/js/` in imports. Configured in `vite.config.ts`.
- **`reka-ui`** — the shadcn-vue port we use for UI primitives. Components in `resources/js/components/ui/`.
- **`lucide-vue-next`** — icon library. `import { Camera } from 'lucide-vue-next'`.

---

## When the app won't boot

1. `bash scripts/dev-doctor.sh` — diagnoses tooling.
2. `tail -f storage/logs/laravel.log` — Laravel writes errors here.
3. `php artisan config:clear && php artisan cache:clear && php artisan view:clear`
4. `composer dump-autoload` if "Class not found".
5. `npm run build` if Vite manifest is missing.
6. `php artisan migrate --pretend` to preview pending migrations.

---

## Hard rules (will be flagged in code review)

1. **Migrations run on SQLite + Postgres.** Use `Schema::change()`, not raw `ALTER COLUMN ... TYPE`.
2. **No files at the project root.** New code goes in its proper subdirectory.
3. **Two auth guards.** Don't merge `web` and `admin`.
4. **Inertia ≠ Filament.** Don't cross-render.
5. **`env()` only inside `config/*.php`.** Code reads `config('section.key')`.
6. **When you add an env var, update `.env.example`.**
7. **Don't commit:** `.env`, `bootstrap/cache/*`, `storage/{logs,framework,pail,debugbar}/*`, `database/database.sqlite`, `*.log`, `composer.phar`. The `.gitignore` enforces this.
8. **Wallet invariants are real** — `wallet_transactions.amount === scans.cost`, `type` lowercased, top-ups have `game_id = NULL`. → `docs/architecture.md`
9. **Mass assignment hygiene** — every model has `$fillable`. Controllers use Form Requests. → `docs/security.md`
10. **Tests live in `tests/`. Run with `composer test`.** Add tests when you add money-touching code.
