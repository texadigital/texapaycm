#!/bin/bash

# TexaPay Deployment Fix Script
# This script fixes the Redis session issue on production

echo "🚀 Starting TexaPay deployment fix..."

# Navigate to the project directory
cd /var/www/html/texapay || cd /var/www/texapay || cd /home/texapay/public_html

echo "📁 Current directory: $(pwd)"

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
php artisan migrate:status | grep sessions || {
    echo "📋 Creating sessions table..."
    php artisan session:table
    php artisan migrate
}

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
curl -s -o /dev/null -w "%{http_code}" https://pay.texa.ng/ && echo " - Site is responding" || echo " - Site may have issues"

echo "🎉 Deployment fix completed!"
echo "📝 Check the site at: https://pay.texa.ng/"
echo "🔍 If issues persist, check logs: tail -f storage/logs/laravel.log"
