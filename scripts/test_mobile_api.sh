#!/usr/bin/env bash
set -euo pipefail

# ============
# Configuration
# ============
BASE_URL="${BASE_URL:-http://127.0.0.1}"
COOKIE_JAR="${COOKIE_JAR:-cookies.txt}"
IDEMPOTENCY_KEY="${IDEMPOTENCY_KEY:-$(uuidgen 2>/dev/null || cat /proc/sys/kernel/random/uuid || echo RANDOM-$$-$(date +%s))}"

# Provided test credentials and data
LOGIN_PHONE="${LOGIN_PHONE:-237674226623}"
LOGIN_PASSWORD="${LOGIN_PASSWORD:-Ayuk.texa1}"
MSISDN="${MSISDN:-237693456789}"
BANK_CODE="${BANK_CODE:-999240}"
ACCOUNT_NUMBER="${ACCOUNT_NUMBER:-0119215210}"
AMOUNT_XAF="${AMOUNT_XAF:-3000}"

jq_bin="$(command -v jq || true)"
python_bin="$(command -v python3 || command -v python || true)"

pretty() {
  if [[ -n "$jq_bin" ]]; then
    jq -C . 2>/dev/null || cat
  elif [[ -n "$python_bin" ]]; then
    "$python_bin" -m json.tool 2>/dev/null || cat
  else
    cat
  fi
}

# No jq/python required: extract simple values with sed

curl_json() {
  local method="$1"; shift
  local url="$1"; shift
  local data="${1:-}"
  if [[ -n "$data" ]]; then
    curl --http1.1 --fail-with-body -sS -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X "$method" \
      -H "Accept: application/json" -H "Content-Type: application/json" \
      -H "Idempotency-Key: $IDEMPOTENCY_KEY" \
      --data "$data" "$url"
  else
    curl --http1.1 --fail-with-body -sS -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X "$method" \
      -H "Accept: application/json" \
      -H "Idempotency-Key: $IDEMPOTENCY_KEY" \
      "$url"
  fi
}

curl_form() {
  local method="$1"; shift
  local url="$1"; shift
  curl --http1.1 --fail-with-body -sS -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X "$method" \
    -H "Accept: application/json" -H "Content-Type: application/x-www-form-urlencoded" \
    --data "$*" "$url"
}

echo_section() { printf "\n====================\n%s\n====================\n" "$1"; }

# 0) Health
echo_section "0) Health and Feature flag"
curl_json GET "$BASE_URL/api/mobile/feature" | pretty || true
curl_json GET "$BASE_URL/api/mobile/health/pawapay" | pretty || true
curl_json GET "$BASE_URL/api/mobile/health/safehaven" | pretty || true
curl_json GET "$BASE_URL/api/mobile/health/safehaven/banks" | pretty || true
curl_json GET "$BASE_URL/api/mobile/health/oxr" | pretty || true

# 1) Auth - Login (form fields per Postman)
echo_section "1) Auth: Login"
curl_form POST "$BASE_URL/api/mobile/auth/login" \
  "phone=$LOGIN_PHONE&password=$LOGIN_PASSWORD&pin=1234" | pretty

# 2) Banks
echo_section "2) Banks"
curl_json GET "$BASE_URL/api/mobile/banks" | pretty || true
curl_json GET "$BASE_URL/api/mobile/banks/favorites" | pretty || true
curl_json POST "$BASE_URL/api/mobile/banks/suggest" '{"name":"New Sandbox Bank"}' | pretty || true

# 3) KYC
echo_section "3) KYC"
curl_json POST "$BASE_URL/api/mobile/kyc/smileid/start" '{}' | pretty || true
curl_json POST "$BASE_URL/api/mobile/kyc/smileid/web-token" '{}' | pretty || true
curl_json GET  "$BASE_URL/api/mobile/kyc/status" | pretty || true

# 4) Transfers: Name Enquiry -> Quote -> Confirm
echo_section "4) Transfers: Name Enquiry"
curl_json POST "$BASE_URL/api/mobile/transfers/name-enquiry" \
  "{\"bankCode\":\"$BANK_CODE\",\"accountNumber\":\"$ACCOUNT_NUMBER\"}" | pretty || true

echo_section "4) Transfers: Quote"
quote_resp="$(curl_json POST "$BASE_URL/api/mobile/transfers/quote" \
  "{\"amountXaf\":$AMOUNT_XAF,\"bankCode\":\"$BANK_CODE\",\"accountNumber\":\"$ACCOUNT_NUMBER\"}")"
echo "$quote_resp" | pretty
quote_id="$(echo "$quote_resp" | sed -n 's/.*"quote"[^{]*{[^}]*"id"[[:space:]]*:[[:space:]]*\([0-9][0-9]*\).*/\1/p' | head -n1)"
if [[ -z "$quote_id" ]]; then
  quote_id="$(echo "$quote_resp" | sed -n 's/.*"id"[[:space:]]*:[[:space:]]*\([0-9][0-9]*\).*/\1/p' | head -n1)"
