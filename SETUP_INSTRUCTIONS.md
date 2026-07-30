# DIDX Laravel - Setup Instructions

## Quick Start Guide

### Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Asterisk (for VoIP functionality)

### Step 1: Verify Environment Configuration

Check that `.env` file has correct database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=165.227.88.28
DB_PORT=3306
DB_DATABASE=telecom_db
DB_USERNAME=admin
DB_PASSWORD=12343211
```

If you need to change credentials, edit the `.env` file accordingly.

### Step 2: Install PHP Dependencies

```bash
cd c:\xampp\htdocs\didx-laravel
composer install
```

### Step 3: Generate Application Key (if not already set)

```bash
php artisan key:generate
```

The key should already be set in .env, but you can regenerate if needed.

### Step 4: Run Database Migrations

This creates all necessary tables in your telecom_db database:

```bash
php artisan migrate
```

Tables created:
- `call_logs` - DID routes and status
- `channel_test_logs` - Channel test history
- `channel_test_cdrs` - Channel test call records
- `admin_users` - Admin user accounts
- `cdr` - Call detail records
- `migrations` - Migration tracking

### Step 5: Create Initial Admin User (Optional)

You can create an admin user through the web interface at `/settings`, or via command line:

```bash
php artisan tinker
```

Then in the Tinker shell:

```php
$user = new \App\Models\AdminUser();
$user->username = 'admin';
$user->password_hash = password_hash('your_password_here', PASSWORD_BCRYPT);
$user->role = 'admin';
$user->save();
exit
```

### Step 6: Start Laravel Development Server

```bash
php artisan serve
```

This starts the server at `http://localhost:8000`

### Step 7: Access the Application

Open your browser and navigate to:
- Dashboard: `http://localhost:8000/dashboard`
- Settings: `http://localhost:8000/settings`
- Reports: `http://localhost:8000/reports/cdr`
- Live Calls: `http://localhost:8000/live-calls`
- API Status: `http://localhost:8000/api/status`

## Using with XAMPP

If running on XAMPP, you may want to use Apache instead of `php artisan serve`:

1. Point Apache DocumentRoot to `c:\xampp\htdocs\didx-laravel\public`
2. Update your hosts file to include a local domain (optional)
3. Access via `http://localhost` or your configured domain

### Apache VirtualHost Example

In `c:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName didx.local
    DocumentRoot "c:\xampp\htdocs\didx-laravel\public"
    
    <Directory "c:\xampp\htdocs\didx-laravel\public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add to `c:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1    didx.local
```

Then access at `http://didx.local`

## Common Commands

### Clear Cache
```bash
php artisan cache:clear
```

### Clear Views
```bash
php artisan view:clear
```

### View Database Tables
```bash
php artisan tinker
\DB::statement('SHOW TABLES');
exit
```

### Reset Database (WARNING: Deletes all data)
```bash
php artisan migrate:reset
php artisan migrate
```

### Run Tests
```bash
php artisan test
```

## File Structure Overview

```
didx-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DidRouteController.php
│   │   │   ├── ReportController.php
│   │   │   ├── LiveCallController.php
│   │   │   ├── ChannelTestController.php
│   │   │   ├── SettingsController.php
│   │   │   └── Api/StatusController.php
│   ├── Models/
│   │   ├── CallLog.php
│   │   ├── ChannelTestLog.php
│   │   ├── ChannelTestCdr.php
│   │   ├── Cdr.php
│   │   └── AdminUser.php
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   └── views/
│       ├── layouts/
│       ├── dashboard.blade.php
│       ├── reports/
│       ├── tests/
│       ├── calls/
│       └── settings/
├── routes/
│   ├── web.php
│   └── api.php
├── config/
│   ├── database.php
│   └── app.php
├── .env
└── public/
    └── index.php
```

## Troubleshooting

### Issue: "SQLSTATE[HY000]: General error: 1030"
**Solution**: Check database connection in `.env` file, verify MySQL is running

### Issue: "Class not found" errors
**Solution**: Run `composer dump-autoload` then restart server

### Issue: Asterisk commands not working
**Solution**: Verify sudo privileges for web server user (www-data or similar)

### Issue: Views not rendering
**Solution**: Run `php artisan view:clear` and refresh browser

### Issue: 404 errors on routes
**Solution**: Verify routes in `routes/web.php`, check that controllers exist

## Accessing the Old Database

The original standalone PHP files connected to:
- **Host**: 192.241.212.5
- **Database**: telecom_db
- **Username**: root
- **Password**: personal123

The Laravel migration preserves this connection. If your database structure is different, you may need to update the migrations or models accordingly.

## Performance Optimization (Production)

For production deployment:

1. Set `APP_DEBUG=false` in `.env`
2. Set `APP_ENV=production` in `.env`
3. Run `php artisan config:cache`
4. Run `php artisan route:cache`
5. Ensure proper file permissions on `storage/` and `bootstrap/cache/`

## Support

For issues or questions, refer to:
- Laravel Documentation: https://laravel.com/docs
- This project: `LARAVEL_MIGRATION.md`
- Original code: See individual controller comments
