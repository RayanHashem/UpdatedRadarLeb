# Security Notes

Specific to RadarLeb. Read this before changing anything in `app/Http/Middleware/`,
`config/auth.php`, or anywhere passwords or wallets are touched.

---

## 🚨 ROTATE PRODUCTION CREDENTIALS — TOP PRIORITY

The `origin/updatedradarleb` branch (now in `cleanup/abed-radarleb`'s history)
contains a deleted file `AWS_RDS_CONNECTION_GUIDE.md` that exposed:

- Production RDS hostname: `radarleb-db.col8o06a4whf.us-east-1.rds.amazonaws.com`
- Production DB username: `radarleb_admin`
- A value that was claimed to be the production password

**Anyone who cloned or forked the repo before today has this in their git history.**
Deleting the file in our cleanup commit does NOT remove it from history.

**Action items, in priority order:**
1. Rotate the RDS master password immediately in AWS Console → RDS → Modify.
2. Restrict the RDS Security Group to a known IP allowlist (or use a bastion
   host / VPN). If it's currently set to `0.0.0.0/0` it must be locked down.
3. Audit RDS CloudTrail for the time the credential was committed → today.
   Look for unusual connections from unknown IPs.
4. Once the password is rotated, decide whether to rewrite git history with
   `git filter-repo` to remove the file from all branches/tags. If the repo
   was ever public, do this. If it's been private the whole time, the rotation
   alone is probably enough.
5. Update production `.env` with the new password. Do not commit the new value
   anywhere.

---

## Auth model

Two guards, completely independent — see `docs/architecture.md` for the full
picture. Critical rules:

1. **Public users authenticate via `web` guard with phone + password.** Never
   merge with the admin guard.
2. **Admin users authenticate via `admin` guard with email + password.**
   Filament handles this; don't override.
3. **`PortBasedSessionIsolation` middleware** is what keeps the two session
   cookies from clobbering each other. Don't disable it. (Class name is
   misleading — the impl is path-based, not port-based.)

## DevAutoAuth — three layers of defense

`app/Http/Middleware/DevAutoAuth.php` exists to auto-login a dev user when
`DEV_PASSWORD` is set in `.env`. It is **dangerous** if it ever runs in
production. Three layers protect against that:

1. **Environment check:** the first thing the middleware does is
   `if (! app()->environment('local')) return $next($request);`. So even if
   it's registered, in production it is a no-op.
2. **Host check:** explicitly refuses to run on `.com`, `.net`, `.org`, `.io`,
   `amazonaws.com`, `elasticbeanstalk.com`, `cloudfront.net`. This catches the
   case where `APP_ENV=local` is misconfigured in production.
3. **Not registered:** the middleware is **not** in `bootstrap/app.php`. Even
   with `APP_ENV=local` and a non-prod hostname, it won't run unless someone
   adds it to the middleware stack.

If you change anything about `DevAutoAuth`, all three layers must remain.

## Mass assignment hygiene

Every Eloquent model on user-facing forms must declare `$fillable` (whitelist)
or `$guarded = ['id']` (blacklist). **Never `$guarded = []`**, which lets users
set any column they want via `$user->fill($request->all())`. Models with money
columns (`User`, `WalletTransaction`, `Game`) are especially sensitive.

When accepting input in a controller, prefer:

```php
// Good — Form Request validates and only attributes the validated keys
public function update(UserUpdateRequest $request, User $user)
{
    $user->update($request->validated());
}

// Bad — accepts any field the request body sends
public function update(Request $request, User $user)
{
    $user->update($request->all());
}
```

## Passwords

- All password storage uses `Hash::make()` (bcrypt by default in Laravel 12).
- All password verification uses `Hash::check($plain, $hashed)`.
- **Never store plaintext.** Never log password fields. Never include them in
  Inertia shared props.
- The `User` model's `$hidden` array hides `password` and `remember_token`
  from JSON serialization. Don't remove those.
- Seeder default password is sourced from `env('SEEDER_PASSWORD', 'password')`
  — change it for any non-dev environment.

## CSRF & sessions

- Inertia includes the CSRF token automatically. Never disable CSRF on a state-
  changing route.
- 419 errors on Inertia routes are handled in `bootstrap/app.php` (full
  location visit). Don't `try/catch` them in components.
- Filament's CSRF flow is its own; don't intercept Filament responses.
- The `/db-check` health endpoint accepts a query token from
  `env('HEALTH_CHECK_SECRET')`. **Add this to `.env.example`.** It's currently
  missing.

## Wallet & scan endpoints

- `POST /scan/{game}` (in `routes/auth.php`) is auth-gated through the `web`
  guard. Don't relax that.
- `WalletTransaction::boot()` validates `amount === scans.cost` (1¢ tolerance).
  Don't bypass this hook by calling `DB::insert` directly.
- The unique partial index on `wallet_transactions.scan_id` prevents duplicate
  charges. Don't drop it without a replacement.
- Top-up actions only run inside Filament admin pages — never expose a public
  "add money to my wallet" endpoint until there's a real payment integration.

## Public exposure

- `GET /winners` (in `routes/web.php`) returns a list of recent winners with
  `game_name` and `user_name`. It's not auth-gated and has no rate limit.
  Add `throttle:30,1` if abuse becomes a concern.
- The hardcoded support phone number `71484833` appears in three places:
  `resources/js/pages/Dashboard.vue`, `resources/js/pages/radarr.vue`,
  `resources/js/pages/dasho.vue`. That's intentional public information, not a
  leak — but if it changes, change all three. Better: extract to a config file.

## XSS & blade safety

- Public pages render through Inertia/Vue. Vue auto-escapes `{{ value }}`.
- Filament templates auto-escape via Livewire's HTML escape pipeline.
- The few raw blade templates in `resources/views/` use `{{ }}` — don't
  switch any of them to `{!! !!}` without a clear reason and a sanitizer.

## What to do if you find a security issue

1. Don't open a public GitHub issue. Talk to the project owner directly.
2. Fix on a private branch. Don't push the fix to a public branch with a
   commit message that names the vulnerability.
3. After the fix is deployed, decide whether the issue affected real data
   in production. If yes, that's a disclosure conversation, not just a code
   conversation.

## Future work

- [ ] Add `php artisan vendor:publish --tag=sanctum` and use Sanctum for the
      `/api/*` routes when those grow beyond the current minimal set.
- [ ] Add request rate limiting to `POST /scan/{game}` (anti-abuse).
- [ ] 2FA for admin users.
- [ ] `Audit` log for any wallet/admin action — currently there's no audit
      trail beyond `wallet_transactions.created_at`.
