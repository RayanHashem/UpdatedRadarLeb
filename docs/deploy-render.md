# Deploy RadarLeb on Render (free) + AWS RDS

This is the production-deploy guide for the **cheapest path that doesn't change
the database**. Everything below adds up to **just the AWS RDS cost** (~$13/mo
on db.t3.micro, $0 if still in AWS Free Tier) — Render itself is free.

> **If you're migrating from an existing EC2 setup, skip to the "Migrating
> from EC2" section below first** — it covers the pre-migration audit and
> the order of operations to avoid downtime.

---

## Why this path

| What | Where | Cost |
|---|---|---|
| Application (Laravel + Filament) | Render free web service | $0 (sleeps after 15 min idle) |
| Database | Existing AWS RDS Postgres | $13/mo on-demand, less with Reserved |
| Static assets | Served by the app's nginx | $0 |
| Logs | Render's built-in log UI | $0 |

**Trade-off:** Render free tier sleeps after 15 minutes of no traffic. The
first request after sleep waits ~30s while the container wakes. Acceptable
for staging or low-traffic production. If users complain, upgrade to
Render's `starter` plan ($7/mo) — same architecture, no sleep, plus you
get static outbound IPs you can allowlist on the RDS security group.

---

## Migrating from EC2 (you have a current setup paying ~$30/mo)

Read this section if you're moving off an existing EC2 instance like
`radarleb-staging`. Goal: bring up Render, verify it works, then shut down
the EC2 + clean up the orphan resources that silently cost money.

### Step 0: audit the current AWS bill (2 minutes, free)

Before changing anything, see what's actually charging:

1. AWS Console → **Billing & Cost Management** → **Cost Explorer** (free).
2. Group by: **Service**, last 30 days. You'll see a breakdown like:
   - EC2 (instance hours): ~$7-10
   - RDS (instance + storage): ~$13-15
   - EBS (volumes + snapshots): ~$2-5  ← look for surprises here
   - Data Transfer: $1-3
   - Backup / S3 / etc: variable

3. Common silent costs to clean up immediately:
   - **Orphan EBS volumes** (unattached, but still billed). Console → EC2 → Volumes → filter "State = available" (i.e. not attached). Delete any you don't need.
   - **Old EBS snapshots** of terminated instances. Console → EC2 → Snapshots. Delete anything that's not your current backup window.
   - **Elastic IPs not associated with a running instance**. Console → EC2 → Elastic IPs. They cost ~$3.60/mo if not attached. Release any unused ones.
   - **NAT Gateway** in a VPC. ~$30/mo by itself if active. Console → VPC → NAT Gateways. If your app doesn't need outbound internet from a private subnet, delete it.

4. Note the per-service breakdown — this is your "savings target" once
   you migrate.

### Step 1: deploy to Render (do not touch EC2 yet)

Follow the "One-time setup" section below to bring up the Render service
in parallel with your existing EC2. Both run; users still hit EC2.

### Step 2: verify Render works end-to-end

- Hit `https://your-app.onrender.com/` — public app loads
- Hit `https://your-app.onrender.com/admin/login` — Filament admin loads
- Sign in with the seeded admin
- Top up a test user, then sign in as that user and run a scan
- Confirm `wallet_transactions` and `scans` rows appear in RDS

### Step 3: cut DNS over (if you have a custom domain)

Render → your service → Settings → **Custom Domains** → add your domain.
Render gives you the DNS records (CNAME usually). Update DNS at your
registrar. Wait for propagation (5–60 min).

If you don't have a custom domain, just give your friend / users the new
`*.onrender.com` URL.

### Step 4: shut down the EC2 instance

Once Render has been serving real traffic for 24–48h with no issues:

1. AWS Console → EC2 → Instances → select `radarleb-staging` →
   **Instance state** → **Stop**. (Stop, don't terminate, for the first
   week — it's reversible. Stopped instances don't bill compute, only EBS.)
2. After a week of confidence, **Terminate** the instance. Its EBS volumes
   get released too. Console → EC2 → Volumes → confirm they're gone.
3. Release any **Elastic IP** that was attached. Console → EC2 →
   Elastic IPs → Actions → Release.
4. If there's a security group dedicated to the EC2 (e.g. `radarleb-web`)
   and nothing else uses it, delete it.

### Step 5: confirm the bill drops

