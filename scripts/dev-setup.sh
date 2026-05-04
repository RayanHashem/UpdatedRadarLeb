#!/usr/bin/env bash
# RadarLeb — Local dev one-shot setup
# Idempotent. Run from project root: bash scripts/dev-setup.sh
#
# What it does, in order:
#   1. Verify required tools (PHP 8.2+, Composer, Node).
#   2. Sync working tree to origin/updatedradarleb (the cleanup branch's base).
#   3. Move misplaced Laravel config files from project root into config/.
#   4. Create .env (and .env.example) if missing, with sensible SQLite defaults.
#   5. composer install + npm install.
#   6. php artisan key:generate, migrate, storage:link.
#   7. Print how to start the dev server.
#
# Safe to re-run; each step is guarded.

set -euo pipefail

GREEN="\033[0;32m"; RED="\033[0;31m"; YELLOW="\033[0;33m"; BLUE="\033[0;34m"; DIM="\033[0;2m"; RESET="\033[0m"
say()  { printf "${BLUE}==>${RESET} %s\n" "$1"; }
ok()   { printf "${GREEN}✓${RESET}  %s\n" "$1"; }
warn() { printf "${YELLOW}!${RESET}  %s\n" "$1"; }
die()  { printf "${RED}✗${RESET}  %s\n" "$1"; exit 1; }

cd "$(dirname "$0")/.."
PROJECT_ROOT="$(pwd)"
say "Project root: $PROJECT_ROOT"

# -----------------------------------------------------------------------------
# 1. Tool check
# -----------------------------------------------------------------------------
say "Checking required tools"
command -v php      >/dev/null 2>&1 || die "PHP not installed. Run:  brew install php"
command -v composer >/dev/null 2>&1 || die "Composer not installed. Run:  brew install composer"
command -v node     >/dev/null 2>&1 || die "Node not installed. Run:  brew install node"
command -v npm      >/dev/null 2>&1 || die "npm not installed (comes with node)"

PHP_VER=$(php -r 'echo PHP_VERSION;')
PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')
if [ "$PHP_MAJOR" -lt 8 ] || { [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 2 ]; }; then
  die "PHP $PHP_VER is too old — Laravel 12 needs >= 8.2"
fi
ok "PHP $PHP_VER, Composer $(composer --version --no-ansi 2>/dev/null | awk '{print $3}'), Node $(node -v)"

# -----------------------------------------------------------------------------
# 2. Sync working tree to origin/updatedradarleb
# -----------------------------------------------------------------------------
BRANCH=$(git branch --show-current)
if [ "$BRANCH" != "cleanup/abed-radarleb" ]; then
  warn "You're on branch '$BRANCH', expected 'cleanup/abed-radarleb'. Continuing anyway."
fi

say "Fetching origin"
git fetch origin --prune

if [ -n "$(git status --porcelain)" ]; then
  warn "Working tree has uncommitted changes. Resetting hard to origin/updatedradarleb."
  git reset --hard origin/updatedradarleb
  ok "Working tree synced to $(git rev-parse --short HEAD)"
else
  ok "Working tree clean at $(git rev-parse --short HEAD)"
fi

# -----------------------------------------------------------------------------
# 3. Move misplaced Laravel config files: project root → config/
# -----------------------------------------------------------------------------
say "Restoring Laravel config/ directory"
mkdir -p config

# These files at root are Laravel config arrays that need to live in config/.
# auth.php at root is intentionally SKIPPED — it's actually misnamed route content.
CONFIG_FILES=(app cache database filesystems logging mail packages queue services session settings)

MOVED=0
for f in "${CONFIG_FILES[@]}"; do
  src="$f.php"
  dst="config/$f.php"
  if [ -f "$src" ] && [ ! -f "$dst" ]; then
    # Quick heuristic: real config files start with `<?php` and contain `return [`
    if head -10 "$src" | grep -q "return \[\|return array("; then
      git mv "$src" "$dst" 2>/dev/null || mv "$src" "$dst"
      ok "  moved $src → $dst"
      MOVED=$((MOVED+1))
    else
      warn "  $src does not look like a config array, skipping"
    fi
  elif [ -f "$dst" ]; then
    : # already in place
  fi
done
ok "$MOVED config file(s) moved into config/"

# -----------------------------------------------------------------------------
# 4. .env / .env.example
# -----------------------------------------------------------------------------
say "Setting up .env"
if [ ! -f .env.example ]; then
  cat > .env.example <<'ENVEOF'
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
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=radarleb
# DB_USERNAME=root
# DB_PASSWORD=

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

# Dev-only auto-auth helper (used by app/Http/Middleware/DevAutoAuth.php)
DEV_PASSWORD=

# Filament admin panel
ADMIN_MAX_EXECUTION_TIME=300

# Trusted proxies (comma-separated, or *). Leave blank for local dev.
APP_TRUSTED_PROXIES=
ENVEOF
  ok "Created .env.example"
fi

if [ ! -f .env ]; then
  cp .env.example .env
  ok "Created .env from .env.example"
fi

# -----------------------------------------------------------------------------
# 5. Composer + npm install
# -----------------------------------------------------------------------------
if [ ! -d vendor ]; then
  say "composer install (may take a few minutes)"
  # --no-scripts first because the post-autoload-dump tries to run artisan,
  # which would fail before key:generate. We'll run scripts manually after.
  composer install --no-interaction --prefer-dist --no-scripts
  ok "PHP dependencies installed"
else
  ok "vendor/ already present (skip composer install — re-run manually if you want a refresh)"
fi

if [ ! -d node_modules ]; then
  say "npm install"
  npm install
  ok "JS dependencies installed"
else
  ok "node_modules/ already present"
fi

# -----------------------------------------------------------------------------
# 6. APP_KEY, migrate, storage:link
# -----------------------------------------------------------------------------
if ! grep -qE '^APP_KEY=base64:' .env 2>/dev/null; then
  say "Generating APP_KEY"
  php artisan key:generate --ansi
fi

# Make sure SQLite file exists
mkdir -p database
[ -f database/database.sqlite ] || touch database/database.sqlite
ok "SQLite db file ready"

say "Running database migrations"
php artisan migrate --graceful --ansi || warn "migrations had warnings (non-fatal)"

say "Linking storage"
php artisan storage:link 2>/dev/null || true

# Now safe to run composer scripts (package:discover etc.)
say "Running composer post-install scripts"
composer run-script post-autoload-dump --no-interaction || warn "post-autoload-dump had warnings"

# -----------------------------------------------------------------------------
# 7. Done
# -----------------------------------------------------------------------------
cat <<'BANNER'

──────────────────────────────────────────────────────────
  RadarLeb is ready. To start the dev server:

    composer run dev

  This launches:
    • php artisan serve     → http://localhost:8000
    • php artisan queue:listen
    • php artisan pail (logs)
    • npm run dev (Vite, hot-reload assets)

  Or run pieces individually in separate terminals:
    Terminal 1:  php artisan serve
    Terminal 2:  npm run dev

  Filament admin panel: http://localhost:8000/admin
──────────────────────────────────────────────────────────
BANNER
