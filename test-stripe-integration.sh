#!/bin/bash

echo "🧪 Testing Stripe Integration Setup"
echo "=================================="

# Test 1: Check if Stripe keys are configured
echo "1. Checking Stripe configuration..."
if grep -q "STRIPE_KEY=pk_" /opt/sites/admin.middleworldfarms.org/.env && grep -q "STRIPE_SECRET=sk_" /opt/sites/admin.middleworldfarms.org/.env; then
    echo "✅ Stripe keys found in .env file"
else
    echo "❌ Stripe keys not configured properly"
    echo "   Please update STRIPE_KEY and STRIPE_SECRET in .env"
    exit 1
fi

# Test 2: Test Stripe service with real API call
echo ""
echo "2. Testing Stripe API connection..."
cd /opt/sites/admin.middleworldfarms.org

php artisan tinker --execute="
echo 'Testing Stripe API connection...' . PHP_EOL;

try {
    \$stripeService = new \App\Services\StripeService();
    \$stats = \$stripeService->getPaymentStatistics(7);
    
    if (isset(\$stats['total_revenue'])) {
        echo '✅ Stripe API connection successful!' . PHP_EOL;
        echo 'Total revenue (7 days): £' . number_format(\$stats['total_revenue'], 2) . PHP_EOL;
        echo 'Total transactions: ' . \$stats['total_transactions'] . PHP_EOL;
    } else {
        echo '⚠️  API connected but no recent payments found' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo '❌ Stripe API Error: ' . \$e->getMessage() . PHP_EOL;
    if (strpos(\$e->getMessage(), 'No API key provided') !== false) {
        echo 'Please check your STRIPE_SECRET key in .env file' . PHP_EOL;
    }
}
"

echo ""
echo "3. Testing dashboard route..."
curl -s -o /dev/null -w "%{http_code}" "https://admin.middleworldfarms.org:8444/admin/stripe" | {
    read http_code
    if [ "$http_code" = "200" ]; then
        echo "✅ Stripe dashboard accessible at /admin/stripe"
    else
        echo "❌ Dashboard returned HTTP $http_code"
    fi
}

echo ""
echo "🎉 Integration test complete!"
echo "Visit: https://admin.middleworldfarms.org:8444/admin/stripe"
