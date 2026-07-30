# 🚀 How to Run DIDX Laravel Locally

## ⚡ Quick Start (5 minutes)

### Prerequisites
- XAMPP with PHP 8.1+ and MySQL
- Composer installed
- Git (optional)

### Step 1: Automated Setup (Recommended)

**Option A: Windows Batch Script**
```bash
cd c:\xampp\htdocs\didx-laravel
setup.bat
```

**Option B: PowerShell**
```powershell
cd c:\xampp\htdocs\didx-laravel
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
.\setup.ps1
```

This script will:
- ✅ Install dependencies
- ✅ Generate app key
- ✅ Run migrations
- ✅ Clear caches

**Option C: Manual (Linux/Mac)**
```bash
cd c:\xampp\htdocs\didx-laravel
composer install
php artisan key:generate
php artisan migrate
```

### Step 2: Start Development Server

```bash
php artisan serve
```

You'll see:
```
INFO  Server running on [http://127.0.0.1:8000].

Press Ctrl+C to quit.
```

### Step 3: Open in Browser

```
http://localhost:8000/dashboard
```

✅ **Done! You're now running DIDX locally.**

---

## 🎯 What's Included

| Component | Access |
|-----------|--------|
| 📊 Dashboard | http://localhost:8000/dashboard |
| 📞 Live Calls | http://localhost:8000/live-calls |
| 🧪 Channel Tests | http://localhost:8000/channel-tests |
| 📋 Reports | http://localhost:8000/reports/cdr |
| ⚙️ Settings | http://localhost:8000/settings |
| 📡 API Status | http://localhost:8000/api/status |

---

## 🔧 Configuration

### Default Database

The project is configured to connect to:
- **Host**: 165.227.88.28
- **Database**: telecom_db
- **Username**: admin
- **Password**: 12343211

### Change Database (If Needed)

Edit `.env` file:

```env
DB_HOST=localhost          # or your remote host
DB_PORT=3306
DB_DATABASE=telecom_db
DB_USERNAME=root
DB_PASSWORD=personal123
```

Then run migrations:
```bash
php artisan migrate
```

---

## 📁 Project Structure

```
didx-laravel/
├── app/
│   ├── Http/Controllers/   ← Business logic
│   │   ├── DidRouteController.php
│   │   ├── ReportController.php
│   │   ├── LiveCallController.php
│   │   ├── ChannelTestController.php
│   │   ├── SettingsController.php
│   │   └── Api/StatusController.php
│   └── Models/             ← Database models
├── database/
│   └── migrations/         ← Table schemas
├── resources/views/        ← Blade templates
├── routes/
│   ├── web.php
│   └── api.php
├── .env                    ← Configuration
└── artisan                 ← Command line tool
```

---

## 🛠️ Common Commands

### Start/Stop Server
```bash
# Start server
php artisan serve

# Start on different port
php artisan serve --port=8001

# Stop server (Ctrl+C)
```

### Database
```bash
# Run migrations
php artisan migrate

# Undo all migrations
php artisan migrate:reset

# Refresh database
php artisan migrate:refresh

# Seed database with test data
php artisan db:seed
```

### Cache
```bash
# Clear all caches
php artisan cache:clear

# Clear views
php artisan view:clear

# Clear routes
php artisan route:clear
```

### Debug
```bash
# Interactive PHP shell
php artisan tinker

# View all routes
php artisan route:list

# Check environment
php artisan about
```

---

## 🔍 Testing Features

### Test Dashboard (DID Management)

1. Go to: http://localhost:8000/dashboard
2. Scroll to "Provision DID" form
3. Enter: `1234567890`
4. Click "Deploy to Switch"
5. ✅ DID appears in list

### Test Settings

1. Go to: http://localhost:8000/settings
2. Add admin user:
   - Username: `testadmin`
   - Password: `password123`
   - Role: `admin`
3. ✅ User appears in list

### Test API

1. Go to: http://localhost:8000/api/status
2. ✅ Returns JSON with call statuses

---

## ⚠️ Troubleshooting

### "Connection refused" on database

**Problem**: Can't connect to 192.241.212.5

**Solution 1: Use local MySQL**
```env
DB_HOST=localhost
```

**Solution 2: Use Docker**
```bash
docker-compose up
```

**Solution 3: Check if remote server is running**
```bash
ping 192.241.212.5
```

### "Port 8000 already in use"

