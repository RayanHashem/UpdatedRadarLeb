# Run main app and admin panel together

Run two Laravel servers so the main app and Filament admin don’t clash.

**Terminal 1 – main app**
```bash
php artisan serve --port=8000
```
→ **http://localhost:8000**

**Terminal 2 – admin panel**
```bash
php artisan serve --port=8001
```
Then open in the browser: **http://localhost:8001/admin** (or **http://localhost:8001/admin/login**).  
Do **not** open `http://localhost:8001` alone — that shows the main app; the admin is only at the `/admin` path.

Same codebase and database; only the port is different. Create admin users once with `php artisan db:seed --class=AdminUserSeeder` (prints temporary passwords). Log in at `/admin/login` with those credentials; change password via Profile in the user menu.

---

**Session / URL (no clashes)**  
- Do **not** set `SESSION_DOMAIN` in `.env` so cookies aren’t shared across ports.  
- Session cookie name is set by port (8000 → radarleb_main_session, 8001 → radarleb_admin_session); app URL forced to current port. See ADMIN_SESSION_FIX_DELIVERABLES.md. and “session”
**After changing .env or config**
```bash
php artisan optimize:clear
```

**If you see "Connection timed out" to the database (e.g. RDS)**  
- The app uses `CACHE_STORE=file` so the admin login page can load without the DB for cache.  
- If login or any admin page still fails with a DB timeout, your machine cannot reach the database (e.g. AWS RDS). Either: (1) use a VPN or network that can reach RDS and ensure the RDS security group allows your IP on port 5432, or (2) for local-only development, switch `.env` to a local database (e.g. `DB_CONNECTION=sqlite`, `DB_DATABASE` path to a `database/database.sqlite` file) and run `php artisan migrate`.

**If the admin login page is unclickable or frozen**  
Clear site data for `localhost:8001` (cookies + storage) or use an incognito window, then open `http://localhost:8001/admin/login` again. The panel uses light theme by default so scripts and styles load correctly.

---

**Password reset emails**  
For "Forgot password" to send emails to users, configure mail in `.env`:
- **Gmail**: Set `MAIL_USERNAME`, `MAIL_PASSWORD` (use an [App Password](https://myaccount.google.com/apppasswords)), `MAIL_FROM_ADDRESS`
- **Mailtrap** (testing): Sign up at mailtrap.io, use their SMTP credentials
- Without mail config, reset emails are written to `storage/logs/laravel.log` (you can copy the reset link from there for testing)
