# Filament Admin Panel – Setup & Two-Server Run

## 1. Fix 404 (provider registration)

Filament panel routes are registered via `App\Providers\Filament\AdminPanelProvider::class` in **`bootstrap/providers.php`**.

After any change to providers, run:

```bash
php artisan optimize:clear
```

Verify admin routes:

```bash
php artisan route:list | findstr admin
php artisan route:list | findstr filament
```

You should see routes such as `/admin`, `/admin/login`, etc.

---

## 2. Test admin user

- **Email:** `admin@admin.com`
- **Password:** `admin123`
- **Name:** Test Admin

This user passes `canAccessPanel()` (email ends with `@admin.com`).

### Create/update the admin user

**Option A – Seeder (recommended, reproducible):**

```bash
php artisan db:seed --class=AdminUserSeeder
```

Or seed everything (including admin):

```bash
php artisan db:seed
```

**Option B – Tinker (one-off):**

```bash
php artisan tinker
```

Then in tinker:

```php
$u = \App\Models\User::updateOrCreate(
    ['email' => 'admin@admin.com'],
    [
        'name' => 'Test Admin',
        'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
        'phone_number' => null,
        'game_id' => 1,
    ]
);
$u->exists; // true
exit
```

---

## 3. Run admin on port 8001, main app on 8000

No route or app logic changes. Run two Artisan servers:

**Terminal 1 – main app (port 8000):**

```bash
php artisan serve --port=8000
```

**Terminal 2 – same app, admin on port 8001:**

```bash
php artisan serve --port=8001
```

- Main app: **http://localhost:8000**
- Admin panel: **http://localhost:8001/admin**

Both use the same codebase and DB; only the port differs.

---

## 4. Sanity checks

- **`/admin`** → redirects to **`/admin/login`** when not authenticated (Filament auth middleware).
- **`/admin/login`** → shows Filament login; log in with `admin@admin.com` / `admin123`.
- No changes to existing API or frontend (Vue SPA) logic.
- No changes to existing web routes; Filament registers its own routes under `/admin`.

---

## 5. Summary

| Item | Value |
|------|--------|
| **Admin URL (port 8001)** | http://localhost:8001/admin |
| **Login URL** | http://localhost:8001/admin/login |
| **Email** | admin@admin.com |
| **Password** | admin123 |
| **Main app (port 8000)** | http://localhost:8000 |
