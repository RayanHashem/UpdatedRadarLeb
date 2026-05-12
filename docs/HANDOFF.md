# RadarLeb — Cleanup Handoff

**From:** Abed El-Fattah Amouneh
**To:** Rayan Hashem
**Branch:** `cleanup/abed-radarleb`
**Date:** May 2026

This is the single document you need to merge, run, and ship the cleaned-up codebase. It covers what changed, why, what to do next, and how to keep things healthy. No need to read every other doc unless you want depth — links are at the bottom.

---

## 1. TL;DR

The codebase was inherited as a sprawling, hard-to-navigate Laravel 12 + Vue 3 app with security gaps, no tests, performance issues, and 249 stray files at the project root. Over this session I:

- Cut the cleanup branch from `updatedradarleb`
- Removed 249 misplaced files and reorganized the rest into proper Laravel directories
- Added security hardening (mass-assignment lockdown, form requests, throttle, CSPRNG for money decisions, `/db-check` gate)
- Added a 51-test PHPUnit suite covering the money path (scans, wallets, auth) — all green
- Refactored business logic into Action classes (single-purpose `__invoke()`)
- De-duplicated 5 copy-pasted Filament page classes into one abstract base + 5 thin subclasses
- Added i18n (English + Arabic), locale switcher, RTL-aware components
- Eager-loaded Filament resources (was N+1 query soup) and optimized hot paths
- Adapted the Figma design system (tokens, login page, dashboard polish, top bar, bottom action row, credits page)
- Built deploy infra for Render (free tier) + AWS RDS (or Supabase Postgres free)
- Added beginner-friendly AI-tooling files (`CLAUDE.md`, `AGENTS.md`, `.cursor/rules/*.mdc`) so Cursor / Claude / Codex give consistent help
- Added a Credits page (`/credits`) accessible from Settings
- Added `wallet:topup` artisan command for testing
- Added Windows PowerShell setup script alongside the existing bash one

**Test status:** `composer test` — **51 passed, 0 failed**, 1 skipped (in-memory sqlite warning, expected).

---

## 2. How to run the project (Windows)

You have two paths. Pick whichever feels more comfortable.

### Path A — WSL2 + Ubuntu (recommended)

This gives you the same environment Abed and the production server use, so any docs / scripts that say "run `bash scripts/dev-setup.sh`" just work.

**One-time WSL install (5 min):**

```powershell
# Open PowerShell AS ADMINISTRATOR, run this once:
wsl --install -d Ubuntu
# Restart your computer when prompted. After reboot, Ubuntu finishes setup
# and asks you to create a Linux username + password.
```

**Then in the Ubuntu terminal:**

```bash
# Install PHP 8.4, Composer, Node, Git
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt install -y php8.4 php8.4-cli php8.4-mbstring php8.4-xml php8.4-sqlite3 php8.4-curl php8.4-zip php8.4-bcmath
sudo apt install -y composer git unzip
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Clone the repo INSIDE your WSL home (not /mnt/c/) — much faster
cd ~
git clone https://github.com/RayanHashem/RadarLeb.git
cd RadarLeb
git checkout cleanup/abed-radarleb

# One-shot setup
bash scripts/dev-setup.sh

# Run the app
composer run dev
```

Open `http://localhost:8000` in Windows Chrome. WSL forwards localhost automatically.

**Editor:** install VS Code on Windows, install the "WSL" extension, then from Ubuntu run `code .` inside the project — VS Code opens connected to WSL.

### Path B — Native Windows (Laragon or Herd)

If you'd rather not touch WSL.

