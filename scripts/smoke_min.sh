#!/usr/bin/env bash
set -euo pipefail

: "${PUBLIC_HOST:?Set PUBLIC_HOST (e.g., abcd-1234.ngrok-free.dev)}"
: "${PHONE:?Set PHONE (e.g., 650123456 or +237650123456)}"
: "${PASSWORD:?Set PASSWORD}"
PIN="${PIN:-}"

JAR="/tmp/texa_smoke_cookies.txt"
rm -f "$JAR"

# 1) CSRF cookie
code=$(curl -sk -o /dev/null -w "%{http_code}" -c "$JAR" -b "$JAR" "https://${PUBLIC_HOST}/sanctum/csrf-cookie")
echo "[1] csrf-cookie: $code"
if [ "$code" -lt 200 ] || [ "$code" -ge 400 ]; then exit 1; fi

# read XSRF token
raw=$(awk '$6=="XSRF-TOKEN" {print $7}' "$JAR" | tail -n1)
if [ -z "$raw" ]; then echo "no XSRF-TOKEN in cookies"; exit 1; fi
xsrf=$(printf '%s' "$raw" | python3 -c 'import sys,urllib.parse; print(urllib.parse.unquote(sys.stdin.read().strip()))')

# 2) Login
login_json="/tmp/texa_login.json"
if [ -n "$PIN" ]; then
  printf '{"phone":"%s","password":"%s","pin":"%s"}' "$PHONE" "$PASSWORD" "$PIN" > "$login_json"
else
  printf '{"phone":"%s","password":"%s"}' "$PHONE" "$PASSWORD" > "$login_json"
fi
code=$(curl -sk -o /dev/null -w "%{http_code}" -c "$JAR" -b "$JAR" \
  -H "X-XSRF-TOKEN: $xsrf" -H "Accept: application/json" -H "Content-Type: application/json" \
  -X POST --data-binary @"$login_json" "https://${PUBLIC_HOST}/api/mobile/auth/login")
echo "[2] login: $code"
if [ "$code" -lt 200 ] || [ "$code" -ge 400 ]; then exit 1; fi

# 3) Authenticated endpoint
code=$(curl -sk -o /dev/null -w "%{http_code}" -c "$JAR" -b "$JAR" "https://${PUBLIC_HOST}/api/mobile/dashboard")
echo "[3] dashboard: $code"
if [ "$code" -lt 200 ] || [ "$code" -ge 400 ]; then exit 1; fi

echo "Smoke OK"
