#!/bin/bash
# FarmOS Connection Diagnostic Script

echo "=== FarmOS Connection Diagnostics ==="
echo "Date: $(date)"
echo "Target: https://farmos.middleworldfarms.org"
echo ""

echo "1. Testing HTTP Connection..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" -k "https://farmos.middleworldfarms.org/" 2>/dev/null)
echo "   Status Code: $HTTP_STATUS"

echo ""
echo "2. Testing Different Endpoints..."
echo "   /user/login: $(curl -s -o /dev/null -w "%{http_code}" -k "https://farmos.middleworldfarms.org/user/login" 2>/dev/null)"
echo "   /api: $(curl -s -o /dev/null -w "%{http_code}" -k "https://farmos.middleworldfarms.org/api" 2>/dev/null)"
echo "   /admin: $(curl -s -o /dev/null -w "%{http_code}" -k "https://farmos.middleworldfarms.org/admin" 2>/dev/null)"

echo ""
echo "3. Server Response Headers..."
curl -s -I -k "https://farmos.middleworldfarms.org/" 2>/dev/null | head -5

echo ""
echo "4. Testing HTTP (non-SSL)..."
HTTP_PLAIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://farmos.middleworldfarms.org/" 2>/dev/null)
echo "   HTTP Status: $HTTP_PLAIN_STATUS"

echo ""
echo "5. DNS Resolution..."
nslookup farmos.middleworldfarms.org 2>/dev/null | grep -A 2 "Name:"

echo ""
echo "=== Recommendations ==="
if [ "$HTTP_STATUS" = "403" ]; then
    echo "❌ 403 Forbidden - Access restrictions in place"
    echo "   → Check server .htaccess files"
    echo "   → Check IP whitelist settings"
    echo "   → Check farmOS admin settings"
elif [ "$HTTP_STATUS" = "200" ]; then
    echo "✅ Connection successful"
elif [ "$HTTP_STATUS" = "302" ] || [ "$HTTP_STATUS" = "301" ]; then
    echo "🔄 Redirect - farmOS may be redirecting to login"
else
    echo "❓ Unexpected status: $HTTP_STATUS"
fi

echo ""
echo "Next step: Set up farmOS credentials using the guide in setup-farmos-credentials.md"
