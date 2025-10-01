#!/bin/bash

# Configuration
BASE_URL="https://malika-jubilant-seriously.ngrok-free.dev/api/mobile"
COOKIE_FILE="cookies.txt"
TEST_PHONE="237650000005"
TEST_PASSWORD="Test@1234"
TEST_PIN="1234"

# Clean up from previous runs
rm -f $COOKIE_FILE

# Function to make authenticated requests
auth_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    curl -s -X $method \
        "$BASE_URL$endpoint" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -H "Accept: application/json" \
        -b $COOKIE_FILE \
        -c $COOKIE_FILE \
        -d "$data"
}

# Test 1: Check feature flag
echo "=== Testing Feature Flag ==="
curl -s -X GET "$BASE_URL/feature" | jq
echo -e "\n"

# Test 2: Register a new user
echo "=== Testing User Registration ==="
auth_request "POST" "/auth/register" \
    "name=TestUser&phone=$TEST_PHONE&password=$TEST_PASSWORD&pin=$TEST_PIN" | jq
echo -e "\n"

# Test 3: Login with the new user
echo "=== Testing User Login ==="
auth_request "POST" "/auth/login" \
    "phone=$TEST_PHONE&password=$TEST_PASSWORD&pin=$TEST_PIN" | jq
echo -e "\n"

# Test 4: Get user profile
echo "=== Testing Profile Endpoint ==="
auth_request "GET" "/profile" | jq
echo -e "\n"

# Test 5: List banks
echo "=== Testing Banks List ==="
auth_request "GET" "/banks" | jq
echo -e "\n"

# Test 6: Get dashboard summary
echo "=== Testing Dashboard Summary ==="
auth_request "GET" "/dashboard" | jq
echo -e "\n"

# Test 7: Logout
echo "=== Testing Logout ==="
auth_request "POST" "/auth/logout" | jq
echo -e "\n"

# Clean up
rm -f $COOKIE_FILE
