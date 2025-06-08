#!/bin/bash

# Test User Switching Integration
echo "=== Testing User Switching Integration ==="
echo ""

API_KEY="Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h"
BASE_URL="https://middleworldfarms.org/wp-json/mwf/v1"

# Test 1: Get recent users
echo "1. Testing Recent Users..."
RECENT_RESPONSE=$(curl -s -H "X-WC-API-Key: $API_KEY" "$BASE_URL/users/recent?limit=1")
echo "$RECENT_RESPONSE" | jq '.success, .users[0].display_name' 2>/dev/null || echo "Response: $RECENT_RESPONSE"

# Extract first user ID for testing
USER_ID=$(echo "$RECENT_RESPONSE" | jq -r '.users[0].id' 2>/dev/null)
echo "Found user ID: $USER_ID"
echo ""

# Test 2: Search users
echo "2. Testing User Search..."
SEARCH_RESPONSE=$(curl -s -H "X-WC-API-Key: $API_KEY" "$BASE_URL/users/search?search=test&limit=1")
echo "$SEARCH_RESPONSE" | jq '.success' 2>/dev/null || echo "Response: $SEARCH_RESPONSE"
echo ""

# Test 3: Get user details
if [ "$USER_ID" != "null" ] && [ "$USER_ID" != "" ]; then
    echo "3. Testing Get User Details for ID $USER_ID..."
    USER_DETAILS=$(curl -s -H "X-WC-API-Key: $API_KEY" "$BASE_URL/users/$USER_ID")
    echo "$USER_DETAILS" | jq '.success, .user.display_name' 2>/dev/null || echo "Response: $USER_DETAILS"
    echo ""
    
    # Test 4: Create switch URL (this won't actually switch, just generate URL)
    echo "4. Testing Switch URL Generation..."
    SWITCH_RESPONSE=$(curl -s -X POST -H "X-WC-API-Key: $API_KEY" -H "Content-Type: application/json" \
        -d "{\"user_id\": $USER_ID, \"redirect_to\": \"/my-account/\", \"admin_context\": \"laravel_test\"}" \
        "$BASE_URL/users/switch")
    echo "$SWITCH_RESPONSE" | jq '.success, .switch_url != null' 2>/dev/null || echo "Response: $SWITCH_RESPONSE"
else
    echo "3-4. Skipping user-specific tests (no user ID found)"
fi

echo ""
echo "=== Integration Test Complete ==="
echo "All endpoints should return 'true' for successful operation"
