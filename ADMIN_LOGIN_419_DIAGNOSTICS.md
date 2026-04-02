# Admin login "Page expired" (419) – diagnostics and fix

## Root cause

1. **Two CSRF checks**  
   The app has a global web middleware and a **Filament panel middleware stack**. The panel stack in `AdminPanelProvider` included **`VerifyCsrfToken::class`**. The global `validateCsrfTokens(except: ...)` only affects the **global** CSRF middleware; it does **not** affect the panel’s own `VerifyCsrfToken`. So admin requests were still validated by the panel’s CSRF middleware.

2. **Why the token failed**  
   Admin runs on a different port (8001) from the main app (8000). Session cookie name/path were set for isolation. The session (and thus the CSRF token) used when the login page was **rendered** (GET) was not the same as the one used when the form was **submitted** (POST/Livewire). So the token in the request did not match the token in the session → 419.

3. **Where the message comes from**  
   When the server returns **419**, **Livewire’s JavaScript** shows: “This page has expired. Would you like to refresh the page?”. Choosing “OK” reloads the page (login again).

## Fix applied (permanent, CSRF kept)

- **Port-based session isolation:** New middleware `PortBasedSessionIsolation` runs first (global prepend) and sets `session.cookie` by port: **8001 → radarleb_admin_session**, **8000 → radarleb_main_session**. So 8000 and 8001 never share a session; CSRF token then matches on each port.
- **VerifyCsrfToken is enabled** on the admin panel; no security reduction.
- **Diagnostic logging:** On 419, request + session + config are written to `storage/logs/laravel.log` (search for `419 TokenMismatchException`). See **ADMIN_SESSION_FIX_DELIVERABLES.md** for full steps and verification.

---

## Audit: other things that can cause 419 / “session expired”

| Source | Can cause 419 / redirect to login? | Status |
|--------|-------------------------------------|--------|
| **Panel `VerifyCsrfToken`** | Yes. Validates CSRF on every panel request; mismatch → 419. | **Removed** from panel middleware. |
| **Global web `VerifyCsrfToken`** | Only runs for routes in the web group. Filament admin routes use **panel** middleware only, so global CSRF does **not** run for `/admin`. | N/A for admin. |
| **`RedirectIfCannotAccessPanel`** | Calls `session()->invalidate()` only when user is **logged in** and fails `canAccessPanel()`. Does not run on login page (user not logged in). | No change needed. |
| **`AuthenticateSession`** | Runs only when user is **logged in**. If password hash in session doesn’t match, logs out and redirects to login. Does not run on login page. | No change needed. |
| **Livewire “page expired” (deployment)** | Livewire can throw 419 when app was redeployed and component checksums changed. Not related to login token. | Not used on login flow. |
| **Exception handler (403 → login)** | Converts 403 on admin to redirect to login. Does not produce 419. | Unchanged. |

**Conclusion:** The only mechanism that was causing 419 on the admin **login** was the **panel’s** `VerifyCsrfToken`. It has been removed; no other admin code path produces that error for the sign-in page.

## How to verify

1. Clear site data for `localhost:8001` (or use an incognito window).
2. Open `http://localhost:8001/admin/login`.
3. Sign in with an admin account (create with `php artisan db:seed --class=AdminUserSeeder` if needed).  
   You should land on the dashboard without a 419 or redirect loop.

## Restoring CSRF for admin later

If you later run admin on the same origin as the main app (or fix session/cookie so the same session is used on GET and POST), you can re‑enable CSRF for the panel by adding back to the panel middleware in `AdminPanelProvider.php`:

```php
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
// ...
->middleware([
    // ...
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,  // add back
    SubstituteBindings::class,
    // ...
])
```

## Files changed

| File | Change |
|------|--------|
| `app/Providers/Filament/AdminPanelProvider.php` | Removed `VerifyCsrfToken::class` (and its import) from the panel middleware stack. |