**Problem**: Another app using port 8000

**Solution**:
```bash
php artisan serve --port=8001
# Then go to http://localhost:8001
```

### "Class not found" error

**Problem**: Dependencies not installed

**Solution**:
```bash
composer dump-autoload
```

### "SQLSTATE[HY000]" database errors

**Problem**: Database connection failed

**Solution**:
```bash
# Check .env database settings
# Then run migrations
php artisan migrate
```

### "Route not found" (404)

**Problem**: Routes not registered

**Solution**:
```bash
php artisan route:clear
```

### Views won't render

**Problem**: Cache issue

**Solution**:
```bash
php artisan view:clear
```

---

## 🔐 Database Access (Direct)

Connect to the database directly using MySQL CLI:

```bash
mysql -h 192.241.212.5 -u root -p
# Enter password: personal123
```

Or use MySQL GUI (MySQL Workbench, Sequel Pro, etc.):

```
Host: 192.241.212.5
Port: 3306
Username: root
Password: personal123
Database: telecom_db
```

View tables:
```sql
use telecom_db;
show tables;
select * from call_logs;
select * from admin_users;
```

---

## 📦 Using with XAMPP Apache

If you prefer Apache instead of dev server:

### 1. Configure VirtualHost

Edit `c:\xampp\apache\conf\extra\httpd-vhosts.conf`:

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

### 2. Update Hosts

Edit `c:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1    didx.local
```

### 3. Restart Apache

XAMPP Control Panel → Restart Apache

### 4. Access

```
http://didx.local/dashboard
```

---

## 🐳 Using with Docker

If you have Docker installed:

```bash
cd c:\xampp\htdocs\didx-laravel

# Start containers
docker-compose up

# Access at
http://localhost:8000
```

Stop containers:
```bash
docker-compose down
```

---

## 📚 Documentation

| File | Purpose |
|------|---------|
| `LOCAL_SETUP.md` | Detailed setup guide |
| `LARAVEL_MIGRATION.md` | Architecture & technical details |
| `SETUP_INSTRUCTIONS.md` | Deployment guide |
| `CONVERSION_SUMMARY.md` | Project overview |
| `VERIFICATION_CHECKLIST.md` | Pre-deployment checks |

---

## 💡 Tips & Tricks

### Enable Query Logging
```bash
php artisan tinker
DB::enableQueryLog();
// run queries...
dd(DB::getQueryLog());
```

### Generate Documentation
```bash
php artisan ide-helper:generate
```

### Optimize Performance
```bash
composer dump-autoload --optimize
php artisan config:cache
php artisan route:cache
```

### Use Laravel Telescope (Debugging)
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

Then access: `http://localhost:8000/telescope`

### Create Test Data
```bash
php artisan tinker
\App\Models\CallLog::factory(10)->create();
\App\Models\AdminUser::create([
    'username' => 'test',
    'password_hash' => password_hash('test123', PASSWORD_BCRYPT),
    'role' => 'admin'
]);
```

---

## 🚀 Next Steps

1. ✅ Run locally successfully
2. 📖 Explore the codebase
3. 🧪 Test all features
4. 💻 Start developing!
5. 📝 Check documentation files

---

## 🆘 Need Help?

### Check These First
1. Is XAMPP running? (Apache + MySQL)
2. Is Composer installed?
3. Is PHP 8.1+?
4. Check `.env` database config

### Check Logs
```bash
# View Laravel logs
tail -f storage/logs/laravel.log
```

### Run Diagnostics
```bash
php artisan about
php artisan route:list
php artisan config:show
```

### Still Stuck?
1. Check `LOCAL_SETUP.md` for detailed troubleshooting
2. Review `LARAVEL_MIGRATION.md` for architecture
3. Check Laravel docs: https://laravel.com/docs

---

## ✅ Verification

To verify everything is working:

```bash
# Test server starts
php artisan serve

# Test database connection
php artisan tinker
DB::connection()->getPdo();

# Test API
curl http://localhost:8000/api/status

# Test routes
php artisan route:list
```

All should work without errors ✅

---

## 🎓 Learning Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Eloquent ORM**: https://laravel.com/docs/eloquent
- **Blade Templates**: https://laravel.com/docs/blade
- **Routing**: https://laravel.com/docs/routing
- **Controllers**: https://laravel.com/docs/controllers

---

**Happy coding! 🎉**

Your DIDX Laravel application is ready to use locally.
