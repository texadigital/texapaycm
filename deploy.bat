@echo off
REM TexaPay Deployment Script for Windows
REM Target: pay.texa.ng VPS Server

echo ========================================
echo TexaPay Deployment to VPS
echo ========================================
echo.

echo Target Server: 195.35.1.5
echo Target Path: /home/texa-pay/htdocs/pay.texa.ng
echo.

echo Step 1: Creating deployment package...
echo.

REM Create a temporary directory
set TIMESTAMP=%date:~-4%%date:~3,2%%date:~0,2%-%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set DEPLOY_FILE=texapay-deploy-%TIMESTAMP%.zip

REM Use PowerShell to create zip (excluding unnecessary files)
powershell -Command "& { $excludePatterns = @('node_modules', '.git', 'vendor', 'storage\logs\*', 'storage\framework\cache\*', 'storage\framework\sessions\*', 'storage\framework\views\*', '.env*', '*.log'); Get-ChildItem -Path . -Recurse | Where-Object { $exclude = $false; foreach ($pattern in $excludePatterns) { if ($_.FullName -like \"*$pattern*\") { $exclude = $true; break } }; -not $exclude } | Compress-Archive -DestinationPath '%DEPLOY_FILE%' -Force }"

echo.
echo Deployment package created: %DEPLOY_FILE%
echo.

echo Step 2: Upload to VPS...
echo.
echo Please run this command in your terminal:
echo scp %DEPLOY_FILE% root@195.35.1.5:/tmp/
echo.

echo Step 3: Then SSH to VPS and run:
echo ssh root@195.35.1.5
echo cd /home/texa-pay/htdocs/pay.texa.ng
echo unzip -o /tmp/%DEPLOY_FILE%
echo composer install --no-dev --optimize-autoloader
echo php artisan migrate --force
echo php artisan config:clear
echo php artisan route:clear
echo php artisan cache:clear
echo php artisan config:cache
echo php artisan route:cache
echo.

pause
