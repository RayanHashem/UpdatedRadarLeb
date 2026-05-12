# History — what changed and why

A short log of non-obvious decisions and quirks. Read this before being
surprised by something weird in the code.

## The repo was a mess (May 2026 cleanup)

The cleanup branch `cleanup/abed-radarleb` deleted ~249 files from the project
root: 125 stray `.vue` components, 58 stray `.php` files (all duplicates of
files already in `app/` or `resources/js/`), 18 stray `.js`/`.ts`/`.css` files
(Filament's published assets, leaked from `public/`), 22 troubleshooting
markdown docs, plus committed binaries (`composer.phar`, `composer-setup.php`,
`laravel.log`).

The Laravel + Vue + Inertia + Filament app **autoloaded none of those files**
(PSR-4 only loads from `app/`, Vite only bundles from `resources/js/`), so the
deletions did not change runtime behavior. Git history preserves all of them
under commit `e1283ae` (`origin/updatedradarleb`) if anything was missed.

The same cleanup also moved 9 misplaced Laravel config files (`app.php`,
`cache.php`, `database.php`, `filesystems.php`, `logging.php`, `mail.php`,
`queue.php`, `services.php`, `session.php`) from the project root into
`config/`. Without that, the app couldn't boot — `config/` had only `auth.php`
and `view.php`.

If you find yourself wondering "why does the codebase still feel weird in
places?" — it's because we did a structural cleanup, not a logical one. The
business code under `app/` and `resources/js/` was not refactored.

## The wallet transaction backfill saga

In Feb 2026 the previous developer added `game_id` to `wallet_transactions`
and discovered three things wrong with the existing data:

1. Some transactions had `amount` that didn't match the related `scans.cost`
   (rounding drift over time).
2. Some scans had two transactions because there was no unique constraint.
3. The `type` column had inconsistent values: `'Scan'`, `'SCAN'`, `'Play'`,
   `'spent'`, `'TopUp'`, `'topup'`, etc.

Five migrations under `database/migrations/2026_02_19_*` clean up that mess:

| Migration | What it does |
|---|---|
| `000001_add_game_id_to_wallet_transactions_table` | Adds `game_id` (nullable FK) + indexes |
| `000002_backfill_game_id_in_wallet_transactions` | Copies `game_id` from the related scan |
| `000003_add_unique_constraint_scan_id_to_wallet_transactions` | Dedupes + adds unique partial index on `scan_id` |
| `000004_normalize_wallet_transaction_types` | Lower-cases `type`, maps variants to canonical values |
| `000005_fix_wallet_transaction_amount_mismatches` | Sets `amount = scans.cost` where they drifted (>1¢) |

After those ran, `WalletTransaction::boot()` enforces the rules going forward:
type lowercased on save, amount validated against scan cost.

**Three of these five migrations were originally written using Postgres-only
raw SQL** (`UPDATE ... FROM`, `DELETE ... USING`, partial indexes). The local
SQLite dev environment couldn't run them. They were rewritten in commit
`018b733` to use SQL-standard correlated subqueries and Laravel's Schema
builder — same semantics, runs on both DBs. See `.cursor/rules/migrations.mdc`
for the patterns.

## Game scan prices are Radar Cash units

`games.price_to_play` stores the Radar Cash units debited from the wallet for
one scan. Current values are `1`, `4`, `8`, `24`, `32`.

The migration `2026_05_12_000002_restore_radar_unit_scan_costs_and_rename_super_car.php`
restored the RD:Leb radars-per-scan interpretation after a short-lived
wallet-dollar interpretation. The column type remains `DECIMAL(12,2)`.

Filament should show `price_to_play` without a `$` prefix because it is Radar
Cash units.

## Multi-game spending support

Originally the "users by game" Filament pages
(`MobileUsers`, `BikeElectronicsUsers`, `SUVUsers`, `MuscleCarUsers`,
`SuperCashPrizeUsers`) filtered users by `users.game_id` — i.e. by their
*currently selected* game. That meant a user who'd spent across multiple
games would only show up under one of them.

In Feb 2026 that filter was rewritten to use `whereHas('walletTransactions', …)`
with a check on `wallet_transactions.game_id`. Now a user appears under every
game they've ever spent on. The "Current Game" column on `UserResource` still
reads `users.game_id` — that's the user's *latest selection*, not their
spending history.

This is why `wallet_transactions.game_id` is nullable: top-up transactions
have no associated game, so they keep `game_id = NULL`.

## DevAutoAuth was added, then defended in depth

The `DevAutoAuth` middleware auto-logs-in a dev user when `DEV_PASSWORD` is
set. It's incredibly useful locally. It is **incredibly dangerous** in
production.

The previous developer added three layers of defense after a security audit:
environment check, hostname check, and "not registered in
`bootstrap/app.php`". See `docs/security.md`. If you ever change the
middleware, all three layers must remain.

## PortBasedSessionIsolation is actually path-based

The class is named `PortBasedSessionIsolation`. Its current implementation
splits sessions based on whether the request path starts with `/admin` —
that's path-based, not port-based. The old port-based logic caused 419 CSRF
errors when the app and admin ran on the same port (e.g. `php artisan serve`).

Don't refactor based on the class name. The behavior is correct; only the
name is misleading. Renaming it is fine but mechanical (the middleware is
prepended in `bootstrap/app.php` and referenced nowhere else by class name).

## The Service Worker is intentionally disabled

`resources/js/app.ts` contains a block of code that immediately unregisters
all Service Workers on page load and clears all caches. The comment says it
was disabled to fix CSS loading issues — Vite's HMR was conflicting with the
SW caching the old bundle.

The SW code itself is in `public/sw.js`. Re-enabling it requires resolving
the cache-invalidation conflict with Vite's bundle hashing. Until then, leave
the unregistration block in place. If you remove it, expect users to see
stale CSS after deploys.

## The previous developer was on Windows + PowerShell

Many of the deleted docs (`RUN_ME_NOW.md`, `LOCAL_SETUP_COMMANDS.md`, etc.)
were full of PowerShell snippets. The repo also had `composer-setup.php` and
`composer.phar` committed because they were running Composer locally without
a system install. We've moved everything to a Mac/Linux-friendly bash workflow
(`scripts/dev-setup.sh`) and the Windows-isms are gone.

If you ever go back to Windows, WSL2 is a much smoother path than PowerShell.

## Things that look weird but are intentional

- **`DECIMAL(12,2)` for things that are integers** (`games.price_to_play` is
  `1`, `4`, etc.). Kept as decimal so historical USD data didn't need a
  destructive migration.
- **`failed scans still create a wallet transaction`** (see
  `docs/architecture.md`). Users pay regardless of outcome.
- **`PortBasedSessionIsolation` named misleadingly** (see above).
- **`storage/pail/` exists but is `.gitignore`d** — Laravel Pail uses it for
  log buffering. The `.gitignore` stub keeps the directory present after a
  fresh clone.
- **`bootstrap/cache/*.php` keeps wanting to commit** — Laravel regenerates
  these on every `composer install`. They're now properly `.gitignore`d.

## Things that look weird AND are actually broken

- **Some Filament UI labels print `$` for `price_to_play`** (see "Game prices
  used to be USD" above).
- ~~No real test coverage.~~ **Fixed in cleanup branch.** `tests/Feature/`
  now has `ScanFlowTest`, `WalletTransactionInvariantsTest`, `AuthFlowTest`,
  `Api/ApiEndpointsTest`, `MassAssignmentGuardTest`, `HealthAndLocaleTest` —
  ~38 tests covering the money paths. Run `composer test`.
- **No rate limiting on `POST /scan/{game}`.** Trivially abusable.
- **No rate limiting on `GET /winners`.** Less critical (data is meant to be
  public) but should still be throttled.
- **Empty stub methods** in `app/Http/Controllers/Api/GameController.php`
  (`store`, `show`, `update`, `destroy`) — registered nowhere, used by no one.
  Delete on sight.
- **The hardcoded support phone `71484833`** appears in three Vue page files.
  Extract to a config constant.
