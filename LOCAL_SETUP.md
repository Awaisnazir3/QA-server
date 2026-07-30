# DIDX Laravel - Local Development Setup

## System Requirements

- **PHP**: 8.1 or higher
- **MySQL**: 5.7 or higher  
- **Composer**: Latest version
- **Node.js**: 14+ (optional, for frontend assets)
- **Git**: For version control

---

## Option 1: Using XAMPP (Easiest for Windows)

### Step 1: Verify XAMPP Installation

Ensure you have XAMPP installed with:
- Apache
- MySQL
- PHP 8.1+

Start XAMPP Control Panel and start **Apache** and **MySQL** services.

### Step 2: Navigate to Project

```bash
cd c:\xampp\htdocs\didx-laravel
```

### Step 3: Install Dependencies

```bash
composer install
```

This installs all PHP dependencies from `composer.json`.

### Step 4: Set Environment Variables

The `.env` file is already configured:

```env
APP_NAME=DIDX
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=165.227.88.28
DB_PORT=3306
DB_DATABASE=telecom_db
DB_USERNAME=admin
DB_PASSWORD=12343211
```

**⚠️ If your database server is different:**

Edit `.env` and change:
```env
DB_HOST=your_database_host
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 5: Generate Application Key

```bash
php artisan key:generate
```

(Usually already set, but regenerate if needed)

### Step 6: Create Database Tables (Migrations)

```bash
php artisan migrate
```

This creates all necessary tables:
- `call_logs` - DID routes
- `channel_test_logs` - Test history
- `channel_test_cdrs` - Call records
- `admin_users` - Admin accounts
- `cdr` - Call detail records

### Step 7: Start Development Server

```bash
php artisan serve
```

Server starts at: **http://localhost:8000**

### Step 8: Access the Application

Open your browser and go to:

```
http://localhost:8000/dashboard
```

✅ **You're done!** The application is now running locally.

---

## Option 2: Using Apache (XAMPP)

If you prefer using Apache instead of the dev server:

### Step 1: Configure Apache VirtualHost

Edit `c:\xampp\apache\conf\extra\httpd-vhosts.conf` and add:

```apache
<VirtualHost *:80>
    ServerName didx.local
    DocumentRoot "c:\xampp\htdocs\didx-laravel\public"
    
    <Directory "c:\xampp\htdocs\didx-laravel\public">
        AllowOverride All
        Require all granted
    </Directory>
    
    <Directory "c:\xampp\htdocs\didx-laravel">
        AllowOverride All
    </Directory>
</VirtualHost>
```

### Step 2: Update Hosts File

Edit `c:\Windows\System32\drivers\etc\hosts` and add:

```
127.0.0.1    didx.local
```

### Step 3: Restart Apache

In XAMPP Control Panel, click "Restart" on Apache.

### Step 4: Access via Browser

```
http://didx.local
```

---

## Option 3: Using Docker (Advanced)

If you have Docker installed:

### Create Dockerfile

Create `Dockerfile` in project root:

```dockerfile
FROM php:8.1-apache

RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get update && apt-get install -y curl
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN a2enmod rewrite
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]
```

### Create docker-compose.yml

```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "8000:80"
    environment:
      - DB_HOST=mysql
      - DB_DATABASE=telecom_db
      - DB_USERNAME=root
      - DB_PASSWORD=personal123
    volumes:
      - .:/var/www/html
    depends_on:
      - mysql

  mysql:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: personal123
      MYSQL_DATABASE: telecom_db
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

### Run with Docker

```bash
docker-compose up
```

Access at: `http://localhost:8000`

---

## Step-by-Step Command Reference

### 1. Initial Setup (First Time)

```bash
cd c:\xampp\htdocs\didx-laravel
composer install
php artisan key:generate
php artisan migrate
```

### 2. Start Development

```bash
php artisan serve
```

Then open: `http://localhost:8000/dashboard`

### 3. Stop Development Server

Press `Ctrl+C` in terminal

### 4. Database Management

**View all tables:**
```bash
php artisan tinker
DB::statement('SHOW TABLES');
exit
```

**Reset database (⚠️ deletes all data):**
```bash
php artisan migrate:reset
php artisan migrate
```

**Rollback one migration:**
```bash
php artisan migrate:rollback
```

### 5. Clear Cache

```bash
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

### 6. View Database Directly

Using MySQL CLI:
```bash
mysql -h 192.241.212.5 -u root -p
```

Enter password: `personal123`

Then query:
```sql
use telecom_db;
show tables;
select * from call_logs;
```

---

## Common Issues & Solutions

### Issue 1: "Connection refused" (Database not found)

**Problem**: Can't connect to database at 192.241.212.5

**Solution**:
```bash
# Edit .env
DB_HOST=localhost      # Use local MySQL if remote unavailable
DB_DATABASE=telecom_db
```

Or check if remote server is accessible:
```bash
ping 192.241.212.5
mysql -h 192.241.212.5 -u root -p
```

### Issue 2: "Port 8000 already in use"

**Problem**: Another application using port 8000

**Solution**:
```bash
# Use different port
php artisan serve --port=8001
```

Then access: `http://localhost:8001`

