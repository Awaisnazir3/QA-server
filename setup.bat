@echo off
REM DIDX Laravel - Automated Setup Script for Windows

echo.
echo ============================================
echo    DIDX Laravel - Local Setup
echo ============================================
echo.

REM Check if composer is installed
where composer >nul 2>nul
if errorlevel 1 (
    echo ERROR: Composer is not installed or not in PATH
    echo Please install Composer from https://getcomposer.org/
    pause
    exit /b 1
)

REM Check if PHP is installed
where php >nul 2>nul
if errorlevel 1 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP 8.1+ from https://www.php.net/
    pause
    exit /b 1
)

echo Step 1: Installing Composer dependencies...
call composer install
if errorlevel 1 (
    echo ERROR: Failed to install dependencies
    pause
    exit /b 1
)
echo ✓ Dependencies installed

echo.
echo Step 2: Generating application key...
call php artisan key:generate
if errorlevel 1 (
    echo ERROR: Failed to generate key
    pause
    exit /b 1
)
echo ✓ Application key generated

echo.
echo Step 3: Running database migrations...
call php artisan migrate
if errorlevel 1 (
    echo WARNING: Database migration had issues
    echo Make sure your database is running at the configured host
    echo Current configuration in .env file
)
echo ✓ Migrations completed

echo.
echo Step 4: Clearing caches...
call php artisan cache:clear
call php artisan view:clear
call php artisan route:clear
echo ✓ Caches cleared

echo.
echo ============================================
echo    Setup Complete!
echo ============================================
echo.
echo Your DIDX Laravel application is ready!
echo.
echo To start the development server, run:
echo   php artisan serve
echo.
echo Then access the application at:
echo   http://localhost:8000/dashboard
echo.
echo Database Configuration (from .env):
echo   Host: 192.241.212.5
echo   Database: telecom_db
echo   Username: root
echo.
echo To change database settings, edit .env file
echo.
echo For more information, see LOCAL_SETUP.md
echo.
pause
