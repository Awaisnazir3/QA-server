# DIDX Laravel - Automated Setup Script for PowerShell

Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "   DIDX Laravel - Local Setup" -ForegroundColor Cyan
Write-Host "============================================`n" -ForegroundColor Cyan

# Check if Composer is installed
try {
    $composerVersion = composer --version 2>$null
    Write-Host "✓ Composer found: $composerVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ ERROR: Composer is not installed" -ForegroundColor Red
    Write-Host "  Please install from: https://getcomposer.org/" -ForegroundColor Yellow
    exit 1
}

# Check if PHP is installed
try {
    $phpVersion = php -v 2>$null | Select-Object -First 1
    Write-Host "✓ PHP found: $phpVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ ERROR: PHP is not installed" -ForegroundColor Red
    Write-Host "  Please install PHP 8.1+ from: https://www.php.net/" -ForegroundColor Yellow
    exit 1
}

# Step 1: Install dependencies
Write-Host "`nStep 1: Installing Composer dependencies..." -ForegroundColor Yellow
composer install
if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Failed to install dependencies" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Dependencies installed" -ForegroundColor Green

# Step 2: Generate application key
Write-Host "`nStep 2: Generating application key..." -ForegroundColor Yellow
php artisan key:generate
if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Failed to generate key" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Application key generated" -ForegroundColor Green

# Step 3: Run migrations
Write-Host "`nStep 3: Running database migrations..." -ForegroundColor Yellow
php artisan migrate
if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠ WARNING: Database migration had issues" -ForegroundColor Yellow
    Write-Host "  Make sure your database is running" -ForegroundColor Yellow
}
Write-Host "✓ Migrations completed" -ForegroundColor Green

# Step 4: Clear caches
Write-Host "`nStep 4: Clearing caches..." -ForegroundColor Yellow
php artisan cache:clear | Out-Null
php artisan view:clear | Out-Null
php artisan route:clear | Out-Null
Write-Host "✓ Caches cleared" -ForegroundColor Green

# Summary
Write-Host "`n============================================" -ForegroundColor Cyan
Write-Host "   Setup Complete!" -ForegroundColor Cyan
Write-Host "============================================`n" -ForegroundColor Cyan

Write-Host "Your DIDX Laravel application is ready!`n" -ForegroundColor Green

Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Run: php artisan serve" -ForegroundColor White
Write-Host "  2. Open: http://localhost:8000/dashboard" -ForegroundColor White
Write-Host "`n"

Write-Host "Database Configuration (from .env):" -ForegroundColor Yellow
Write-Host "  Host: 192.241.212.5" -ForegroundColor White
Write-Host "  Database: telecom_db" -ForegroundColor White
Write-Host "  Username: root" -ForegroundColor White
Write-Host "`n"

Write-Host "To change database settings, edit the .env file" -ForegroundColor Yellow
Write-Host "For more information, see LOCAL_SETUP.md" -ForegroundColor Yellow
Write-Host "`n"
