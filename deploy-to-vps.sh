#!/bin/bash

# TexaPay Deployment Script to VPS
# Target: pay.texa.ng at /home/texa-pay/htdocs/pay.texa.ng

set -e  # Exit on any error

echo "🚀 Starting TexaPay deployment to VPS..."
echo "Target: pay.texa.ng"
echo "Path: /home/texa-pay/htdocs/pay.texa.ng"
echo ""

# Configuration
VPS_HOST="195.35.1.5"
VPS_USER="texa-pay"
VPS_PATH="/home/texa-pay/htdocs/pay.texa.ng"
LOCAL_PATH="."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Not in Laravel project directory. Please run from project root."
    exit 1
fi

print_status "Preparing deployment..."

# Create a temporary directory for deployment
TEMP_DIR=$(mktemp -d)
print_status "Using temporary directory: $TEMP_DIR"

# Copy files to temp directory (excluding unnecessary files)
print_status "Copying files to temporary directory..."

rsync -av --progress \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='storage/framework/sessions' \
    --exclude='storage/framework/views' \
    --exclude='.env' \
    --exclude='.env.local' \
    --exclude='.env.production' \
    --exclude='.env.staging' \
    --exclude='deploy-to-vps.sh' \
    --exclude='scripts/find_duplicate_transactions.php' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    "$LOCAL_PATH/" "$TEMP_DIR/"

print_status "Files copied successfully"

# Create deployment package
DEPLOY_PACKAGE="texapay-deploy-$(date +%Y%m%d-%H%M%S).tar.gz"
print_status "Creating deployment package: $DEPLOY_PACKAGE"

cd "$TEMP_DIR"
tar -czf "../$DEPLOY_PACKAGE" .
cd - > /dev/null

print_status "Deployment package created: $DEPLOY_PACKAGE"

# Upload to VPS
print_status "Uploading to VPS..."

# Check if SSH key exists
if [ ! -f ~/.ssh/id_rsa ] && [ ! -f ~/.ssh/id_ed25519 ]; then
    print_warning "No SSH key found. You may need to enter password."
fi

# Upload the package
scp "$TEMP_DIR/../$DEPLOY_PACKAGE" "$VPS_USER@$VPS_HOST:/tmp/"

print_status "Package uploaded to VPS"

# Deploy on VPS
print_status "Deploying on VPS..."

ssh "$VPS_USER@$VPS_HOST" << EOF
    set -e
    
    echo "📦 Extracting deployment package..."
    cd $VPS_PATH
    
    # Create backup of current deployment
    if [ -d "current" ]; then
        echo "💾 Creating backup..."
        mv current "backup-\$(date +%Y%m%d-%H%M%S)" || true
    fi
    
    # Create new current directory
    mkdir -p current
    cd current
    
    # Extract new deployment
    tar -xzf "/tmp/$DEPLOY_PACKAGE"
    
    # Set proper permissions
    echo "🔐 Setting permissions..."
    chmod -R 755 .
    chmod -R 775 storage bootstrap/cache
    
    # Install/update dependencies
    echo "📦 Installing dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Clear caches
    echo "🧹 Clearing caches..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # Run migrations (if any)
    echo "🗄️ Running migrations..."
    php artisan migrate --force
    
    # Optimize for production
    echo "⚡ Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Update symlink to point to new deployment
    cd ..
    if [ -L "public" ]; then
        rm public
    fi
    ln -sf current/public public
    
    # Clean up
    rm "/tmp/$DEPLOY_PACKAGE"
    
    echo "✅ Deployment completed successfully!"
    echo "🌐 Your application is now live at: https://pay.texa.ng"
EOF

# Clean up local files
print_status "Cleaning up..."
rm -rf "$TEMP_DIR"
rm -f "$DEPLOY_PACKAGE"

print_status "🎉 Deployment completed successfully!"
print_status "🌐 Your application is now live at: https://pay.texa.ng"
print_status "🔧 Duplicate transaction fixes have been deployed"

echo ""
echo "Next steps:"
echo "1. Test the application at https://pay.texa.ng"
echo "2. Verify the duplicate transaction protection is working"
echo "3. Check the logs if needed: ssh $VPS_USER@$VPS_HOST 'tail -f $VPS_PATH/current/storage/logs/laravel.log'"
