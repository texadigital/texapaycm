#!/bin/bash

# Configuration
BASE_URL="https://malika-jubilant-seriously.ngrok-free.dev/api/mobile"
COOKIE_FILE=".session_cookie"
TEST_PHONE="237653456789"  # Using the provided payin number
TEST_PASSWORD="Test@1234"
TEST_PIN="1234"

# Recipient details
RECIPIENT_BANK_CODE="999240"  # SAFE HAVEN SANDBOX BANK
RECIPIENT_ACCOUNT_NUMBER="0119215210"
TRANSFER_AMOUNT=1000  # 1000 XAF

# Clean up from previous runs
rm -f $COOKIE_FILE

# Function to generate a UUID v4
generate_uuid() {
    local N B C='89ab'
    for (( N=0; N < 16; ++N ))
    do
        B=$(( $RANDOM%256 ))
        case $N in
            6)
                printf '4%x' $(( B%16 ))
                ;;
            8)
                printf '%c%x' ${C:$RANDOM%${#C}:1} $(( B%16 ))
                ;;
            3 | 5 | 7 | 9)
                printf '%02x-' $B
                ;;
            *)
                printf '%02x' $B
                ;;
        esac
    done
    echo
}

# Function to make authenticated requests
auth_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    # Generate a unique idempotency key for POST, PUT, PATCH, DELETE methods
    local idempotency_key=""
    if [[ "$method" == "POST" || "$method" == "PUT" || "$method" == "PATCH" || "$method" == "DELETE" ]]; then
        idempotency_key="$(generate_uuid)"
    fi
    
    # Build the curl command
    local cmd=(
        "curl" "-s" "-X" "$method"
        "$BASE_URL$endpoint"
        "-H" "Content-Type: application/json"
        "-H" "Accept: application/json"
        "-H" "X-Requested-With: XMLHttpRequest"
    )
    
    # Add idempotency key if this is a mutating request
    if [ -n "$idempotency_key" ]; then
        cmd+=("-H" "Idempotency-Key: $idempotency_key")
    fi
    
    # Add cookie if it exists
    if [ -f "$COOKIE_FILE" ]; then
        cmd+=("-b" "$COOKIE_FILE")
    fi
    
    # Save cookies
    cmd+=("-c" "$COOKIE_FILE")
    
    # Add data if provided
    if [ -n "$data" ]; then
        cmd+=("-d" "$data")
    fi
    
    # Execute the command
    "${cmd[@]}"
}

# Function to make authenticated GET requests
auth_get_request() {
    local endpoint=$1
    
    # Build the curl command
    local cmd=(
        "curl" "-s" "-X" "GET"
        "$BASE_URL$endpoint"
        "-H" "Accept: application/json"
        "-H" "X-Requested-With: XMLHttpRequest"
    )
    
    # Add cookie if it exists
    if [ -f "$COOKIE_FILE" ]; then
        cmd+=("-b" "$COOKIE_FILE")
    fi
    
    # Save cookies
    cmd+=("-c" "$COOKIE_FILE")
    
    # Execute the command
    "${cmd[@]}"
}

# Function to print section headers
print_section() {
    echo -e "\n\033[1;34m=== $1 ===\033[0m"
}

# 1. Check if user exists by attempting to log in
print_section "Attempting to log in"
LOGIN_DATA="{\"phone\":\"$TEST_PHONE\",\"password\":\"$TEST_PASSWORD\",\"pin\":\"$TEST_PIN\"}"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "X-Requested-With: XMLHttpRequest" \
    -c $COOKIE_FILE \
    -d "$LOGIN_DATA")
echo $LOGIN_RESPONSE | jq

