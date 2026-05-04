# Deploy Guide

How RadarLeb runs in production. Local dev uses SQLite via
`scripts/dev-setup.sh` — that's a different doc. This file is for staging /
production on AWS.

> **Looking for the free deploy?** See **[`docs/deploy-render.md`](./deploy-render.md)** —
> Render free web service ($0/mo, sleeps after 15 min idle) + the existing
> AWS RDS Postgres. That's the recommended path for getting a friend's
> project live without spending money. This file is the AWS-native upgrade
> path for when free tier limits start hurting.

> Before you deploy: read `docs/security.md` first. There are credentials in
> the repo's git history that **must** be rotated before going further.

---

## Production stack

- Compute: AWS (the previous setup ran on Elastic Beanstalk; EC2 / ECS works too)
- Database: **PostgreSQL** on Amazon RDS (`radarleb-db.*.us-east-1.rds.amazonaws.com`)
- Storage: AWS S3 (file uploads, if any) — local fallback is `FILESYSTEM_DISK=local`
- Mail: AWS SES (optional) — local fallback is `MAIL_MAILER=log`
- Queue: AWS SQS (optional) — local fallback is `QUEUE_CONNECTION=database`
- Sessions: file-based by default (`SESSION_DRIVER=file`). Use `database` or
  `redis` if you scale to multiple containers.

The whole point of the local SQLite setup is to develop without any AWS account.
Production cuts over by changing `.env`.

## Required production .env

Copy from `.env.example` and set everything below. **Bold = must be changed
from .env.example defaults.**

```env
APP_NAME=RadarLeb
APP_ENV=production            # NEVER set this to 'local' in prod (DevAutoAuth check)
APP_KEY=base64:...            # php artisan key:generate (run once, then paste)
APP_DEBUG=false               # Hides stack traces from end users
APP_URL=https://your-domain.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TRUSTED_PROXIES=*         # Or specific load-balancer IPs

# DB — PostgreSQL on RDS
DB_CONNECTION=pgsql
DB_HOST=your-rds-endpoint.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=radarleb
DB_USERNAME=radarleb_admin
DB_PASSWORD=                  # ROTATE — see docs/security.md
DB_SSLMODE=require            # Force TLS to RDS

# Sessions / cache / queue
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=database

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=radarleb-public
AWS_USE_PATH_STYLE_ENDPOINT=false

# Mail
MAIL_MAILER=ses
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Health check (for /db-check)
HEALTH_CHECK_SECRET=          # generate a random 32+ char token

# Filament admin
ADMIN_MAX_EXECUTION_TIME=300

# Vite
VITE_APP_NAME="${APP_NAME}"

# DEV ONLY — leave blank in production
DEV_PASSWORD=
SEEDER_PASSWORD=
```

## RDS PostgreSQL setup

1. **Create the RDS instance** (PostgreSQL 16+). Choose a strong master
   password; don't reuse anything from the deleted credentials.
2. **Security group:** allow inbound 5432 only from your app's IPs / VPC.
   Never `0.0.0.0/0`.
3. **`Publicly accessible`:** keep this OFF if your app is in the same VPC.
   Turn it ON only if you're connecting from a developer laptop temporarily.
4. **Force SSL:** set `DB_SSLMODE=require` in `.env`. The Laravel pgsql config
   already reads `DB_SSLMODE`.
5. **Enable automated backups** (daily snapshot, 7+ day retention).
6. **Enable Performance Insights** (free for the first 7 days of data).

## Pre-deploy checklist

In order, before you push to production:

```bash
# 1. Rotate any credentials still in git history (see docs/security.md)

# 2. Lock the version
git status                          # clean working tree
git tag v1.0.0                      # whatever version makes sense
git push --tags

# 3. Build assets
npm ci                              # clean install (matches lockfile exactly)
npm run build                       # produces public/build/
# OR for SSR:
npm run build:ssr

# 4. Install PHP deps in production mode
composer install --no-dev --optimize-autoloader

# 5. Cache config / routes / views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Run migrations
php artisan migrate --force         # --force is required outside 'local'

# 7. Seed only the bare minimum (admin user, games)
php artisan db:seed --class=GameSeeder --force

# 8. Storage symlink
php artisan storage:link

# 9. Republish Filament assets
php artisan filament:upgrade

# 10. Smoke test (see scripts/smoke-test.sh)
bash scripts/smoke-test.sh https://your-domain.com $HEALTH_CHECK_SECRET
```

## Verifying production safety

After deploy, on the production host:

```bash
echo $APP_ENV               # MUST output: production
echo $APP_DEBUG             # MUST output: false

grep -r "DevAutoAuth" bootstrap/app.php
# MUST return nothing — the middleware should NOT be registered

php artisan tinker
> app()->environment()       # production
> config('app.debug')        # false
> DB::select('SELECT 1 as ok')   # [{ok: 1}]
> DB::select('SELECT current_database(), current_user')
```

If any of these don't match, fix `.env`, run `php artisan config:clear`, and
restart the app server.

## Common deploy issues

### `Class not found` after deploy
- Run `composer dump-autoload --optimize`.

### `Vite manifest not found`
- You forgot to run `npm run build`. The `public/build/manifest.json` must
  exist on the deployed instance.

### `Connection refused` to RDS
- Security group not allowing inbound from app's IP/VPC. Add an inbound rule.

### `password authentication failed for user "radarleb_admin"`
- `DB_PASSWORD` in `.env` doesn't match the RDS master password. Special
  characters need quoting: `DB_PASSWORD="dCsuC#!special"`.

### `SSL connection required`
- Set `DB_SSLMODE=require` and re-deploy.

### Filament admin shows "Vite manifest" or empty styles
- Run `php artisan filament:upgrade` on the deployed host. Filament publishes
  its own JS/CSS into `public/{js,css}/filament/` and those need refreshing
  after every `composer update`.

### `419 PAGE EXPIRED` on Inertia routes
- The `bootstrap/app.php` exception handler should catch this and re-issue
  a location visit. If it isn't, check that `HandleInertiaRequests` middleware
  is in the `web` group.

### App `500`s with no log line
- Make sure `APP_DEBUG=false`, then check `storage/logs/laravel.log` on the
  host. If the log file doesn't exist or isn't writable, fix file permissions:
  `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage`.

## Rollback plan

If a deploy goes bad and you need to roll back:

1. `git checkout <previous-tag>` on the host.
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate:rollback --step=N` — only if migrations were destructive.
   Most of our migrations are additive; rollback is safe but may leave columns
   with defaults that don't match the previous app code.
4. `php artisan config:cache && php artisan route:cache`
5. Restart the app server.

If the database itself is compromised, restore from the most recent automated
RDS snapshot. RDS keeps point-in-time recovery; pick a moment before the bad
deploy.

## Scheduling

The Laravel scheduler runs from `routes/console.php`. To make it actually fire
in production, the host must run cron every minute:

```cron
* * * * * cd /var/www/radarleb && php artisan schedule:run >> /dev/null 2>&1
```

Without this cron line, `routes/console.php` has no effect.
