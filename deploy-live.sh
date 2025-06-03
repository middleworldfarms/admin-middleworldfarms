#!/bin/bash

# Laravel Live Deployment Script for Plesk
# This script configures the subdomain to serve Laravel from the public folder

echo "🚀 Making Laravel Admin Panel LIVE on Plesk..."
echo "=================================================="

# Current directory should be the Laravel root
LARAVEL_ROOT="/var/www/vhosts/middleworldfarms.org/subdomains/admin"
PUBLIC_DIR="$LARAVEL_ROOT/public"
HTTPDOCS_DIR="/var/www/vhosts/middleworldfarms.org/subdomains/admin/httpdocs"

echo "📁 Laravel root: $LARAVEL_ROOT"
echo "📁 Public folder: $PUBLIC_DIR"
echo "📁 Plesk httpdocs: $HTTPDOCS_DIR"

# Step 1: Check if httpdocs exists and backup if needed
if [ -d "$HTTPDOCS_DIR" ]; then
    echo "📦 Backing up existing httpdocs..."
    mv "$HTTPDOCS_DIR" "$HTTPDOCS_DIR.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Step 2: Create symlink from httpdocs to Laravel public folder
echo "🔗 Creating symlink from httpdocs to Laravel public folder..."
ln -sf "$PUBLIC_DIR" "$HTTPDOCS_DIR"

# Step 3: Set proper permissions
echo "🔐 Setting proper permissions..."
chown -R wonderful-kilby_axeszvh5cj9:psacln "$LARAVEL_ROOT"
chmod -R 755 "$LARAVEL_ROOT"
chmod -R 775 "$LARAVEL_ROOT/storage"
chmod -R 775 "$LARAVEL_ROOT/bootstrap/cache"

# Step 4: Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
cd "$LARAVEL_ROOT"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Step 5: Optimize Laravel for production
echo "⚡ Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅ DEPLOYMENT COMPLETE!"
echo "🌐 Your Laravel admin panel should now be live at:"
echo "   https://admin.middleworldfarms.org/"
echo "   https://admin.middleworldfarms.org/admin"
echo "   https://admin.middleworldfarms.org/admin/deliveries"
echo ""
echo "🔧 If you need to make changes, remember to run:"
echo "   php artisan config:clear && php artisan cache:clear"
echo ""
