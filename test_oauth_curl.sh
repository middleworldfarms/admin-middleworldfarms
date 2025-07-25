#!/bin/bash

CLIENT_ID="Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD"
CLIENT_SECRET="mwf2025AdminSecretKey789xyz"
USERNAME="martin@middleworldfarms.org"
PASSWORD="Mackie1974"

echo "Testing FarmOS OAuth with curl..."
echo "Client ID: $CLIENT_ID"
echo "Client Secret: $CLIENT_SECRET"
echo ""

curl -v -X POST https://farmos.middleworldfarms.org/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Accept: application/json" \
  -d "grant_type=password&username=${USERNAME}&password=${PASSWORD}&client_id=${CLIENT_ID}&client_secret=${CLIENT_SECRET}"

echo ""
echo "Done."
