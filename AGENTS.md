# Agent Instructions for RadarLeb

OpenAI Codex CLI and other AGENTS.md-aware tools enter here. The full project
context lives in [`CLAUDE.md`](./CLAUDE.md). **Read it first.** This file is a
short pointer.

## TL;DR

- **Stack:** Laravel 12 + Filament 3 + Inertia 2 + Vue 3 + Tailwind 4 + Vite 6
- **DB:** SQLite locally, PostgreSQL in prod — write portable migrations
- **PHP:** 8.4 (NOT 8.5 — some deps cap at 8.4)
- **Setup:** `bash scripts/dev-setup.sh`
- **Run:** `composer run dev`
- **Test:** `composer test`
- **Lint:** `npm run lint && ./vendor/bin/pint`

## The 10 things you must know

(Detailed in `CLAUDE.md` and `docs/`. Headlines:)

1. `games.price_to_play` is **radar units** (1, 4, 8, 24, 32), not USD.
2. `PortBasedSessionIsolation` middleware is actually path-based — name is misleading.
3. `DevAutoAuth` has 3 defense layers (env + host + not registered). Keep all three.
4. `wallet_transactions.amount` MUST equal `scans.cost` (1¢ tolerance, model boot hook).
5. Migrations run on SQLite + Postgres — no `ALTER COLUMN ... TYPE`, use `Schema::change()`.
6. Two auth guards: `web` (public users) and `admin` (Filament). Separate.
7. Public UI is Inertia/Vue. Admin UI is Filament/Livewire. Don't cross-render.
8. Service Worker is intentionally disabled in `resources/js/app.ts`.
9. Test coverage is very thin — money-moving changes need tests.
10. **Production credentials were leaked in deleted git history. Rotate them.** See `docs/security.md`.

## Where things live

| What | Where |
|---|---|
| Eloquent models | `app/Models/` |
| Web controllers | `app/Http/Controllers/` |
| API controllers | `app/Http/Controllers/Api/` |
| Filament resources | `app/Filament/Resources/<Name>Resource.php` + `<Name>Resource/Pages/` |
| Filament pages | `app/Filament/Pages/` |
| Filament widgets | `app/Filament/Widgets/` |
| Migrations | `database/migrations/` |
| Seeders | `database/seeders/` |
| Factories | `database/factories/` |
| Vue components | `resources/js/components/` (UI primitives in `ui/<kind>/`) |
| Vue pages | `resources/js/pages/` |
| Routes | `routes/{web,api,auth,console}.php` |
| Tests | `tests/Feature/` (HTTP), `tests/Unit/` (pure) |
| Project docs | `docs/{architecture,security,deploy,history}.md` |

## Common tasks

- **New API endpoint:** controller method in `app/Http/Controllers/Api/`, register in `routes/api.php`, add a Feature test.
- **New admin resource:** `php artisan make:filament-resource <Name>` then customize.
- **Schema change:** `php artisan make:migration` — keep portable. See `.cursor/rules/migrations.mdc`.
- **New Vue page:** create under `resources/js/pages/`, add `Inertia::render('PageName')` in `routes/web.php`.
- **New env var:** also add to `.env.example`.

## Hard rules (won't merge if violated)

1. Migrations are SQLite+Postgres portable.
2. No files at the project root that aren't in `scripts/cleanup-phase-2.sh`'s whitelist.
3. `env()` only inside `config/*.php`. Code reads `config('...')`.
4. Mass assignment: every model has `$fillable`; controllers use Form Requests.
5. Wallet invariants enforced through the `WalletTransaction` model; never `DB::insert`.
6. Don't commit: `.env`, `bootstrap/cache/*`, `storage/{logs,framework,pail}/*`, `database/database.sqlite`, `*.log`, `composer.phar`.

## When something breaks

See "When the app won't boot" in `CLAUDE.md`.
