# AWS RDS PostgreSQL Connection Guide

## ✅ Completed Steps

### 1. PHP PostgreSQL Extensions Verified
- ✅ `pdo_pgsql` - **ENABLED**
- ✅ `pgsql` - **ENABLED**
- PHP Configuration File: `C:\php\php.ini`

**To verify yourself:**
```powershell
php -m | Select-String -Pattern "pdo_pgsql|pgsql"
```

**If extensions are missing**, edit `C:\php\php.ini` and uncomment:
```ini
extension=pdo_pgsql
extension=pgsql
```

### 2. .env File Updated
Your `.env` file has been updated with PostgreSQL settings:

```env
DB_CONNECTION=pgsql
DB_HOST=radarleb-db.col8o06a4whf.us-east-1.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=radarleb-db
DB_USERNAME=radarleb_admin
DB_PASSWORD=dCsuCX7A4wTyfnx
```

### 3. Laravel Configuration Verified
The `database.php` config file already supports PostgreSQL and uses standard env variables:
- ✅ `DB_CONNECTION` → driver selection
- ✅ `DB_HOST` → database host
- ✅ `DB_PORT` → database port (default: 5432)
- ✅ `DB_DATABASE` → database name
- ✅ `DB_USERNAME` → database user
- ✅ `DB_PASSWORD` → database password

---

## 🔍 Connection Verification Steps

### Step 1: Clear Laravel Cache
```powershell
php artisan optimize:clear
```

### Step 2: Test Connection via Tinker
```powershell
php artisan tinker
```

Then in tinker:
```php
DB::select('SELECT 1 as ok');
// Should return: [{"ok":1}]

// Or test with version:
DB::select('SELECT version()');
```

**Exit tinker:** Type `exit` or press `Ctrl+C`

### Step 3: Test Connection via Script
```powershell
php test_db_connection.php
```

This script will:
- Display connection details
- Test the connection
- Show PostgreSQL version if successful
- Provide troubleshooting steps if it fails

---

## 🐛 Troubleshooting Common Issues

### Issue 1: "Connection refused" or "Connection timeout"

**Cause:** RDS Security Group not allowing inbound connections

**Fix:**
1. Go to AWS Console → RDS → Your Database → Connectivity & Security
2. Click on the Security Group
3. Edit Inbound Rules
4. Add rule:
   - Type: PostgreSQL
   - Port: 5432
   - Source: Your IP address (or `0.0.0.0/0` for testing only - **NOT recommended for production**)
5. Save rules

**To find your IP:**
```powershell
# PowerShell
(Invoke-WebRequest -Uri "https://api.ipify.org").Content
```

### Issue 2: "Publicly accessible: No"

**Cause:** RDS instance doesn't have public access enabled

**Fix:**
1. Go to AWS Console → RDS → Your Database
2. Click "Modify"
3. Under "Connectivity", expand "Additional configuration"
4. Set "Public access" to **Yes**
5. Apply changes immediately (or during next maintenance window)
6. Wait for modification to complete (5-10 minutes)

### Issue 3: "Wrong endpoint" or "Host not found"

**Cause:** Incorrect DB_HOST in .env

**Fix:**
1. Go to AWS Console → RDS → Your Database
2. Under "Connectivity & security", copy the **Endpoint**
3. Update `.env`:
   ```env
   DB_HOST=your-actual-endpoint.rds.amazonaws.com
   ```
4. Run: `php artisan optimize:clear`

### Issue 4: "Authentication failed" or "Password authentication failed"

**Cause:** Wrong username or password

**Fix:**
1. Verify credentials in AWS Console → RDS → Your Database → Configuration
2. Check `.env` file matches exactly (no extra spaces)
3. If password has special characters, ensure it's properly quoted in .env:
   ```env
   DB_PASSWORD="dCsuCX7A4wTyfnx"
   ```

### Issue 5: "PDOException: could not find driver" or "pdo_pgsql not found"

**Cause:** PHP PostgreSQL extension not enabled

**Fix:**
1. Check if extension is loaded:
   ```powershell
   php -m | Select-String -Pattern "pdo_pgsql"
   ```

2. If not found, edit `C:\php\php.ini`:
   ```ini
   ; Find and uncomment these lines:
   extension=pdo_pgsql
   extension=pgsql
   ```

3. Restart your web server/PHP-FPM if applicable

4. Verify again:
   ```powershell
   php -m | Select-String -Pattern "pdo_pgsql"
   ```

### Issue 6: "SSL connection required"

**Cause:** RDS requires SSL but Laravel config doesn't enforce it

**Fix:**
The `database.php` config already has `'sslmode' => 'prefer'` which should work. If you need to force SSL, you can add to `.env`:
```env
DB_SSLMODE=require
```

Then update `database.php` to use:
```php
'sslmode' => env('DB_SSLMODE', 'prefer'),
```

---

## 📋 Quick Verification Checklist

Run these commands in order:

```powershell
# 1. Verify PHP extensions
php -m | Select-String -Pattern "pdo_pgsql|pgsql"
# Expected: pdo_pgsql and pgsql

# 2. Verify .env settings
Get-Content .env | Select-String -Pattern "DB_"
# Expected: All DB_ variables present

# 3. Clear cache
php artisan optimize:clear

# 4. Test connection
php test_db_connection.php
# Expected: "✅ Connection successful!"

# 5. Test in tinker
php artisan tinker
# Then: DB::select('SELECT 1 as ok');
# Expected: [{"ok":1}]
```

---

## 🔒 Security Notes

1. **Never commit `.env` file** - It contains sensitive credentials
2. **Use IP whitelisting** - Only allow your IP in RDS Security Group, not `0.0.0.0/0`
3. **Use strong passwords** - Your current password looks good
4. **Enable SSL in production** - Set `DB_SSLMODE=require` for production

---

## 📝 Next Steps (When Ready)

Once connection is verified:

1. **Test connection is stable:**
   ```powershell
   php artisan tinker
   # Run: DB::select('SELECT current_database(), current_user');
   ```

2. **When ready to migrate** (you said not yet):
   ```powershell
   php artisan migrate
   ```

3. **Verify tables created:**
   ```powershell
   php artisan tinker
   # Run: DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
   ```

---

## 🆘 Still Having Issues?

1. **Check RDS Status:**
   - AWS Console → RDS → Your Database
   - Status should be "Available"
   - Check "Logs & events" for errors

2. **Test from command line** (if psql is installed):
   ```powershell
   psql -h radarleb-db.col8o06a4whf.us-east-1.rds.amazonaws.com -p 5432 -U radarleb_admin -d radarleb-db
   ```

3. **Check Windows Firewall:**
   - Ensure Windows Firewall isn't blocking outbound port 5432

4. **Verify Network Connectivity:**
   ```powershell
   Test-NetConnection -ComputerName radarleb-db.col8o06a4whf.us-east-1.rds.amazonaws.com -Port 5432
   ```

---

## 📞 Connection Details Summary

- **Host:** radarleb-db.col8o06a4whf.us-east-1.rds.amazonaws.com
- **Port:** 5432
- **Database:** radarleb-db
- **Username:** radarleb_admin
- **Password:** dCsuCX7A4wTyfnx
- **Connection Type:** PostgreSQL (pgsql)
- **PHP Extension:** ✅ pdo_pgsql enabled
- **Laravel Config:** ✅ Ready

