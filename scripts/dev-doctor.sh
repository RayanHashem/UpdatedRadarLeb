#!/usr/bin/env bash
# RadarLeb — Local dev environment doctor
# Reports what's installed and what's missing. Does NOT install anything.
# Run from project root: bash scripts/dev-doctor.sh

set -uo pipefail

GREEN="\033[0;32m"
RED="\033[0;31m"
YELLOW="\033[0;33m"
BLUE="\033[0;34m"
DIM="\033[0;2m"
RESET="\033[0m"

ok()   { printf "${GREEN}  OK${RESET}  %s\n" "$1"; }
miss() { printf "${RED}MISS${RESET}  %s\n" "$1"; }
warn() { printf "${YELLOW}WARN${RESET}  %s\n" "$1"; }
hdr()  { printf "\n${BLUE}== %s ==${RESET}\n" "$1"; }

OK_COUNT=0
MISS_COUNT=0
WARN_COUNT=0

check_cmd() {
  local name="$1"
  local cmd="$2"
  if command -v "$cmd" >/dev/null 2>&1; then
    local ver
    ver=$($cmd --version 2>&1 | head -1 || echo "?")
    ok "$name  ${DIM}($ver)${RESET}"
    OK_COUNT=$((OK_COUNT+1))
    return 0
  else
    miss "$name  (not installed)"
    MISS_COUNT=$((MISS_COUNT+1))
    return 1
  fi
}

hdr "macOS / shell"
ok "macOS $(sw_vers -productVersion 2>/dev/null || echo '?')"
ok "shell: $SHELL"

hdr "Core toolchain"
check_cmd "Homebrew" brew || true
check_cmd "git"      git  || true

hdr "PHP + Composer (Laravel backend)"
if command -v php >/dev/null 2>&1; then
  PHP_VER=$(php -r 'echo PHP_VERSION;')
  PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
  PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')
  if [ "$PHP_MAJOR" -gt 8 ] || { [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -ge 2 ]; }; then
    ok "PHP $PHP_VER  ${DIM}(>= 8.2 required)${RESET}"
    OK_COUNT=$((OK_COUNT+1))
  else
    warn "PHP $PHP_VER is too old — Laravel 12 needs >= 8.2"
    WARN_COUNT=$((WARN_COUNT+1))
  fi

  # Required PHP extensions for Laravel
  for ext in mbstring openssl pdo pdo_sqlite tokenizer xml ctype json fileinfo bcmath curl; do
    if php -m 2>/dev/null | grep -qi "^$ext\$"; then
      ok "  ext: $ext"
    else
      miss "  ext: $ext"
      MISS_COUNT=$((MISS_COUNT+1))
    fi
  done
else
  miss "PHP not installed"
  MISS_COUNT=$((MISS_COUNT+1))
fi

check_cmd "Composer" composer || true

hdr "Node.js (frontend / Vite)"
if command -v node >/dev/null 2>&1; then
  NODE_VER=$(node --version | sed 's/^v//')
  NODE_MAJOR=${NODE_VER%%.*}
  if [ "$NODE_MAJOR" -ge 18 ]; then
    ok "Node $NODE_VER  ${DIM}(>= 18 required)${RESET}"
    OK_COUNT=$((OK_COUNT+1))
  else
    warn "Node $NODE_VER is too old — need >= 18"
    WARN_COUNT=$((WARN_COUNT+1))
  fi
else
  miss "Node not installed"
  MISS_COUNT=$((MISS_COUNT+1))
fi
check_cmd "npm" npm || true

hdr "Optional but helpful"
check_cmd "sqlite3"      sqlite3      || true
check_cmd "mkcert (TLS)" mkcert       || true

hdr "Project state"
if [ -d .git ]; then
  ok "git repo"
  BRANCH=$(git branch --show-current 2>/dev/null || echo '?')
  ok "current branch: $BRANCH"
  if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
    warn "working tree has uncommitted changes"
    WARN_COUNT=$((WARN_COUNT+1))
  else
    ok "working tree clean"
  fi
else
  miss "not a git repo"
fi

if [ -f .env ]; then ok ".env exists"; else miss ".env missing"; fi
if [ -f .env.example ]; then ok ".env.example exists"; else warn ".env.example missing (will recreate)"; fi
if [ -d vendor ]; then ok "vendor/ installed"; else miss "vendor/ missing — run composer install"; fi
if [ -d node_modules ]; then ok "node_modules/ installed"; else miss "node_modules/ missing — run npm install"; fi
if [ -f database/database.sqlite ]; then ok "SQLite db file exists"; else warn "database/database.sqlite missing"; fi

hdr "Summary"
printf "  ${GREEN}%d ok${RESET}   ${YELLOW}%d warn${RESET}   ${RED}%d missing${RESET}\n\n" "$OK_COUNT" "$WARN_COUNT" "$MISS_COUNT"

if [ "$MISS_COUNT" -gt 0 ]; then
  echo "Next: install what's missing, then run  bash scripts/dev-setup.sh"
  exit 1
else
  echo "All good. Run  bash scripts/dev-setup.sh  to bootstrap the app."
  exit 0
fi