# 2. If login fails with invalid credentials, register the user
if [ "$(echo $LOGIN_RESPONSE | jq -r '.code')" == "INVALID_CREDENTIALS" ]; then
    print_section "User not found. Registering new user"
    REGISTER_DATA="{\"name\":\"Test User\",\"phone\":\"$TEST_PHONE\",\"password\":\"$TEST_PASSWORD\",\"pin\":\"$TEST_PIN\"}"
    REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -H "X-Requested-With: XMLHttpRequest" \
        -c $COOKIE_FILE \
        -d "$REGISTER_DATA")
    echo $REGISTER_RESPONSE | jq
    
    # Check if registration was successful
    if [ "$(echo $REGISTER_RESPONSE | jq -r '.success')" != "true" ]; then
        echo "Registration failed. Please check the response above."
        exit 1
    fi
    
    # Login after successful registration
    print_section "Logging in after registration"
    LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -H "X-Requested-With: XMLHttpRequest" \
        -c $COOKIE_FILE \
        -d "$LOGIN_DATA")
    echo $LOGIN_RESPONSE | jq
fi

# Check if login was successful
if [ "$(echo $LOGIN_RESPONSE | jq -r '.success' 2>/dev/null)" != "true" ]; then
    echo "Login failed. Please check credentials."
    exit 1
fi

echo "Authentication successful. Session cookies saved to $COOKIE_FILE"

# 2. Get transfer quote
print_section "Getting transfer quote"
QUOTE_DATA=$(cat <<EOF
{
    "amountXaf": $TRANSFER_AMOUNT,
    "bankCode": "$RECIPIENT_BANK_CODE",
    "accountNumber": "$RECIPIENT_ACCOUNT_NUMBER",
    "narration": "Test transfer"
}
EOF
)
QUOTE_RESPONSE=$(auth_request "POST" "/transfers/quote" "$QUOTE_DATA")
echo $QUOTE_RESPONSE | jq

# Extract quote reference
QUOTE_REF=$(echo $QUOTE_RESPONSE | jq -r '.quote.ref')
if [ -z "$QUOTE_REF" ] || [ "$QUOTE_REF" = "null" ]; then
    echo "Failed to get quote reference. Response:"
    echo $QUOTE_RESPONSE | jq
    exit 1
fi

echo "Quote reference: $QUOTE_REF"

# 3. Confirm transfer
print_section "Confirming transfer"
CONFIRM_DATA=$(cat <<EOF
{
    "quoteId": $(echo $QUOTE_RESPONSE | jq '.quote.id'),
    "quoteRef": "$QUOTE_REF",
    "bankCode": "$RECIPIENT_BANK_CODE",
    "accountNumber": "$RECIPIENT_ACCOUNT_NUMBER",
    "msisdn": "$TEST_PHONE",
    "pin": "$TEST_PIN"
}
EOF
)
CONFIRM_RESPONSE=$(auth_request "POST" "/transfers/confirm" "$CONFIRM_DATA")
echo $CONFIRM_RESPONSE | jq

# 4. Get transfer status
TRANSFER_REF=$(echo $CONFIRM_RESPONSE | jq -r '.data.reference')
if [ -n "$TRANSFER_REF" ] && [ "$TRANSFER_REF" != "null" ]; then
    print_section "Transfer Status"
    sleep 2  # Give it a moment to process
    STATUS_RESPONSE=$(auth_get_request "/transfers/$TRANSFER_REF")
    echo $STATUS_RESPONSE | jq
    
    # Get transfer timeline
    print_section "Transfer Timeline"
    TIMELINE_RESPONSE=$(auth_get_request "/transfers/$TRANSFER_REF/timeline")
    echo $TIMELINE_RESPONSE | jq
fi

# 5. Get recent transfers
print_section "Recent Transfers"
TRANSFERS_RESPONSE=$(auth_get_request "/transfers?limit=5")
echo $TRANSFERS_RESPONSE | jq

# 6. Logout
print_section "Logging out"
LOGOUT_RESPONSE=$(auth_request "POST" "/auth/logout" "{}")
echo $LOGOUT_RESPONSE | jq

# Clean up
rm -f $COOKIE_FILE