fi
if [[ -z "$quote_id" ]]; then
  echo "ERROR: quote.id not returned" >&2
  exit 1
fi

echo_section "4) Transfers: Confirm"
confirm_resp="$(curl_json POST "$BASE_URL/api/mobile/transfers/confirm" \
  "{\"quoteId\":$quote_id,\"bankCode\":\"$BANK_CODE\",\"accountNumber\":\"$ACCOUNT_NUMBER\",\"msisdn\":\"$MSISDN\"}")"
echo "$confirm_resp" | pretty
transfer_id="$(echo "$confirm_resp" | sed -n 's/.*"transferId"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -n1)"
if [[ -z "$transfer_id" ]]; then
  transfer_id="$(echo "$confirm_resp" | sed -n 's/.*"transfer"[^{]*{[^}]*"id"[[:space:]]*:[[:space:]]*\([0-9][0-9]*\).*/\1/p' | head -n1)"
fi
if [[ -z "$transfer_id" ]]; then
  echo "ERROR: transfer id not returned after confirm" >&2
  exit 1
fi

echo_section "5) Timeline & Receipts"
curl_json GET "$BASE_URL/api/mobile/transfers/$transfer_id/timeline" | pretty
curl_json GET "$BASE_URL/api/mobile/transfers/$transfer_id/receipt-url" | pretty
curl_json GET "$BASE_URL/api/mobile/transfers/$transfer_id/receipt.pdf" | pretty || true
curl_json POST "$BASE_URL/api/mobile/transfers/$transfer_id/share-url" '{}' | pretty || true

# 6) Pay-in Status Poll
echo_section "6) Pay-in Status Poll"
attempts=6
for ((i=1; i<=attempts; i++)); do
  payin_resp="$(curl_json POST "$BASE_URL/api/mobile/transfers/$transfer_id/payin/status" '{}')"
  echo "$payin_resp" | pretty
  status="$(echo "$payin_resp" | sed -n 's/.*"status"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -n1)"
  if [[ "$status" == "success" || "$status" == "failed" ]]; then
    break
  fi
  sleep 5
done

# 7) Initiate Payout & Status
echo_section "7) Initiate Payout"
payout_resp="$(curl_json POST "$BASE_URL/api/mobile/transfers/$transfer_id/payout" '{}')"
echo "$payout_resp" | pretty

echo_section "7) Payout Status Poll"
for ((i=1; i<=attempts; i++)); do
  payout_status="$(curl_json POST "$BASE_URL/api/mobile/transfers/$transfer_id/payout/status" '{}' )"
  echo "$payout_status" | pretty
  pstat="$(echo "$payout_status" | sed -n 's/.*"status"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -n1)"
  if [[ "$pstat" == "success" || "$pstat" == "failed" ]]; then
    break
  fi
  sleep 5
done

# 8) Notifications
echo_section "8) Notifications"
curl_json GET "$BASE_URL/api/mobile/profile/notifications" | pretty || true
curl_json PUT "$BASE_URL/api/mobile/profile/notifications" \
'{
  "email": { "transfers": true, "promos": false },
  "sms":   { "alerts": true },
  "push":  { "alerts": true }
}' | pretty || true
curl_json GET "$BASE_URL/api/mobile/notifications" | pretty || true
curl_json GET "$BASE_URL/api/mobile/notifications/summary" | pretty || true
curl_json PUT "$BASE_URL/api/mobile/notifications/read-all" '{}' | pretty || true

# 9) Devices
echo_section "9) Devices"
curl_json POST "$BASE_URL/api/mobile/devices/register" \
'{"platform":"web","token":"TEST_TOKEN_ABC"}' | pretty || true
curl_json GET "$BASE_URL/api/mobile/devices" | pretty || true
curl_json POST "$BASE_URL/api/mobile/devices/test-push" '{}' | pretty || true
curl_json DELETE "$BASE_URL/api/mobile/devices/unregister" | pretty || true

# 10) Profile & Security
echo_section "10) Profile & Security"
curl_json GET "$BASE_URL/api/mobile/profile" | pretty || true
curl_form POST "$BASE_URL/api/mobile/profile/security/pin" "currentPin=&newPin=1234" | pretty || true
curl_form POST "$BASE_URL/api/mobile/profile/security/password" "currentPassword=$LOGIN_PASSWORD&newPassword=NewPassw0rd!" | pretty || true

# 11) Pricing
echo_section "11) Pricing"
curl_json GET "$BASE_URL/api/mobile/pricing/limits" | pretty || true
curl_json GET "$BASE_URL/api/mobile/pricing/rate-preview?amountXaf=$AMOUNT_XAF" | pretty || true

# 12) Support & Policies
echo_section "12) Support & Policies"
curl_json GET "$BASE_URL/api/mobile/policies" | pretty || true
curl_form POST "$BASE_URL/api/mobile/support/contact" "subject=App Issue&message=I need help with a transfer." | pretty || true
curl_json GET "$BASE_URL/api/mobile/support/tickets" | pretty || true

echo_section "DONE"
echo "Transfer tested: $transfer_id"
