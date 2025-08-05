#!/bin/bash

# Test AI Crop Timing API endpoint
echo "Testing AI Crop Timing API..."

# Test for lettuce in spring
echo "Testing lettuce in spring:"
curl -X POST http://localhost:8000/admin/api/ai/crop-timing \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: test" \
  -d '{"crop_type": "lettuce", "season": "spring", "is_direct_sow": false}' \
  -s | jq .

echo ""

# Test for tomato in summer  
echo "Testing tomato in summer:"
curl -X POST http://localhost:8000/admin/api/ai/crop-timing \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: test" \
  -d '{"crop_type": "tomato", "season": "summer", "is_direct_sow": false}' \
  -s | jq .

echo ""

# Test for carrot (direct sow) in fall
echo "Testing carrot (direct sow) in fall:"
curl -X POST http://localhost:8000/admin/api/ai/crop-timing \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: test" \
  -d '{"crop_type": "carrot", "season": "fall", "is_direct_sow": true}' \
  -s | jq .