Wait until the next billing cycle (~1st of next month). Cost Explorer →
last 7 days should show the EC2 + EBS lines drop to near zero. Expected
new total: **just RDS** (~$13/mo) plus a few cents of misc charges.

### Optional next steps

- **Reserved Instance for RDS** (1-year, no upfront): drops `db.t3.micro`
  from $13/mo to ~$8.50/mo. Cost Explorer's "Reservation" tab tells you
  exactly how much you'd save. No code changes.
- **Move RDS to free tier db.t4g.micro** (ARM): if she's still in the
  12-month AWS Free Tier, this is $0 for 750 hours/month.
- **If RDS load stays at <5% CPU forever** (which it will for a small
  app): consider migrating to Supabase free (500MB limit) and dropping
  RDS too. Total monthly cost: $0.

---

## What you're standing up

```
                              ┌────────────────────────┐
GitHub ──── push ────────────▶│  Render (free web)     │
cleanup/abed-radarleb         │  Docker container      │
                              │  PHP 8.4-fpm + nginx   │
                              │  $0/mo, sleeps 15min   │
                              └─────────┬──────────────┘
                                        │
                              SSL pgsql │
                                        ▼
                              ┌────────────────────────┐
                              │  AWS RDS Postgres      │
                              │  (existing instance)   │
                              └────────────────────────┘
```

---

## One-time setup (15 minutes)

### 1. Rotate AWS RDS credentials

Read `docs/security.md` first. The previous developer committed RDS
credentials into git history. **Rotate the master password before doing
anything else.** AWS Console → RDS → Modify → set a new password.

### 2. RDS security group: allow Render to connect

Render's free tier doesn't expose static outbound IPs, so RDS has to either
(a) accept connections from anywhere (relying on strong password + SSL) or
(b) you upgrade Render to the `starter` plan ($7/mo) which gives you static
egress IPs you can allowlist precisely.

**Free path (less secure but works):**

1. AWS Console → RDS → your DB → Connectivity & Security → click the security group.
2. Inbound rules → Edit → Add rule:
   - Type: PostgreSQL
   - Port: 5432
   - Source: `0.0.0.0/0`
   - Description: "Render free tier (no static IP). Protected by password + SSL."
3. Save.
4. Make sure "Publicly accessible" on the RDS instance is set to "Yes" (otherwise the inbound rule is moot).

**Better path (paid):** upgrade `render.yaml`'s `plan:` line from `free` to
`starter`, get the static egress IPs from Render's dashboard
(Service → Connect tab), and replace `0.0.0.0/0` with those specific IPs.

### 3. Verify the RDS connection works

From your laptop (with the new password) — confirm you can connect at all:

```bash
psql "postgresql://radarleb_admin:NEW_PASSWORD@your-rds-endpoint.us-east-1.rds.amazonaws.com:5432/radarleb?sslmode=require"
```

You should land in a `radarleb=>` prompt. If not, the security group, password,
or SSL setting is wrong — fix that first or Render will fail the same way.

### 4. Generate a fresh `APP_KEY`

```bash
php artisan key:generate --show
```

Copy the printed `base64:...` value. You'll paste it into Render's dashboard
in the next step. **Do not commit it.**

---

## Connect Render to GitHub

1. Go to https://dashboard.render.com → New + → **Blueprint**.
2. Connect your GitHub account if you haven't already.
3. Pick the `RadarLeb` repo.
4. Render reads `render.yaml` and shows you the planned services. Click "Apply".
5. The first deploy starts automatically. It will fail on first run until you
   fill in the env vars in the next step.

---

## Fill in the dashboard env vars

`render.yaml` declares which env vars exist; the ones marked `sync: false` you
have to fill in via the Render dashboard:

1. Render dashboard → your service → **Environment** tab.
2. Set these (everything else is filled by the blueprint):

| Variable | Value |
|---|---|
| `APP_KEY` | The `base64:...` from step 4 above |
| `APP_URL` | Leave blank for the first deploy. After Render finishes, copy the `https://radarleb.onrender.com` URL it gives you and set it here, then "Manual Deploy" to apply. |
| `DB_HOST` | Your RDS endpoint, e.g. `radarleb-db.col8o06a4whf.us-east-1.rds.amazonaws.com` |
| `DB_DATABASE` | The DB name (likely `radarleb`) |
| `DB_USERNAME` | `radarleb_admin` |
| `DB_PASSWORD` | The **rotated** password from step 1 |