### Issue 3: "Class not found" errors

**Problem**: Composer dependencies not installed

**Solution**:
```bash
composer dump-autoload
php artisan serve
```

### Issue 4: "SQLSTATE[HY000]" database errors

**Problem**: Database migration issue

**Solution**:
```bash
# Clear and re-run migrations
php artisan migrate:reset
php artisan migrate
```

### Issue 5: Views not rendering properly

**Problem**: Blade template cache issue

**Solution**:
```bash
php artisan view:clear
```

### Issue 6: "Permission denied" on storage directory

**Problem**: File permissions issue

**Solution (Windows)**:
```bash
# Give full permissions to storage folder
icacls c:\xampp\htdocs\didx-laravel\storage /grant:r "*S-1-1-0:(OI)(CI)F" /T
```

### Issue 7: "Route not found" (404 errors)

**Problem**: Routes not registered

**Solution**:
```bash
php artisan route:clear
php artisan route:cache
```

---

## Testing the Installation

### 1. Check if running

```bash
# In browser
http://localhost:8000/dashboard
```

You should see the DID Routes dashboard.

### 2. Test Database Connection

```bash
php artisan tinker
DB::connection()->getPdo();
# Should return connection object, not error
exit
```

### 3. Test API Endpoint

```bash
# In browser
http://localhost:8000/api/status
```

Should return JSON with call statuses.

### 4. Test a Feature

1. Go to `/dashboard`
2. Scroll to "Provision DID" form
3. Enter test number: `1234567890`
4. Click "Deploy to Switch"
5. Should see new DID in list

---

## Development Workflow

### Daily Development

```bash
# 1. Start your day
cd c:\xampp\htdocs\didx-laravel
php artisan serve

# 2. Open browser to
http://localhost:8000

# 3. Make code changes
# The server will auto-reload

# 4. End your day
# Press Ctrl+C to stop server
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test tests/Feature/DashboardTest.php

# Run with coverage
php artisan test --coverage
```

### Database Debugging

```bash
# Interactive PHP shell
php artisan tinker

# Query examples in tinker
$logs = DB::table('call_logs')->get();
$users = \App\Models\AdminUser::all();
\App\Models\CallLog::find(1);
```

### Code Generation

```bash
# Create new model
php artisan make:model ModelName

# Create new controller
php artisan make:controller ControllerName

# Create new migration
php artisan make:migration migration_name

# Create new test
php artisan make:test FeatureTest
```

---

## Performance Tips

### 1. Enable Query Caching (Production)

Edit `.env`:
```env
CACHE_STORE=redis
```

### 2. Optimize Autoloader

```bash
composer dump-autoload --optimize
```

### 3. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
```

### 4. Monitor Performance

```bash
# Check slow queries
php artisan tinker
DB::enableQueryLog();
// ... run queries ...
dd(DB::getQueryLog());
```

---

## Useful Laravel Commands

```bash
# View all available commands
php artisan

# View all routes
php artisan route:list

# Optimize application
php artisan optimize

# Clear all caches
php artisan optimize:clear

# Check application health
php artisan down  # Maintenance mode
php artisan up    # Restore service

# Create symbolic link for storage
php artisan storage:link

# Generate documentation
php artisan ide-helper:generate
```

---

## Project Directory Structure

```
didx-laravel/
├── app/
│   ├── Http/Controllers/      ← Business logic
│   └── Models/                ← Database models
├── database/
│   └── migrations/            ← Table schemas
├── resources/views/           ← Blade templates
├── routes/
│   ├── web.php               ← Web routes
│   └── api.php               ← API routes
├── config/                    ← Configuration
├── storage/                   ← Logs, cache
├── public/                    ← Entry point
├── .env                       ← Environment config
├── artisan                    ← CLI tool
└── composer.json              ← Dependencies
```

---

## Next Steps

1. ✅ Install and run locally
2. 🔍 Explore the dashboard at `/dashboard`
3. 📊 Check out the code structure
4. 🧪 Test database connectivity
5. 🚀 Start developing!

---

## Need Help?

### Documentation Files
- `LARAVEL_MIGRATION.md` - Technical architecture
- `SETUP_INSTRUCTIONS.md` - Deployment guide
- `CONVERSION_SUMMARY.md` - Project overview

### Quick Reference
- Routes: `routes/web.php` and `routes/api.php`
- Controllers: `app/Http/Controllers/`
- Models: `app/Models/`
- Views: `resources/views/`

### External Resources
- [Laravel Documentation](https://laravel.com/docs)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Blade Templating](https://laravel.com/docs/blade)

---

**Happy coding! 🚀**

For questions, refer to the documentation files or the Laravel official docs.
