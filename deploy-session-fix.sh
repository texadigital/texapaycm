#!/bin/bash

# TexaPay Session Fix Deployment Script
# This script fixes the Redis session issue on production

echo "🚀 Starting TexaPay session fix deployment..."

# Navigate to the project directory
cd /home/texa-pay/htdocs/pay.texa.ng

echo "📁 Current directory: $(pwd)"

# Pull latest changes from Git
echo "📥 Pulling latest changes from Git..."
git pull origin main

# Backup current .env file
echo "💾 Backing up current .env file..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update .env file to use database sessions instead of Redis
echo "🔧 Updating session driver to database..."
sed -i 's/SESSION_DRIVER=redis/SESSION_DRIVER=database/' .env

# Verify the change
echo "✅ Verifying session driver change..."
grep "SESSION_DRIVER" .env

# Clear all Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure sessions table exists
echo "🗄️ Checking sessions table..."
if ! php artisan migrate:status | grep -q "sessions"; then
    echo "📋 Creating sessions table..."
    php artisan session:table
    php artisan migrate
else
    echo "✅ Sessions table already exists"
fi

# Set proper permissions
echo "🔐 Setting proper permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 755 public/

# Restart web server
echo "🔄 Restarting web server..."
if systemctl is-active --quiet apache2; then
    sudo systemctl restart apache2
    echo "✅ Apache restarted"
elif systemctl is-active --quiet nginx; then
    sudo systemctl restart nginx
    echo "✅ Nginx restarted"
else
    echo "⚠️ Could not determine web server type"
fi

# Test the application
echo "🧪 Testing application..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://pay.texa.ng/)
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Site is responding (HTTP 200)"
elif [ "$HTTP_CODE" = "302" ]; then
    echo "✅ Site is responding (HTTP 302 - redirect)"
else
    echo "⚠️ Site returned HTTP $HTTP_CODE"
fi

echo "🎉 Deployment completed!"
echo "📝 Check the site at: https://pay.texa.ng/"
echo "🔍 If issues persist, check logs: tail -f storage/logs/laravel.log"
