# RadarLeb – Pre-deploy checklist & mobile audit

Laravel + Vue (Inertia) + Filament, AWS (RDS Postgres, EC2/Nginx). Main app + `/admin` panel.

---

## Part A — Pre-deploy audit

### 1. .env correctness for production

| Item | Status | Notes |
|------|--------|--------|
| `APP_ENV=production` | **Set** | Must be `production` on staging/prod. |
| `APP_DEBUG=false` | **Set** | Never `true` in production. |
| `APP_URL` | **Set** | Full URL, e.g. `https://radarleb.com` (no trailing slash). |
| `APP_KEY` | **Set** | From `php artisan key:generate`; keep secret. |
| DB_* (Postgres) | **Set** | RDS host, port, database, user, password. |
| `SESSION_DRIVER` | **OK** | `file` or `database`/`redis` for multi-instance. |
| `CACHE_STORE` | **OK** | `file` or `redis` for production. |
| `QUEUE_CONNECTION` | **Optional** | `sync` for single server; `database`/`redis` + Supervisor if using queues. |
| Mail (MAIL_*) | **Set** | Required for forgot-password; use real SMTP in prod. |
| `HEALTH_CHECK_SECRET` | **Optional** | Set in prod if you want to call `/db-check?secret=xxx`. |

Use `.env.example` as a template; never commit `.env`.

---

### 2. Config caching strategy

| Command | When |
|--------|------|
| `php artisan config:cache` | After deploy / after .env changes (run once). |
| `php artisan route:cache` | After deploy. |
| `php artisan view:cache` | After deploy. |
| `php artisan optimize` | Bundles optimize + config + route + view (Laravel 11+). |

After changing `.env`, run `php artisan config:clear` (or `optimize:clear`) then re-cache.

---

### 3. Storage & permissions

| Check | Action |
|-------|--------|
| `storage/app/public` | Symlink: `php artisan storage:link` (once per deploy target). |
| `storage/logs` | Writable by web server (e.g. `chown www-data:www-data -R storage bootstrap/cache`). |
| `bootstrap/cache` | Writable for config/route/view cache. |

---

### 4. Queues / cron

| Item | Status | Action |
|------|--------|--------|
| Scheduler | **Not used** | `routes/console.php` has no `Schedule::`; no cron needed for scheduler. |
| Queues | **Optional** | Dev uses `queue:listen`; prod: if using queues, run `queue:work` via **Supervisor** and set `QUEUE_CONNECTION=database` or `redis`. |

---

### 5. Security

| Item | Status | Notes |
|------|--------|--------|
| HTTPS | **Required** | Terminate at Nginx/ALB; set `APP_URL=https://...`. |
| Trusted proxies | **Configured** | Set `APP_TRUSTED_PROXIES=*` (or comma-separated IPs) in prod; `bootstrap/app.php` passes it to `trustProxies(at: ...)`. |
| CORS | **Default** | Laravel API routes; adjust in config if you add a separate SPA domain. |
| Rate limiting | **Default** | Throttle on API if needed; auth routes use Laravel’s throttle. |
| No debug | **Enforce** | `APP_DEBUG=false` in production. |
| Error pages | **Default** | Laravel’s 404/500; ensure debug off so stack traces are not shown. |
| `/db-check` | **Secured** | Returns 404 in production unless `?secret=HEALTH_CHECK_SECRET` is passed. |

---

### 6. Performance

| Item | Action |
|------|--------|
| OPcache | Enable in PHP for production. |
| Composer | `composer install --no-dev --optimize-autoloader` on deploy. |
| Frontend | `npm ci && npm run build` (Vite); serve built assets. |
| Response caching | Add where needed (e.g. winners list, radar status); not implemented by default. |

---

### 7. Database

| Item | Status |
|------|--------|
| Migrations | Run `php artisan migrate --force` on deploy. |
| Seeds | Run only if needed (e.g. admin user); document in runbook. |
| Connection | Postgres via `DB_*`; default Laravel pgsql connector. Optional: for RDS `connect_timeout`, re-enable `App\Database\Connectors\PostgresConnector` in `AppServiceProvider` and add `connect_timeout` to pgsql config. |
| Connection pooling | Optional (e.g. PgBouncer); not required for single app instance. |

---

### 8. Health check & monitoring

| Endpoint | Purpose |
|----------|--------|
| `GET /up` | Laravel default health (e.g. for ALB/ECS). |
| `GET /db-check?secret=HEALTH_CHECK_SECRET` | Optional DB check in production (returns JSON). |

Logs: `storage/logs/laravel.log`; ensure log level and rotation (e.g. `LOG_LEVEL=warning` in prod).

---

### 9. Filament admin

