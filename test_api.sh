#!/bin/bash

echo "Testing Succession Planning API..."

# Test the generate endpoint
echo "Testing generation endpoint..."
curl -X POST http://localhost:8000/admin/farmos/succession-planning/generate \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: test" \
  -d '{
    "crop": "Lettuce",
    "variety": "Buttercrunch",
    "successions": 4,
    "interval_days": 14,
    "start_date": "2025-08-15",
    "harvest_window": 60
  }' \
  -w "HTTP Status: %{http_code}\n" \
  -s

echo -e "\n\nDone!"