`HEALTH_CHECK_SECRET` is auto-generated by Render the first time. Copy it
from the Environment tab if you want to ping `/db-check?secret=...` later.

3. Save → Render automatically redeploys.

---

## What the deploy does

Each push to the configured branch triggers Render to:

1. Pull the repo.
2. Build the Docker image (`Dockerfile`):
   - Stage 1: `npm ci && npm run build` (Vite production bundle).
   - Stage 2: `composer install --no-dev --optimize-autoloader`.
   - Stage 3: drops both into a `serversideup/php:8.4-fpm-nginx` image.
3. Start the container.
4. Container entrypoint:
   - `php artisan migrate --force` (apply any new schema).
   - `php artisan config:cache && route:cache && view:cache`.
   - Hand off to s6 (php-fpm + nginx).
5. Render runs a health check (`GET /up`). On 200, traffic routes in.

The first build is slow (~3–5 min). Subsequent builds use the layer cache
(~1–2 min).

---

## Seeding production data

After the first successful deploy, seed the games table and create your real
admin user. From your laptop, **run a one-shot job in Render's dashboard**:

1. Render dashboard → your service → **Shell** tab.
2. Run:
   ```bash
   php artisan db:seed --class=GameSeeder --force
   php artisan db:seed --class=AdminUserSeeder --force
   ```
3. The admin seeder prints temporary passwords to the console — **copy them
   before closing the shell**. They're not shown again.
4. Each admin user logs in at `https://your-app.onrender.com/admin/login` and
   changes their password via the Profile page.

**Do NOT run `LocalAdminSeeder` in production.** It refuses to run outside
`APP_ENV=local`, but don't try.

---

## Trade-offs of the free tier (read this once)

- **Cold start.** After 15 minutes of no traffic, Render spins the container
  down. The next request waits ~30 seconds while the container boots. For
  RadarLeb that means: the first user to scan after a quiet period sees a
  ~30s spinner. If active users notice this, upgrade to `starter` ($7/mo).
- **No background workers.** `QUEUE_CONNECTION=sync` runs jobs inside the
  request — fine for the small handful of operations RadarLeb has now, but
  if you add heavy jobs (e.g. winners notifications via email/SMS), they'll
  block the user request. To run a worker dyno you need a separate Render
  service (paid).
- **No persistent storage.** Anything written to `storage/app/local/`,
  `storage/logs/`, etc. is wiped between deploys/restarts. Use S3 (or
  R2 / B2 / DigitalOcean Spaces — all S3-compatible) when you start letting
  users upload anything.
- **750 free hours/month.** A single service running 24/7 uses ~720 hours,
  fits comfortably. Multiple services exceed it.

---

## Common deploy issues

### "Container failed health check"
Look at Render → Logs. Almost always: `migrate --force` failed. Most likely
RDS isn't reachable (security group / wrong password) or migrations are
running fine but the app is stuck on a different error. Run the shell:
```
php artisan migrate:status
```

### `Class "config" does not exist` after deploy
You ran `composer dump-autoload` but forgot `--optimize`. The Dockerfile
already does this — if you're seeing it locally, it's a stale cache.
`php artisan optimize:clear` fixes it.

### Pinging `/admin/login` shows "419 Page Expired"
Session cookie path mismatch between web and admin. The
`PortBasedSessionIsolation` middleware (which is path-based, despite the name)
handles this — make sure it's still in `bootstrap/app.php`.

### Filament admin times out
Render free's per-request timeout is 60s. Filament's Livewire round-trips
can occasionally exceed this on cold start + slow query. Either:
- Optimize the slow Filament query (add a cached widget, eager-load).
- Upgrade to Render `starter` (no cold start = no edge timeouts).

### "Too many connections" on RDS
Each restart opens fresh DB connections. Free RDS instances cap around
~50–100 connections. With 1 Render service + database sessions/cache/queue,
this is plenty. If you see this error, AWS Console → RDS → Modify →
parameter group → set `max_connections` higher.

---

## Rollback plan

If a deploy goes bad: Render dashboard → your service → **Deploys** tab →
find the last green deploy → "Rollback". Render restores that container
image and traffic switches over within ~30 seconds.

For DB rollbacks: AWS Console → RDS → your DB → "Restore to point in time"
and pick a moment before the bad deploy. RDS keeps automated backups for
7 days by default.