| Item | Status |
|------|--------|
| Admin path | `/admin`; login at `/admin/login`. |
| Guard | Default web guard; admin users must exist (e.g. seeded). |
| Session isolation | **Fixed**: admin uses cookie `radarleb_admin_session` with path `/admin`; main uses `radarleb_main_session` with path `/`. Both can be active in same browser; CSRF/login/redirects unaffected (middleware runs before StartSession). |
| Asset build | Same Vite build as main app; Filament panel uses its own assets; ensure `npm run build` runs so manifest is correct. |

---

## Part B — Mobile responsiveness audit

### Main web app (Vue/Inertia + Bootstrap + custom CSS)

| Check | Status | Notes |
|-------|--------|--------|
| 360px width (narrow phones) | **Addressed** | New breakpoint in `resources/css/app.css`: overlay, auth forms, bar, buttons. |
| Layout overflow / horizontal scroll | **Addressed** | Overlay and form containers use `max-width: 100%`, padding reduced at 360px. |
| Tables | N/A | Main app has no data tables; dashboard is radar UI + overlays. |
| Modals / overlays | **Addressed** | Help, Winners, Settings overlays and `GameModal.vue` use responsive padding and width; 360px styles added. |
| Navbar (top bar) | **OK** | Bar already responsive; 360px reduces logo and icon sizes, gap. |
| Forms (login, register, forgot-password, settings) | **OK** | Auth uses existing breakpoints (768, 576, 380px); 360px block added for form container and inputs. |
| Password toggles / errors | **OK** | Existing; no change. |
| Responsive navigation | **N/A** | No hamburger; top bar with icons is used and fits on small screens. |

### Filament admin panel

| Check | Status | Notes |
|-------|--------|--------|
| Table columns on small screens | **Addressed** | Key resources use `->visibleFrom('md')` or `->visibleFrom('lg')` on less critical columns (ID, phone, dates, etc.) so tables are usable on mobile. |
| Column toggles | **OK** | Filament supports column visibility toggles; some columns already `toggleable(isToggledHiddenByDefault: true)`. |
| Actions (View / Top up / Edit) | **OK** | Filament 3 tables show actions in a responsive way (e.g. row actions); modals (e.g. Top up) are already usable on small screens. |
| Sidebar | **OK** | Panel has `sidebarCollapsibleOnDesktop()`; Filament’s sidebar collapses on mobile by default. |
| Clipped content / overflow | **Addressed** | Fewer columns via `visibleFrom()`; panel injects CSS at 414px for `.fi-ta-content` overflow-x and form inputs so tables/filters/modals don’t overflow. |

---

## Part C — Implemented changes (summary)

### Code and config

- **`.env.example`**  
  Added with production-safe placeholders: `APP_*`, `DB_*`, `SESSION_*`, `CACHE_*`, `QUEUE_*`, `MAIL_*`, `HEALTH_CHECK_SECRET`, `APP_TRUSTED_PROXIES`.

- **`app/Http/Middleware/PortBasedSessionIsolation.php`**  
  Session cookie name and path: path starting with `admin` (or port 8001) → `radarleb_admin_session` with `session.path = '/admin'`; else `radarleb_main_session` with path `/`. Admin cookie is only sent to `/admin`; both sessions can be active in same browser. CSRF, login, remember-me, redirects unchanged (middleware runs before StartSession).

- **`routes/api.php`**  
  `/db-check` returns 404 in production unless `?secret=HEALTH_CHECK_SECRET` is provided; in non-production or when secret matches, returns JSON DB status (no host in response for safety).

- **`resources/css/app.css`**  
  - New `@media (max-width: 360px)` for overlay content, auth forms, bar, menu items, logo, winner cards, help container.  
  - New `@media (max-width: 360px)` for `#sign-in`: form container width/padding, inputs, buttons.

- **`resources/js/components/GameModal.vue`**  
  - Modal content: `min-width: 0`, `box-sizing: border-box`.  
  - New `@media (max-width: 360px)` for overlay padding, modal padding, font sizes, stacked full-width buttons.

- **Filament resources (tables)**  
  - **UserResource**: ID, phone, Radar cash spent, Draw #, Created at → `visibleFrom('md')` or `visibleFrom('lg')`.  
  - **WinnerResource**: ID, phone, Draw # → `visibleFrom('md')`.  
  - **WalletTransactionResource**: ID, phone, Balance After, Scan ID → `visibleFrom('md')` or `visibleFrom('lg')`.  
  - **ScanResource**: ID, phone, Radar Lvl, Created at → `visibleFrom('md')`.  
  - **DrawResource**: ID, Winner, Winner Phone, opened_at, closed_at → `visibleFrom('sm')` / `visibleFrom('md')` / `visibleFrom('lg')` as appropriate.  
  - **GameResource**: Price, Price to Play, Draw #, Minimum amount, Prize progress → `visibleFrom('md')` or `visibleFrom('lg')`.

