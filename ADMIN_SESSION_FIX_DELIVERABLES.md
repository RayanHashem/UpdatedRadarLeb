# Admin 419 fix – deliverables

## Root cause (proven by design)

- **Same host, different ports:** Browsers send cookies by **domain** only; **port is ignored**. So `localhost:8000` and `localhost:8001` share the same cookie jar for `localhost`. If both use the same session cookie name (e.g. `laravel_session`), they overwrite each other: loading admin on 8001 then main on 8000 (or the reverse) can change which session the cookie points to, so the next request on 8001 may get a different session → **CSRF token no longer matches** → 419.
- **Fix:** Use **different session cookie names per port** so the two apps never share a session. Set this **before** `StartSession` runs (global prepend middleware).

---

## 1) Diagnostic logging (419 / TokenMismatch)

**File:** `bootstrap/app.php`

- Added `reportable()` for `TokenMismatchException`.
- On 419, the following is written to `storage/logs/laravel.log`:
  - **Request:** `url`, `method`, `cookie_header`, `x_xsrf_token`, `x_csrf_token`
  - **Session:** `session_id`, `session_keys` (all session keys)
  - **Config:** `config_app_url`, `config_session_domain`, `config_session_cookie`, `config_session_secure`, `config_session_same_site`, `config_session_path`

**How to use:** Trigger a 419, then run:

```bash
tail -100 storage/logs/laravel.log
```

Search for `419 TokenMismatchException – diagnostic` to see the exact request and config at failure time.

**Network check when clicking Sign in:**

- **Request:** POST to `http://localhost:8001/admin/login` (or Livewire update URL under `/admin`). Request must include:
  - Cookie header (e.g. `radarleb_admin_session=...`)
  - `X-XSRF-TOKEN` or `X-CSRF-TOKEN` (or `_token` in body)
- **Response:** If it’s 419, the log entry above explains why (e.g. wrong cookie name, wrong session, or redirect to 8000).

---

## 2) Session isolation by port (no port in cookie; different names)

**Approach A (implemented): separate session cookie name by port in middleware**

- **File:** `app/Http/Middleware/PortBasedSessionIsolation.php` (new)
- **Behaviour:**
  - **Port 8001** → `config(['session.cookie' => 'radarleb_admin_session'])`
  - **Port 8000** → `config(['session.cookie' => 'radarleb_main_session'])`
  - Other ports → leave default
  - Also sets `config('app.url')` and `URL::forceRootUrl()` from the request so links/redirects stay on the same port.
- **Registration:** In `bootstrap/app.php`, this middleware is **prepended** globally so it runs **before** any `StartSession` (web or panel). Cookie name and app URL are fixed before the session is started or used.

**Why this works:** Cookies are keyed by domain; port is not part of the key. So we keep one cookie name for 8000 and another for 8001. The browser then holds two cookies for `localhost` (e.g. `radarleb_main_session` and `radarleb_admin_session`). Each request sends the cookie that matches the app (by how we set the name on that port), so sessions no longer clash and CSRF tokens match.

**Verify in DevTools:**

1. Open **Application** (Chrome) → **Cookies** → `http://localhost:8001`
2. After visiting `/admin/login` you should see a cookie named **`radarleb_admin_session`** (not `laravel_session`).
3. Open **Application** → **Cookies** → `http://localhost:8000`
4. After using the main app you should see **`radarleb_main_session`** (not `laravel_session`).

---

## 3) CSRF kept on; session/CSRF stable

- **VerifyCsrfToken** is **re-enabled** in the Filament admin panel middleware (`app/Providers/Filament/AdminPanelProvider.php`).
- Session is isolated by port **before** session start, so:
  - GET login and POST (or Livewire) use the **same** session and **same** CSRF token on 8001.
  - No removal of CSRF; no random 419 from session/cookie clashes.

**Files changed:**

| File | Change |
|------|--------|
| `app/Http/Middleware/PortBasedSessionIsolation.php` | **New.** Sets `session.cookie` and `app.url` / `URL::forceRootUrl` by request port; runs first (global prepend). |
| `bootstrap/app.php` | Prepend `PortBasedSessionIsolation` (replacing path-based admin middleware). Add `reportable()` for `TokenMismatchException` with diagnostic log. |
| `app/Providers/Filament/AdminPanelProvider.php` | Re-add `VerifyCsrfToken::class` to panel middleware. |
| `app/Providers/AppServiceProvider.php` | Remove path-based `session.cookie` / `session.path` / `app.url` from `boot()`. |

---

## 4) Commands and verification

**After pulling changes:**

```bash
php artisan optimize:clear
```

**Run both apps:**

- Terminal 1: `php artisan serve --port=8000`
- Terminal 2: `php artisan serve --port=8001`

**Checklist:**

1. **Admin login (8001)**  
   - Open `http://localhost:8001/admin/login`, sign in with an admin account (see AdminUserSeeder).  
   - Repeat **10 times** (refresh, sign out/sign in). No 419, no “page expired” popup.

2. **No redirect to 8000**  
   - From admin (8001), click links and use the panel; URL must stay on `localhost:8001`.

3. **Main app (8000)**  
   - Use `http://localhost:8000`; main app works and does not require admin cookie.

4. **Cookies (DevTools)**  
   - On 8001: cookie `radarleb_admin_session` present.  
   - On 8000: cookie `radarleb_main_session` present.

5. **If 419 still appears**  
   - Reproduce once, then run `tail -100 storage/logs/laravel.log` and find the `419 TokenMismatchException – diagnostic` block.  
   - Check `config_session_cookie` (should be `radarleb_admin_session` for admin requests), `session_id`, and `cookie_header` to see why the token didn’t match.

---

## 5) DevTools – quick reference

**Cookies**

- **Application** → **Storage** → **Cookies** → `http://localhost:8001`  
  - Expect: `radarleb_admin_session`.
- Same for `http://localhost:8000`: `radarleb_main_session`.

**Network (Sign in)**

- **Network** tab → click Sign in.
- Request URL: `.../admin/login` or Livewire update under `/admin`.
- Request headers: `Cookie` (with `radarleb_admin_session`), `X-XSRF-TOKEN` or similar.
- Response: **200** (success) or **419** (then use log above to diagnose).

---

## Summary

- **Cause:** One session cookie name for both ports → sessions overwritten → CSRF token mismatch → 419.
- **Fix:** One middleware runs first and sets session cookie name (and app URL) by port so 8000 and 8001 never share a session. CSRF stays on; tokens match because the session is stable per port.
- **Proof:** Logs on 419 show request + config; cookie names in DevTools confirm isolation. Running login 10 times and using both ports without 419 confirms the fix.
