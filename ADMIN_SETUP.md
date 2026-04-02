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

## 2. Admin users (role-based access)

Admin access is controlled by the `role` column on `users`. Allowed roles: `super_admin`, `technical_admin`, `admin`, `staff`. Only users with one of these roles can access `/admin`.

### Create the two admin users (run once)

```bash
php artisan db:seed --class=AdminUserSeeder
```

This creates (or updates) two users and **prints temporary passwords** for new accounts. Store them and change passwords after first login via **Profile** in the admin user menu (top right).

- **Ali Houdeib** — `Ali_houdeib@hotmail.com` — role: `super_admin`
- **Rayan Hashem** — `rayanehashem37@gmail.com` — role: `technical_admin`

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
- **`/admin/login`** → Filament login; use the admin account emails and temporary passwords from the seeder; change password via Profile.
- No changes to existing API or frontend (Vue SPA) logic.
- No changes to existing web routes; Filament registers its own routes under `/admin`.

---

## 5. Summary

| Item | Value |
|------|--------|
| **Admin URL (port 8001)** | http://localhost:8001/admin |
| **Login URL** | http://localhost:8001/admin/login |
| **Admin accounts** | Created by `php artisan db:seed --class=AdminUserSeeder`; see seeder output for temporary passwords. |
| **Change password** | Admin → user menu (top right) → Profile |
| **Main app (port 8000)** | http://localhost:8000 |