---

## Manual test plan

### Desktop (e.g. 1920×1080)

- **Main app:** Login, register, forgot password; dashboard (radar, scan, prize selection); overlays (Help, Winners, Settings, change password); game modal (e.g. win flow).  
- **Admin:** `/admin` login; list Users, Prizes, Winners, Scans, Draws, Transactions; View user, Top up; edit prize.

### Tablet (e.g. 768px width)

- Same flows; confirm overlays and forms are usable; admin tables readable.

### Phone (e.g. 360px width)

- **Main app:** Sign in, register, forgot password (no horizontal scroll); dashboard; open Help, Winners, Settings; change password; trigger game modal – all fit and readable.  
- **Admin:** Open `/admin` on phone; list users/winners/transactions – fewer columns, no overflow; View/Top up from row actions; sidebar collapses.

### Optional

- 375px (iPhone SE) and 390px (e.g. iPhone 14) to confirm breakpoints.

---

## Staging mode – commands before deploy

Run from project root (or your deploy script):

```bash
# 1. Dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 2. Environment (ensure .env is production for staging/prod)
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://your-staging-url.com

# 3. Laravel
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
# Or single: php artisan optimize

# 4. Storage & migrations
php artisan storage:link   # if not already linked
php artisan migrate --force

# 5. Optional: seed admin user (once)
# php artisan db:seed --class=AdminUserSeeder --force

# 6. Permissions (Linux; adjust user/group)
# chown -R www-data:www-data storage bootstrap/cache
# chmod -R 775 storage bootstrap/cache
```

After deploy, smoke-check:

- `https://your-domain.com/up` → 200.
- `https://your-domain.com` → main app (login or dashboard).
- `https://your-domain.com/admin` → Filament login.
- Optional: `https://your-domain.com/db-check?secret=YOUR_HEALTH_CHECK_SECRET` → JSON with DB status.

**Smoke script:** `./scripts/smoke-test.sh https://your-domain.com` (checks /up, /admin/login; if run on server, runs `php artisan radarleb:smoke` for DB, storage, queue).  
**Nginx snippet:** `deploy/nginx-laravel.conf` (Laravel + Vite `build/` assets).

---

## Verification summary (“what I verified”)

| Area | What was verified / fixed |
|------|----------------------------|
| **Session isolation** | Middleware runs before StartSession; only cookie name and path are set. Admin cookie has path `/admin` so it’s only sent to /admin. Main and admin sessions can be active at once. CSRF token, login, remember-me, and redirects use the session chosen per request; no breaking change. |
| **Database** | Custom PostgresConnector binding removed so default Laravel pgsql is used. Optional connector remains in `App\Database\Connectors\PostgresConnector` for RDS `connect_timeout` if re-enabled in AppServiceProvider. `tests/Feature/DatabaseConnectionTest.php` asserts DB connection when not using in-memory sqlite. |
| **Filament mobile** | `visibleFrom('md'/'lg')` on table columns; AdminPanelProvider injects CSS via `PanelsRenderHook::STYLES_AFTER` so `.fi-ta-content` has overflow-x and form inputs are constrained at max-width 414px. Actions/filters/search remain Filament default (usable on small screens). |
| **AWS hardening** | `APP_TRUSTED_PROXIES` read in `bootstrap/app.php` and passed to `trustProxies(at: ...)`. `deploy/nginx-laravel.conf` added for Laravel + Vite. `php artisan radarleb:smoke` and `scripts/smoke-test.sh` check /up, /admin/login, DB, storage, queue. |

---

## Checklist summary

| Category | OK | Action |
|----------|----|--------|
| .env production | ✓ | Set APP_ENV, APP_DEBUG=false, APP_URL, DB_*, MAIL_* |
| Config/route/view cache | ✓ | Run optimize/cache after deploy |
| Storage link & permissions | ✓ | storage:link; chown/chmod storage & bootstrap/cache |
| Queues/cron | ✓ | None required unless you add queues (then use Supervisor) |
| Security (HTTPS, proxies, debug off) | ✓ | Trust proxies behind ELB/CloudFront; HEALTH_CHECK_SECRET optional |
| Performance (composer, npm, OPcache) | ✓ | composer --no-dev, npm run build, OPcache on |
| DB migrations | ✓ | migrate --force on deploy |
| Health | ✓ | /up; /db-check?secret= optional |
| Filament & session isolation | ✓ | Path-based cookie for /admin; assets built with npm run build |
| Mobile 360px (main app) | ✓ | CSS + GameModal updates applied |
| Mobile Filament tables | ✓ | visibleFrom('md'/'lg') on non-essential columns |

All listed changes are production-safe and avoid breaking existing behavior.
