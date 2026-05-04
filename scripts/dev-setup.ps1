# RadarLeb — Local dev one-shot setup (Windows PowerShell)
# Mirrors scripts/dev-setup.sh but runs on native Windows (Laragon, Herd, or
# manually-installed PHP+Composer+Node). Idempotent; safe to re-run.
#
# Usage:
#   1. Open PowerShell IN the project root (cd C:\path\to\RadarLeb)
#   2. If you've never run a script before, allow it for this session:
#        Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
#   3. .\scripts\dev-setup.ps1
#
# Prerequisites (install once, in this order):
#   - PHP 8.4 (NOT 8.5 — three deps cap at 8.4):
#        https://windows.php.net/download/  (pick "Thread Safe x64" zip)
#        Or use Laragon (https://laragon.org) which bundles PHP+Composer+nginx.
#        Or use Herd Windows (https://herd.laravel.com) — free, easiest.
#   - Composer: https://getcomposer.org/Composer-Setup.exe
#   - Node 20+: https://nodejs.org/ (LTS installer)
#   - Git for Windows: https://git-scm.com/download/win
#
# If you'd rather use WSL2, follow the bash-script path instead — see
# docs/HANDOFF.md for both routes.

$ErrorActionPreference = 'Stop'

function Say($msg)  { Write-Host "==> $msg" -ForegroundColor Blue }
function Ok($msg)   { Write-Host "[OK]  $msg" -ForegroundColor Green }
function Warn($msg) { Write-Host "[!]   $msg" -ForegroundColor Yellow }
function Die($msg)  { Write-Host "[X]   $msg" -ForegroundColor Red; exit 1 }

# Move to project root regardless of where the user invoked this from.
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
Set-Location $ProjectRoot
Say "Project root: $ProjectRoot"

# -----------------------------------------------------------------------------
# 1. Tool check
# -----------------------------------------------------------------------------
Say "Checking required tools"

function Have($cmd) { $null -ne (Get-Command $cmd -ErrorAction SilentlyContinue) }

if (-not (Have php))      { Die "PHP not on PATH. Install PHP 8.4 or use Laragon/Herd." }
if (-not (Have composer)) { Die "Composer not on PATH. Get it from https://getcomposer.org/Composer-Setup.exe" }
if (-not (Have node))     { Die "Node not on PATH. Install Node 20+ LTS from https://nodejs.org" }
if (-not (Have npm))      { Die "npm not on PATH (comes with Node)." }

$PhpVer = (php -r 'echo PHP_VERSION;')
$PhpMaj = [int](php -r 'echo PHP_MAJOR_VERSION;')
$PhpMin = [int](php -r 'echo PHP_MINOR_VERSION;')
if ($PhpMaj -lt 8 -or ($PhpMaj -eq 8 -and $PhpMin -lt 2)) {
    Die "PHP $PhpVer is too old — Laravel 12 needs >= 8.2 (we recommend 8.4)."
}
if ($PhpMaj -eq 8 -and $PhpMin -ge 5) {
    Warn "PHP $PhpVer detected. Three deps cap at 8.4 (nette/schema, nette/utils, openspout). Composer install may fail — install PHP 8.4 if you hit dep conflicts."
}
Ok "PHP $PhpVer, Composer $((composer --version --no-ansi 2>$null) -split ' ' | Select-Object -Index 2), Node $(node -v)"

# -----------------------------------------------------------------------------
# 2. .env / .env.example (create if missing — never overwrites)
# -----------------------------------------------------------------------------
Say "Setting up .env"

if (-not (Test-Path .env.example)) {
    @'
APP_NAME=RadarLeb
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"

DEV_PASSWORD=

ADMIN_MAX_EXECUTION_TIME=300

APP_TRUSTED_PROXIES=
'@ | Set-Content -Path .env.example -Encoding UTF8
    Ok "Created .env.example"
}

if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    Ok "Created .env from .env.example"
}

# -----------------------------------------------------------------------------
# 3. Composer + npm install
# -----------------------------------------------------------------------------
if (-not (Test-Path vendor)) {
    Say "composer install (may take a few minutes)"
    composer install --no-interaction --prefer-dist --no-scripts
    Ok "PHP dependencies installed"
} else {
    Ok "vendor/ already present (skipping composer install — re-run manually if you want a refresh)"
}

if (-not (Test-Path node_modules)) {
    Say "npm install"
    npm install
    Ok "JS dependencies installed"
} else {
    Ok "node_modules/ already present"
}

# -----------------------------------------------------------------------------
# 4. APP_KEY, migrate, storage:link
# -----------------------------------------------------------------------------
$envContent = Get-Content .env -Raw
if ($envContent -notmatch '(?m)^APP_KEY=base64:') {
    Say "Generating APP_KEY"
    php artisan key:generate --ansi
}

# Ensure SQLite db file exists (PHP's touch() works on Windows).
if (-not (Test-Path database)) { New-Item -ItemType Directory -Path database | Out-Null }
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File -Path database/database.sqlite | Out-Null }
Ok "SQLite db file ready"

Say "Running database migrations"
try {
    php artisan migrate --graceful --ansi
} catch {
    Warn "migrations had warnings (non-fatal)"
}

Say "Linking storage"
php artisan storage:link 2>$null

Say "Running composer post-install scripts"
try {
    composer run-script post-autoload-dump --no-interaction
} catch {
    Warn "post-autoload-dump had warnings"
}

# -----------------------------------------------------------------------------
# 5. Done
# -----------------------------------------------------------------------------
Write-Host ""
Write-Host "==============================================================" -ForegroundColor Green
Write-Host "  RadarLeb is ready. To start the dev server:" -ForegroundColor Green
Write-Host ""
Write-Host "    composer run dev" -ForegroundColor Cyan
Write-Host ""
Write-Host "  This launches all four processes in one terminal:" -ForegroundColor Green
Write-Host "    - php artisan serve     -> http://localhost:8000"
Write-Host "    - php artisan queue:listen"
Write-Host "    - php artisan pail (logs)"
Write-Host "    - npm run dev (Vite, hot-reload assets)"
Write-Host ""
Write-Host "  Or run pieces individually in separate terminals:"
Write-Host "    Terminal 1:  php artisan serve"
Write-Host "    Terminal 2:  npm run dev"
Write-Host ""
Write-Host "  Filament admin panel: http://localhost:8000/admin" -ForegroundColor Cyan
Write-Host "==============================================================" -ForegroundColor Green
