#!/bin/bash

# farmOS Laravel Integration Deployment Script
# This script will restore the complete farmOS integration

set -e

echo "🚀 farmOS Laravel Integration Deployment Script"
echo "================================================"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel project root directory"
    echo "Please run this script from /opt/sites/admin.middleworldfarms.org"
    exit 1
fi

echo "✅ Laravel project directory confirmed"

# Check .env file exists
if [ ! -f ".env" ]; then
    echo "❌ Error: .env file not found"
    echo "Please create .env file with farmOS credentials first"
    exit 1
fi

echo "✅ .env file found"

# Check for required .env variables
required_vars=("FARMOS_URL" "FARMOS_USERNAME" "FARMOS_PASSWORD" "FARMOS_OAUTH_CLIENT_ID" "FARMOS_OAUTH_CLIENT_SECRET")

for var in "${required_vars[@]}"; do
    if ! grep -q "^$var=" .env; then
        echo "❌ Error: Missing $var in .env file"
        echo "Please add all required farmOS configuration to .env"
        echo "See COMPLETE_RESTORATION_GUIDE.md for details"
        exit 1
    fi
done

echo "✅ All required .env variables found"

# Test farmOS OAuth2 connection
echo "🔍 Testing farmOS OAuth2 connection..."
if php test_oauth2_direct.php | grep -q "SUCCESS: OAuth2 token acquired"; then
    echo "✅ OAuth2 connection successful"
else
    echo "❌ Error: OAuth2 connection failed"
    echo "Please check farmOS Simple OAuth client configuration"
    echo "See COMPLETE_RESTORATION_GUIDE.md for setup instructions"
    exit 1
fi

# Test farmOS service integration
echo "🔍 Testing farmOS service integration..."
if php test_farmos_service_complete.php | grep -q "OAuth2 authentication is working"; then
    echo "✅ farmOS service integration successful"
else
    echo "❌ Error: farmOS service integration failed"
    echo "Please check service configuration and API permissions"
    exit 1
fi

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✅ Caches cleared"

# Run composer install to ensure dependencies
echo "📦 Installing/updating dependencies..."
composer install --no-dev --optimize-autoloader

echo "✅ Dependencies installed"

# Set proper permissions
echo "🔒 Setting file permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "✅ Permissions set"

echo ""
echo "🎉 DEPLOYMENT COMPLETE!"
echo "======================="
echo ""
echo "Your farmOS Laravel integration is now ready!"
echo ""
echo "🌐 Access your admin dashboard at: /admin/dashboard"
echo "📊 View planting chart at: /admin/farmos/planting-chart"
echo "🗺️  Map should display all 11 land assets from farmOS"
echo ""
echo "📚 For troubleshooting, see: COMPLETE_RESTORATION_GUIDE.md"
echo ""
echo "✅ All systems operational with live farmOS data!"
