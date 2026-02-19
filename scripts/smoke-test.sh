#!/usr/bin/env bash
# Smoke test for RadarLeb after deploy. Run from project root or set BASE_URL.
# Usage: ./scripts/smoke-test.sh [BASE_URL]
# Example: BASE_URL=https://staging.radarleb.com ./scripts/smoke-test.sh

set -e
BASE_URL="${1:-${BASE_URL:-http://localhost:8000}}"
BASE_URL="${BASE_URL%/}"

echo "Smoke testing: $BASE_URL"
FAIL=0

# 1) Health
if curl -sf -o /dev/null "$BASE_URL/up"; then
  echo "  [OK] GET /up"
else
  echo "  [FAIL] GET /up"
  FAIL=1
fi

# 2) Admin login page (should 200, no 5xx)
CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/admin/login")
if [ "$CODE" = "200" ]; then
  echo "  [OK] GET /admin/login ($CODE)"
else
  echo "  [FAIL] GET /admin/login (got $CODE)"
  FAIL=1
fi

# 3) Server-side checks (run on deploy target)
if command -v php >/dev/null 2>&1 && [ -f artisan ]; then
  if php artisan radarleb:smoke 2>/dev/null; then
    echo "  [OK] radarleb:smoke (DB, storage, queue)"
  else
    echo "  [FAIL] php artisan radarleb:smoke"
    FAIL=1
  fi
else
  echo "  [SKIP] On server run: php artisan radarleb:smoke"
fi

if [ $FAIL -eq 0 ]; then
  echo "Smoke test passed."
else
  echo "Smoke test had failures."
  exit 1
fi