**Easiest: install [Herd Windows](https://herd.laravel.com)** — free, one installer, bundles PHP 8.4 + Composer + Node + nginx. Once installed, all the `php` / `composer` / `node` commands work from any Windows terminal.

**Alternative: [Laragon](https://laragon.org)** — same idea, slightly different UI, also free. Pick the "Full" download.

**Then:**

```powershell
# In PowerShell, in the project root:
cd C:\path\to\RadarLeb

# Allow scripts for this session only (safe; doesn't change global policy):
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

# One-shot setup (Windows version of dev-setup.sh)
.\scripts\dev-setup.ps1

# Run the app
composer run dev
```

`composer run dev` uses `npx concurrently` which works on Windows out of the box — it boots `php artisan serve`, the queue listener, the log tail, and Vite all in one terminal with color-coded prefixes.

**Three caveats on the native Windows path:**

1. **PHP must be 8.4, not 8.5.** Three dependencies (`nette/schema`, `nette/utils`, `openspout`) cap at 8.4. Herd ships 8.4 by default; Laragon lets you pick.
2. **Line endings.** Configure git to handle them: `git config --global core.autocrlf input`. Otherwise `.sh` scripts get CRLF-mangled if anyone opens them in Notepad.
3. **`bash scripts/dev-doctor.sh` won't run on native Windows** (no bash). Use `.\scripts\dev-setup.ps1` instead, or just run the equivalent commands manually:
   - `php --version` (should be 8.4.x)
   - `composer --version`
   - `node -v` (should be v20+)
   - `php artisan --version` (should be Laravel 12.x)

---

## 3. Daily development cheat-sheet

| What you want | Command |
|---|---|
| Start everything | `composer run dev` |
| Public app | `http://localhost:8000` |
| Filament admin | `http://localhost:8000/admin` |
| Vite dev server (don't open directly) | `http://localhost:5173` |
| Run all tests | `composer test` |
| Run one test file | `php artisan test --filter=ScanFlowTest` |
| Lint JS/TS/Vue | `npm run lint` |
| Format PHP | `.\vendor\bin\pint` (Windows) or `./vendor/bin/pint` (WSL/Mac) |
| Apply pending migrations | `php artisan migrate` |
| Reset DB and re-seed (LOCAL ONLY) | `php artisan migrate:fresh --seed` |
| Generate a Filament resource | `php artisan make:filament-resource ModelName` |
| Tail logs | `php artisan pail` |
| Top up your wallet for testing | `php artisan wallet:topup <phone-or-email> 100` |
| Reset an admin password | `php artisan admin:set-password admin@example.com NewP@ss123!` |

**Stack at a glance:** Laravel 12 + Filament 3 (admin) + Inertia 2 + Vue 3 + Tailwind 4 + Vite 6 + SQLite (local) / PostgreSQL (prod).

---

## 4. What was changed, by phase

Each phase is a self-contained set of improvements. They were applied in order; later phases assume earlier ones.

### Phase 1 — Architecture refactor

- Pulled all closure-based routes out of `routes/web.php` into proper controllers in `app/Http/Controllers/`.
- Moved 9 misplaced Laravel config files from project root into `config/`.
- Deleted 249 stray files at the project root (cached output, old screenshots, `composer.phar`, log files, build artifacts). The whitelist of legitimate root files is `scripts/cleanup-phase-2.sh`.
- Replaced `env()` calls scattered through the code with `config('section.key')` reads. `env()` only ever runs inside `config/*.php` now (Laravel best practice — config caches break otherwise).

### Phase 2 — Internationalization

- Added Arabic translation files in `resources/lang/ar/` and English in `resources/lang/en/`.
- Built a `useTranslate` composable (`resources/js/composables/useTranslate.ts`) and a `useDirection` composable for RTL handling.
- Built a `LocaleSwitcher.vue` component (the EN/AR pill in the top bar).
- Locale persists via session; `POST /locale/{locale}` writes it server-side.
- Phone-number entry now respects locale-specific input modes.

### Phase 3 — Security lockdown

- Added `$fillable` to every Eloquent model. Mass assignment is now opt-in per attribute. New tests in `MassAssignmentGuardTest` lock this in.
- Created Form Request classes for every controller mutation endpoint (registration, login, scan, etc.) — validation moved out of controllers into dedicated request classes.
- Added throttle middleware to API + auth endpoints (`60,1` for normal, tighter for login/register).
- Replaced `mt_rand()` with `random_int()` for any decision that touches money or wins. CSPRNG matters here; `mt_rand` is predictable.
- Hardened the `/db-check` health endpoint with a secret-token gate (`HEALTH_CHECK_SECRET` env var). Was previously leaking DB metadata to anyone who knew the URL.
- `DevAutoAuth` middleware (the local-only auto-login helper) has three layers of defense: env check, host check, and not even being registered when not local. See `docs/security.md`.

### Phase 4 — Money-path tests

A 51-test PHPUnit suite, all green:

- `tests/Feature/ScanFlowTest.php` — happy path, insufficient balance, disabled game, atomic write, etc.
- `tests/Feature/WalletTransactionInvariantsTest.php` — `amount` must equal `scans.cost` within 1¢, `type` lowercased, top-ups have `game_id NULL`.
- `tests/Feature/AuthFlowTest.php` — register / login / logout, under-18 rejection, unique phone.
- `tests/Feature/Api/ApiEndpointsTest.php` — every API endpoint requires auth, pagination bounds, etc.
- `tests/Feature/MassAssignmentGuardTest.php` — locks down the `$fillable` decisions.
- `tests/Feature/HealthAndLocaleTest.php` — `/db-check` gate + locale switcher.
- `tests/Feature/LegalPagesTest.php` — `/terms`, `/privacy`, `/credits` all return 200, named routes registered.
- `tests/Unit/Actions/Game/CheckWinEligibilityTest.php` — top-3 + pool-threshold logic.
- `tests/Unit/Actions/Wallet/TopOffWalletTest.php` — credits + transaction row + invariants.

### Phase 5 — Performance

- Added `with()` eager-loading to every Filament resource's `getEloquentQuery()`. The Users-by-Game pages were running N+1 queries on every render.
- Optimized `canUserWinFinal()` with cached aggregate queries.
- Moved Bungee font preconnect from per-page to `app.blade.php` (one DNS lookup per session, not per Inertia visit).

### Phase 6 — Deploy infrastructure

- Multi-stage `Dockerfile` (Node → Vite build → Composer → `serversideup/php:8.4-fpm-nginx`).
- `render.yaml` blueprint for Render free-tier deployment.
- `docs/deploy-render.md` (and `docs/deploy.md`) — full walkthrough including AWS RDS migration if she stays on RDS, or Supabase Postgres free tier as an alternative.

### Phase 7 — Industry-standard architecture

- **7.1** Extracted business logic into Action classes (`app/Actions/Game/AttemptScan.php`, `BuildGameProgress.php`, `CheckWinEligibility.php`, `Wallet/TopOffWallet.php`, `User/RegisterUser.php`). Each is a single-purpose invokable. Controllers became thin again.
- **7.2** Replaced 5 copy-pasted "Users by Game" Filament pages (300 lines of duplication) with one abstract `UsersByGamePage` base + 5 thin subclasses overriding `gameName()`. Total: 161 lines.
- **7.3** Broke up the 1500-line `Dashboard.vue` into composables and overlay components:
  - `resources/js/components/dashboard/HelpOverlay.vue`
  - `resources/js/components/dashboard/WinnersOverlay.vue`
  - `resources/js/composables/useTranslate.ts`, `useDirection.ts`, `useOverlay.ts`
- **7.5** Added unit tests for Actions, updated Feature tests to match the new route structure.

### Phase 8 — Cost optimization

- Migrated off the EC2 deploy path your client was paying $30/mo for.
- Audited the AWS bill — `docs/deploy-render.md` includes the full audit walkthrough so she knows what services to delete.
- New deploy target: Render free tier (web service) + Supabase Postgres free tier OR AWS RDS db.t4g.micro (still cheap).

### Phase 10 — Figma design adaptation

- **10a** Extracted design tokens to `resources/css/tokens.css` — CSS custom properties (`--rl-color-navy`, `--rl-color-cyan`, etc.) and a Tailwind 4 `@theme` block that generates utility classes (`bg-radar-navy`, `text-radar-cyan`).
- **10b** Adapted the Login page to match Figma "Big Screen" + "Small Screen" — desktop logo top-right, form right-anchored; mobile centered.
- **10c+** Adapted Register, Legal (Terms/Privacy/Credits), ForgotPassword. Added the prefill bridge from Login → Register so a user who started typing on /login doesn't re-type on /register.
- **10d** Polished the dashboard:
  - Top status dot shrunk from 40px → 12px, halo balanced
  - Radar circle now perfect circle with rim glow + concentric rings + crosshair pseudo-elements
  - Thermometer (antenna detection bar) got 6 tick marks via repeating-linear-gradient
  - Scan button became a wide pill with cyan glow
  - Prize tiles got hover + selected states
  - Top-bar icons got text labels (Winners / Help / Settings / Logout) per Figma
  - Bottom action row matched Figma: wide SCAN flanked by square cyan tile buttons, no labels
  - RADAR CASH balance moved from under the store icon to a top-bar pill (still live, still updates on scan/top-up)
  - Locale switcher moved into the top bar (was floating + overlapping LOGOUT)

### Phase 11 — Credits + final testing

- Added `/credits` route, `LegalController::credits()` method, extended `Legal.vue` to handle the `credits` type.
- Linked Credits from the Settings overlay.
- Added `wallet:topup` artisan command (wraps `TopOffWallet` action; accepts phone or email).
- Visual click-through verified: dashboard, scan flow, settings → credits, winners, help, locale toggle.
- All 51 PHPUnit tests still green after every change.

---

## 5. The 10 things you must remember about this codebase

1. **`games.price_to_play` is Radar Cash units** — values are `1, 4, 8, 24, 32`. Mobile scans debit 1 Radar Cash.
2. **`PortBasedSessionIsolation` is actually path-based** — class name is misleading. Don't refactor based on the name.
3. **`DevAutoAuth` has 3 layers of defense** — env check + host check + not registered. Keep all three.
4. **`wallet_transactions.amount` MUST equal `scans.cost`** — 1¢ tolerance, validated in `WalletTransaction::boot()`. Don't bypass.
5. **Migrations must run on both SQLite and Postgres** — no Postgres-only raw SQL. We just rewrote 4 of them to be portable.
6. **Two auth guards: `web` (public) and `admin` (Filament)** — keep them separate.
7. **Public UI = Inertia/Vue. Admin UI = Filament/Livewire.** Don't cross-render.
8. **Service Worker is intentionally disabled** in `resources/js/app.ts` — re-enabling it requires resolving a Vite cache-invalidation conflict. See "Future improvements" below.
9. **Money-path tests live in `tests/Feature/`** — keep them green; if you add money-touching code, add tests.
10. **PHP 8.4, not 8.5** — three deps cap at 8.4.

---

## 6. Post-merge action items (do these before the next deploy)

These are **required**, not optional. They protect the live site.

### 6.1 Rotate AWS RDS credentials (security-critical)

Your old RDS credentials were exposed in the EC2 setup that I deprecated. Before reusing the same database from Render, rotate them.

In AWS Console → RDS → your database → Modify → Master password → set a new strong password → Apply immediately.

Then update the `DATABASE_URL` (or `DB_*`) env vars in Render with the new credentials.

### 6.2 Delete the Vercel project

You're no longer deploying to Vercel. To stop accidental deploys (and potential cost):

- vercel.com → your project → Settings → Advanced → Delete Project.
- Also delete `vercel.json` and the `api/` folder from the repo if they still exist (I think I removed them; double-check).

### 6.3 Set the production secrets in Render

In Render dashboard → your web service → Environment, add:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=...                    # generate locally with: php artisan key:generate --show
APP_URL=https://your-render-url
DB_CONNECTION=pgsql
DATABASE_URL=postgres://...    # from RDS or Supabase
HEALTH_CHECK_SECRET=...        # any 32+ char random string; needed for /db-check?secret=...
APP_TRUSTED_PROXIES=*          # Render sits behind a load balancer
```

Generate secrets locally:

```bash
# In WSL or PowerShell:
php artisan key:generate --show          # APP_KEY
openssl rand -hex 32                     # HEALTH_CHECK_SECRET (or use any password generator)
```

### 6.4 Run migrations on the production database

After Render is wired to RDS/Supabase but before opening to users:

```bash
# From your local machine, with DATABASE_URL pointed at production:
php artisan migrate --force
```

Or via Render's shell (the web service has a "Shell" tab in the dashboard).

### 6.5 Smoke-test the live deploy

```bash
curl https://your-render-url/                                    # → 200 OK
curl https://your-render-url/db-check?secret=YOUR_SECRET         # → {"status":"ok"}
curl https://your-render-url/db-check                            # → 404 (gate working)
```

If `/db-check` returns anything other than 200 with the right secret or 404 without, something's wrong — check Render logs.

### 6.6 Pull cleanup/abed-radarleb into your local main flow

After the merge:

```bash
git checkout main                  # or updatedradarleb if that's your default
git pull
git branch -d cleanup/abed-radarleb # delete the local cleanup branch (optional)
```

---

## 7. Ongoing development recommendations

How to work in this codebase going forward.

### 7.1 Adding a new feature

1. Read `CLAUDE.md` (project-root, ~300 lines) — it tells AI tools and humans alike where things go.
2. Read the relevant `.cursor/rules/*.mdc` file for what you're touching:
   - Migrations → `.cursor/rules/migrations.mdc` (must run on SQLite + Postgres)
   - Filament resources → `.cursor/rules/filament.mdc`
   - Tests → `.cursor/rules/tests.mdc`
   - Backend code → `.cursor/rules/backend.mdc`
   - Frontend code → `.cursor/rules/frontend.mdc`
3. If your feature touches money (scans, wallets, wins), **add a test**. Same directory pattern as the existing Feature tests.
4. New business logic should live in an Action class in `app/Actions/{Domain}/{Verb}.php`. Controllers stay thin.
5. New API endpoints go in `routes/api.php`. New web pages in `routes/web.php`. New auth flows in `routes/auth.php`.

### 7.2 Test discipline

- Run `composer test` before every commit. Takes ~2 seconds.
- If a money-path test fails, **stop**. Don't bypass it.
- Add tests when:
  - You touch any wallet / scan / win logic
  - You add a new HTTP endpoint
  - You add a new Action class
  - You change a migration that affects money columns

### 7.3 Dependency updates

- **Don't update PHP past 8.4.** Three deps cap there.
- Monthly: `composer outdated` and `npm outdated` to see what's available. Update non-breaking bumps freely.
- Major Laravel/Filament version bumps need a dedicated branch + full test run + manual click-through.
- After any `composer update`, run `php artisan filament:upgrade` to republish Filament's frontend assets.

### 7.4 Keeping the Figma design in sync

- Color, spacing, font tokens live in `resources/css/tokens.css` (CSS custom properties + Tailwind 4 `@theme`).
- New page styling: reach for `var(--rl-color-cyan)`, `var(--rl-color-coral)`, etc. **Don't hardcode hex values.**
- New utility classes: use the Tailwind ones (`bg-radar-navy`, `rounded-radar-pill`). They're auto-generated from the tokens.
- If you change a token, every place using it via the variable updates automatically. If you change a hex inline, you've created a divergence — fix the template to use the var instead.

### 7.5 CI/CD (suggested next step)

Currently there's no CI. When you have time, add a GitHub Actions workflow that runs `composer test` + `npm run lint` on every push. Stub:

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.4' }
      - uses: actions/setup-node@v4
        with: { node-version: '20' }
      - run: composer install --no-progress
      - run: npm ci
      - run: composer test
      - run: npm run lint
```

This catches regressions before they hit `main`. Render also has built-in deploy hooks if you want auto-deploy on push.

---

## 8. Future improvements wishlist

Things I noticed but didn't fix. None are blockers.

### High-value, low-effort

- **Filament UI should not use `$`** for `price_to_play` because it is Radar Cash units.
- **Translation gaps.** The Winners overlay shows `USER.NAME01` / `WINNER DRAW 1 - BIKE` in English mode — those are i18n keys that haven't been wired to live data. Wire them to actual winner records or fill the lang files with realistic placeholders.
- **`Dashboard.vue` loads in idle radar state, then re-renders** when props arrive. There's a brief flash of the loading spinner on every navigation back to `/`. Fix by initializing `loading: false` in `setup()` once props are confirmed non-null.

### Medium-effort

- **Service Worker is disabled** in `resources/js/app.ts` (it actively unregisters anything previously registered). Re-enabling needs a Vite cache-invalidation strategy that doesn't break offline. Worth doing for PWA/install support.
- **RTL on the dashboard isn't fully wired.** Switching to AR flips the locale state but the dashboard layout doesn't mirror — labels, antenna bar, prize column placement stay LTR. The auth pages handle RTL correctly; the dashboard needs `dir="rtl"`-aware overrides on its grid templates.
- **The duplicate Vue pages at `resources/js/pages/Login.vue`, `Register.vue`, `ConfirmPassword.vue`, `VerifyEmail.vue`, `Password.vue`, `Profile.vue`, `DeleteUser.vue`, `Layout.vue`** are orphans — controllers render `auth/Login` etc. The duplicates ship to clients in dev (lazy-globbed) but never get imported. Delete them in a follow-up PR; risky to do in this one.
- **`PortBasedSessionIsolation` middleware is misnamed** — it's path-based. Rename to `PathBasedSessionIsolation` and update all references.

### Larger projects

- **Move winner-draw email notifications to a queue.** Currently synchronous; if email is slow, the request hangs.
- **Add a real admin audit log.** Filament has actions but no audit trail. Useful when more than one person touches the admin panel.
- **Replace the `/db-check` secret-string gate with a proper IP allowlist + Render's built-in health checks.** The secret works but rotating it is annoying.

---

## 9. Deployment runbook

### First-time deploy to Render

1. **Sign in to Render** with your GitHub account.
2. **New → Blueprint** → connect this repo (`RayanHashem/RadarLeb`) → select branch (after merge, this should be `main` or `updatedradarleb`).
3. Render reads `render.yaml` from the repo root and provisions the service.
4. **Set the secrets** (see §6.3 above) in the web service's Environment tab.
5. **Wait for the first build.** ~5 min. If it fails, the most common reason is a missing env var; check the build log.
6. **Run migrations.** Either via Render's Shell tab (`php artisan migrate --force`) or from your local machine pointed at production.
7. **Smoke-test** (see §6.5).
8. **Set a custom domain** if you have one. Render handles SSL via Let's Encrypt automatically.

Full walkthrough: `docs/deploy-render.md`.

### Subsequent deploys

Render auto-deploys on every push to the deploy branch. You don't need to do anything — git push triggers the build.

To deploy without a code change (e.g. after env-var update):

- Render dashboard → your web service → Manual Deploy → Deploy latest commit.

### Rollback

If a deploy breaks production:

1. Render dashboard → your web service → Events tab.
2. Find the last green deploy.
3. Click "Rollback" on it.

Rollback takes ~30 seconds. The DB is unchanged — only the application code rolls back. **If a migration was the problem, you'll need to roll the DB back too:**

```bash
# From a local machine pointed at production DB:
php artisan migrate:rollback --step=1
```

### When something breaks in prod

1. **Check Render logs first.** Dashboard → your web service → Logs tab. Real-time, searchable.
2. **Check `/db-check?secret=...`.** If that returns 200, the DB connection is healthy.
3. **Tail the queue.** `php artisan queue:failed` from Render's shell shows failed jobs.
4. **Filament admin → Wallet Transactions** is the source of truth for money state. If anything looks wrong with a user's balance, that table tells you what happened.
5. **If you can't figure it out in 10 min, rollback first, debug second.** Production stability > finding the root cause.

---

## 10. Useful files at a glance

| File | What it is |
|---|---|
| `CLAUDE.md` (project root) | Onboarding doc for AI tools and humans. Read this first when picking the project up after a break. |
| `AGENTS.md` (project root) | Same content, for OpenAI Codex. |
| `.cursorrules` (project root) | Same content, for Cursor. |
| `.cursor/rules/*.mdc` | Per-domain rules: migrations, filament, tests, security, backend, frontend. |
| `docs/architecture.md` | Domain model — scans, wallets, draws, prizes, the money invariants. |
| `docs/security.md` | The full security model: auth guards, CSPRNG, DevAutoAuth, mass assignment, etc. |
| `docs/deploy.md` | EC2/RDS deploy notes (mostly historical now). |
| `docs/deploy-render.md` | Render + RDS/Supabase deploy guide (current). |
| `docs/history.md` | Why things are the way they are — non-obvious decisions documented. |
| `scripts/dev-setup.sh` | Mac/Linux/WSL one-shot setup. |
| `scripts/dev-setup.ps1` | Windows native one-shot setup (added in this branch). |
| `scripts/dev-doctor.sh` | What's installed on this machine? (bash; run in WSL on Windows) |
| `scripts/smoke-test.sh` | Post-deploy sanity check (bash; run in WSL or from CI). |

---

## 11. If you get stuck

1. `bash scripts/dev-doctor.sh` (WSL/Mac) or check tools manually (Windows native) — tells you what's installed.
2. `tail -f storage/logs/laravel.log` (WSL/Mac) or `Get-Content -Wait storage\logs\laravel.log` (PowerShell) — real-time error log.
3. `php artisan config:clear; php artisan cache:clear; php artisan view:clear` — clears Laravel's caches when something feels stale.
4. `composer dump-autoload` — fixes "Class not found" after moving files around.
5. `npm run build` — rebuilds the Vite manifest. Useful if assets stop loading after a `git pull`.
6. `php artisan migrate --pretend` — preview pending migrations without running them.

If none of that helps, the codebase has a non-trivial test suite — `composer test` is fast (~2s) and usually pinpoints what regressed.

---

## 12. Final notes from Abed

- Take your time with §6 (post-merge actions). Especially the credential rotation. Doing it on a calm afternoon is much better than doing it in panic.
- The `composer test` suite is your safety net. Run it often.
- The `.cursor/rules/*.mdc` files mean Cursor / Claude Code give consistent help to anyone working on this — including future-you in 6 months. Keep them updated when you change the architecture.
- Welcome aboard. The codebase is in much better shape than it was; treat the test suite kindly and it'll treat you kindly back.

— Abed
