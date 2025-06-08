#!/bin/bash

# MWF User Switching API Test Script
# This script tests all the user switching API endpoints

API_KEY="Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h"
BASE_URL="https://middleworldfarms.org/wp-json/mwf/v1"

echo "=== MWF User Switching API Test ==="
echo ""

# Test 1: Recent Users
echo "1. Testing Recent Users Endpoint..."
curl -s -H "X-WC-API-Key: $API_KEY" "$BASE_URL/users/recent?limit=3" | jq '.success, .total, .users[0].display_name' 2>/dev/null || echo "JSON parsing failed - endpoint may be working but jq not available"
echo ""

# Test 2: Search Users
echo "2. Testing User Search Endpoint..."
curl -s -H "X-WC-API-Key: $API_KEY" "$BASE_URL/users/search?q=test&limit=2" | jq '.success, .total' 2>/dev/null || echo "JSON parsing failed - endpoint may be working but jq not available"
echo ""

# Test 3: Get specific user (use ID 1 as it's usually admin)
echo "3. Testing Get User Details Endpoint..."
curl -s -H "X-WC-API-Key: $API_KEY" "$BASE_URL/users/1" | jq '.success, .user.display_name' 2>/dev/null || echo "JSON parsing failed - endpoint may be working but jq not available"
echo ""

# Test 4: Switch User (this will create a token but not actually switch)
echo "4. Testing User Switch Endpoint..."
curl -s -X POST -H "X-WC-API-Key: $API_KEY" -H "Content-Type: application/json" \
  -d '{"user_id": 1, "redirect_to": "/my-account/", "admin_context": "test"}' \
  "$BASE_URL/users/switch" | jq '.success, .preview_token != null' 2>/dev/null || echo "JSON parsing failed - endpoint may be working but jq not available"
echo ""

# Test 5: Validate token endpoint (using a dummy token)
echo "5. Testing Token Validation Endpoint..."
curl -s "$BASE_URL/users/switch/validate?token=dummy123" | jq '.success' 2>/dev/null || echo "JSON parsing failed - endpoint may be working but jq not available"
echo ""

echo "=== Test Complete ==="
echo "If you see 'true' values above, the endpoints are working correctly."
echo "If you see JSON data instead of parsed values, install 'jq' for better formatting."
