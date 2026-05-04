# Architecture & Domain Model

How RadarLeb works under the hood. Read this before changing anything in
`app/Models/Game.php`, `app/Models/WalletTransaction.php`, or anywhere wallet
math happens.

## The product, in one paragraph

RadarLeb is a sweepstakes-style game. A user signs up with their phone number,
adds Radar Cash to their wallet (admin tops them up — there's no public payment
flow yet), then "scans" to play one of five games. Each scan deducts a fixed
amount of Radar Cash from their wallet, contributes to that game's prize pool,
and may produce a winner during a Draw. Admins manage everything (users, games,
draws, scans, winners, top-ups) through a Filament admin panel at `/admin`.

## The five games

| Game name           | Cost per scan (radar units) |
|---------------------|-----------------------------|
| Mobile              | 1                           |
| Bike & Electronics  | 4                           |
| SUV                 | 8                           |
| Muscle Car          | 24                          |
| Super Cash Prize    | 32                          |

Source of truth: `database/seeders/GameSeeder.php` and the migration
`2026_04_26_000001_fix_games_price_to_play_radar_per_scan.php`.

**Important semantic change you must know:** `games.price_to_play` used to be a
USD dollar amount and is now a **radar unit count** (an integer-shaped decimal:
1, 4, 8, 24, 32). Some Filament UI labels still print a `$` prefix — that's a
known bug, not the source of truth. See `docs/history.md`.

## The core flow: `Game::attemptScan()`

When a user taps "scan" on game X, the flow is:

```
1. Check user has enough Radar Cash for this game's price_to_play
2. Roll the RNG  (Game::roll() — currently mt_rand-based, intentionally unseeded)
3. Insert a row into `scans`:
     user_id, game_id, cost = price_to_play, success = true|false
4. firstOrCreate a row in `wallet_transactions`:
     user_id, scan_id (UNIQUE), game_id, amount = cost, type = 'debit'
5. Update the game's current_amount pool
6. If a winner roll succeeds → insert a `winners` row
```

This whole flow lives in `app/Models/Game.php::attemptScan()` (the one
non-trivial method on the model — read it before touching it).

## Data model: who relates to what

```
users ───┬─── scans ───── games
         │       │
         │       └─── wallet_transactions (1:1 by scan_id)
         │
         └─── wallet_transactions (1:many)
                 ▲
                 │
                 └── topups (transactions where game_id IS NULL, type = 'topup')
```

Models live in `app/Models/`:
- `User.php` — public-facing users, `web` guard
- `AdminUser.php` — Filament admins, `admin` guard
- `Game.php` — the five games. Holds `price_to_play`, `target_amount`, `current_amount`, `image_path`
- `Scan.php` — every scan attempt (success or failure)
- `WalletTransaction.php` — money in/out of a user's wallet
- `Winner.php` — recorded prize wins
- `Draw.php` — periodic draws
- `Device.php`, `UserSession.php`, `GameUserStat.php`, `SystemSetting.php` — supporting

## Wallet rules (these are invariants — don't break them)

1. **`wallet_transactions.amount` MUST equal `scans.cost`** for any row where
   `scan_id` is set. The `WalletTransaction::boot()` hook validates this with
   a 1¢ tolerance for floating-point rounding. There's a backfill migration
   (`2026_02_19_000005_fix_wallet_transaction_amount_mismatches.php`) because
   old data had drifted.

2. **One transaction per scan.** `wallet_transactions.scan_id` has a UNIQUE
   partial index (`WHERE scan_id IS NOT NULL`). Inserts go through
   `firstOrCreate` keyed on `scan_id`.

3. **Top-ups have `game_id = NULL`.** A top-up transaction is admin-created and
   not tied to any game. The form is in `UserResource::topoffRadarCash` action.

4. **`type` is normalized lowercase.** `'debit'`, `'topup'`, `'adjustment'`,
   `'refund'`. The model's `boot()` hook lower-cases anything you save. Old
   data with `'Scan'`, `'TopUp'`, etc. was normalized by migration `000004`.

