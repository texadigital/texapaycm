#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   PUBLIC_HOST=<host> PHONE=<msisdn> PASSWORD=<password> [PIN=<1234>] ./scripts/smoke.sh
# Example:
#   PUBLIC_HOST=abcd-1234.ngrok-free.dev PHONE=674666623 PASSWORD=secret ./scripts/smoke.sh

: "${PUBLIC_HOST:?Set PUBLIC_HOST to your public domain (e.g., abcd-1234.ngrok-free.dev)}"
: "${PHONE:?Set PHONE to the user's phone number (e.g., 650123456 or +237650123456)}"
PASSWORD="${PASSWORD:-secret}"
PIN="${PIN:-}"

COOKIES_FILE="/tmp/texapay_cookies.txt"
rm -f "$COOKIES_FILE"

echo "[1/3] Getting CSRF cookie ..."
HTTP1=$(curl -sk -o /dev/null -w "%{http_code}" -c "$COOKIES_FILE" -b "$COOKIES_FILE" \
  "https://${PUBLIC_HOST}/sanctum/csrf-cookie")
echo "Status: ${HTTP1}" && test "$HTTP1" -ge 200 -a "$HTTP1" -lt 400

# Extract XSRF token from cookie jar (Netscape cookie format: name in column 6, value in column 7)
XSRF_RAW=$(awk '$6=="XSRF-TOKEN" {print $7}' "$COOKIES_FILE" | tail -n1)
if [[ -z "${XSRF_RAW}" ]]; then
  echo "Failed to read XSRF-TOKEN from cookies file" >&2
  exit 1
fi

# URL-decode token for header via stdin to avoid argv quirks
XSRF_DECODED=$(printf '%s' "$XSRF_RAW" | python3 -c 'import sys,urllib.parse; print(urllib.parse.unquote(sys.stdin.read().strip()))')

echo "[2/3] Logging in ..."
# Build JSON payload file: phone + password (+ optional pin)
LOGIN_JSON_FILE=$(mktemp /tmp/login_payload.XXXX.json)
trap 'rm -f "$LOGIN_JSON_FILE"' EXIT
if [[ -n "$PIN" ]]; then
  printf '{"phone":"%s","password":"%s","pin":"%s"}' "$PHONE" "$PASSWORD" "$PIN" > "$LOGIN_JSON_FILE"
else
  printf '{"phone":"%s","password":"%s"}' "$PHONE" "$PASSWORD" > "$LOGIN_JSON_FILE"
fi
HTTP2=$(curl -sk -o /dev/null -w "%{http_code}" -c "$COOKIES_FILE" -b "$COOKIES_FILE" \
  -H "X-XSRF-TOKEN: ${XSRF_DECODED}" \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -X POST \
  --data-binary @"$LOGIN_JSON_FILE" \
  "https://${PUBLIC_HOST}/api/mobile/auth/login")
echo "Status: ${HTTP2}"
if ! [ "$HTTP2" -ge 200 -a "$HTTP2" -lt 400 ]; then
  echo "Login failed with status: ${HTTP2}"
  exit 1
fi

echo "[3/3] Calling authenticated endpoint ..."
HTTP3=$(curl -sk -o /dev/null -w "%{http_code}" -c "$COOKIES_FILE" -b "$COOKIES_FILE" \
  "https://${PUBLIC_HOST}/api/mobile/dashboard")
echo "Status: ${HTTP3}" && test "$HTTP3" -ge 200 -a "$HTTP3" -lt 400

echo "Smoke test passed."