5. **Failed scans still produce a transaction.** If RNG decides the scan
   doesn't win, the `scans` row has `success = false` BUT the user still pays
   (`wallet_transactions` row gets created normally). This is intentional —
   playing costs money regardless of outcome.

6. **A user can spend on multiple games.** The "users by game" pages in
   Filament filter by `wallet_transactions.game_id`, not the user's current
   selection (`users.game_id`). One user can show up under several game pages
   if they've spent across them. See `docs/history.md` for why this changed.

## Two authentication guards

The app has two completely separate logins:

- **`web` guard** — public users (`App\Models\User`). Phone + password.
  Configured in `config/auth.php`. Controllers live under
  `app/Http/Controllers/Auth/` (standard Laravel breeze-flavored).

- **`admin` guard** — Filament admins (`App\Models\AdminUser`). Email + password.
  Filament owns the login flow.

The two guards write their session cookies to different paths. The middleware
`app/Http/Middleware/PortBasedSessionIsolation.php` keeps them from clobbering
each other when both run on the same host.

> **Naming gotcha:** the class is named `PortBasedSessionIsolation` but the
> implementation is actually **path-based** now (it splits `/admin/*` from
> everything else). The old port-based logic caused 419 CSRF errors. The class
> name is misleading; don't refactor based on the name alone.

## Frontend: Inertia + Filament cohabit

- **Public site** (`/`, `/login`, `/dashboard`, `/scan`, `/winners`) is **Vue 3
  + Inertia 2**. Pages live in `resources/js/pages/`. The Inertia entry point is
  `resources/js/app.ts`. Inertia turns Laravel routes into SPA-feeling page
  visits without a separate API.

- **Admin panel** (`/admin/*`) is **Filament 3**, which is **Livewire** under
  the hood. You don't write Vue components for the admin — you write Filament
  resources, pages, and widgets in PHP. Filament publishes its compiled assets
  into `public/{js,css}/filament/` (regenerate with `php artisan filament:upgrade`).

- **They don't talk to each other.** Don't try to render Inertia/Vue inside a
  Filament page. Don't try to mount Filament inside an Inertia layout. They
  share routes/middleware but render through totally different pipelines.

- **CSRF 419 in Inertia** is handled centrally in `bootstrap/app.php` — when
  Inertia hits a 419, it issues a full location visit to refresh the session
  cookie. Don't reinvent this in components.

## Money & decimals

- **Decimal type, not float.** `wallet_transactions.amount`, `scans.cost`,
  `games.price_to_play`, `games.target_amount`, `games.current_amount` are all
  `DECIMAL(12,2)` columns. Cast to `decimal:2` on the models.

- **No payment integration yet.** All money flows through admin top-ups. The
  user has no way to add Radar Cash on their own.

- **Failed scans still cost money** (see wallet rule #5).

## Where new code goes

| Adding a business operation (anything that mutates state) | `app/Actions/<Domain>/<Verb>.php` — invokable, single-purpose. Controllers / Filament actions delegate to it. Examples: `AttemptScan`, `TopOffWallet`, `RegisterUser`. |
| Adding a... | Goes in |
|---|---|
| New API endpoint | `app/Http/Controllers/Api/<Name>Controller.php` + `routes/api.php` |
| New web page (Inertia) | `resources/js/pages/<Name>.vue` + `Inertia::render('Name')` in `routes/web.php` |
| New admin resource | `php artisan make:filament-resource <Model>` |
| New admin page (custom) | `app/Filament/Pages/<Name>.php` |
| New admin widget | `app/Filament/Widgets/<Name>.php` |
| New Eloquent model | `php artisan make:model <Name> -mf` (model + migration + factory) |
| Schema change | `php artisan make:migration <description>` — keep portable! |
| Reusable Vue component | `resources/js/components/<Name>.vue` (UI primitives in `ui/<kind>/`) |

Do not add files at the project root. Don't even feel tempted. We just deleted
249 of those.
