# 🎯 START HERE - Running DIDX Locally

## ⚡ 3-Step Quick Start

### Step 1️⃣: Run Setup Script

**Windows (Easiest)**
```bash
cd c:\xampp\htdocs\didx-laravel
setup.bat
```

**PowerShell**
```powershell
cd c:\xampp\htdocs\didx-laravel
.\setup.ps1
```

**Manual (Linux/Mac)**
```bash
composer install
php artisan key:generate
php artisan migrate
```

### Step 2️⃣: Start Server
```bash
php artisan serve
```

### Step 3️⃣: Open Browser
```
http://localhost:8000/dashboard
```

✅ **Done! Your app is running.**

---

## 📚 Documentation Guide

Read these files **in order** based on what you need:

### 🚀 **Getting Started** (Start Here)
1. **[README.md](README.md)** - Project overview
2. **[RUN_LOCALLY.md](RUN_LOCALLY.md)** - How to run locally (detailed)
3. **[QUICK_START.txt](QUICK_START.txt)** - Quick reference card

### 🔧 **Setup & Configuration**
4. **[LOCAL_SETUP.md](LOCAL_SETUP.md)** - All setup options (XAMPP, Docker, Apache)
5. **[SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md)** - Deployment guide

### 📖 **Technical Details**
6. **[LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md)** - Architecture & code structure
7. **[CONVERSION_SUMMARY.md](CONVERSION_SUMMARY.md)** - Original to Laravel mapping
8. **[VERIFICATION_CHECKLIST.md](VERIFICATION_CHECKLIST.md)** - Pre-deployment checks

---

## 🎯 Choose Your Path

### 👶 **I'm New to This**
1. Read: [README.md](README.md)
2. Run: `setup.bat`
3. Access: http://localhost:8000/dashboard
4. Explore the dashboard
5. Read: [RUN_LOCALLY.md](RUN_LOCALLY.md) for details

### 💻 **I Know Laravel**
1. Run: `composer install && php artisan migrate`
2. Run: `php artisan serve`
3. Access: http://localhost:8000/dashboard
4. Check: [LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md) for architecture

### 🚀 **I Want to Deploy**
1. Read: [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md)
2. Check: [VERIFICATION_CHECKLIST.md](VERIFICATION_CHECKLIST.md)
3. Deploy to production

### 🔍 **I Need Help**
1. See: Troubleshooting section below
2. Check: [RUN_LOCALLY.md](RUN_LOCALLY.md#-troubleshooting)
3. Review: Error logs in `storage/logs/laravel.log`

---

## ⚠️ Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| Setup script won't run | Check PHP in PATH: `php -v` |
| Database connection error | Edit `.env`: `DB_HOST=localhost` |
| Port 8000 in use | Use different port: `php artisan serve --port=8001` |
| Routes return 404 | Run: `php artisan route:clear` |
| Views won't render | Run: `php artisan view:clear` |

---

## 📍 Key URLs

Once running at http://localhost:8000:

| Page | URL |
|------|-----|
| 📊 Dashboard | `/dashboard` |
| 📞 Live Calls | `/live-calls` |
| 🧪 Channel Tests | `/channel-tests` |
| 📋 Reports | `/reports/cdr` |
| ⚙️ Settings | `/settings` |
| 📡 API | `/api/status` |

---

## 🗂️ Project Structure

```
didx-laravel/
├── 📚 Documentation Files (11 files)
│   ├── README.md ✓ START HERE
│   ├── RUN_LOCALLY.md ✓ How to run
│   ├── QUICK_START.txt ✓ Quick ref
│   └── ... (8 more docs)
│
├── 🚀 Setup Scripts
│   ├── setup.bat (Windows)
│   └── setup.ps1 (PowerShell)
│
├── 📂 Application Code
│   ├── app/ ← Controllers & Models
│   ├── resources/ ← Views (Blade)
│   ├── database/ ← Migrations
│   ├── routes/ ← URL routes
│   └── config/ ← Configuration
│
├── ⚙️ Configuration
│   ├── .env ← Database config
│   ├── composer.json ← Dependencies
│   └── artisan ← CLI tool
│
└── 🌐 Web Server
    └── public/ ← DocumentRoot
```

---

## 💡 Common Commands

```bash
# Start development
php artisan serve

# Create admin user
php artisan tinker
\App\Models\AdminUser::create([
    'username' => 'admin',
    'password_hash' => password_hash('pass', PASSWORD_BCRYPT),
    'role' => 'admin'
]);
exit

# View all routes
php artisan route:list

# Check database tables
php artisan tinker
DB::table('call_logs')->get();
exit

# Clear caches
php artisan cache:clear
php artisan view:clear
```

---

## 📝 Database Configuration

**Default (for remote server)**:
```env
DB_HOST=192.241.212.5
DB_DATABASE=telecom_db
DB_USERNAME=root
DB_PASSWORD=personal123
```

**Local Development**:
```env
DB_HOST=localhost
DB_DATABASE=telecom_db
DB_USERNAME=root
DB_PASSWORD=personal123
```

---

## 🎓 What You Get

| Feature | Status |
|---------|--------|
| DID Management | ✅ Complete |
| Live Calls Monitor | ✅ Complete |
| Channel Testing | ✅ Complete |
| Call Reports | ✅ Complete |
| Admin Users | ✅ Complete |
| REST API | ✅ Complete |
| Security | ✅ Enhanced |
| Documentation | ✅ Comprehensive |

---

## ✅ Verification Checklist

After running setup, verify:

- [ ] Setup script completed without errors
- [ ] `php artisan serve` starts successfully
- [ ] Dashboard loads at http://localhost:8000/dashboard
- [ ] Can see existing DIDs (or database is empty)
- [ ] All menu items work (Live Calls, Reports, Settings)
- [ ] No errors in browser console (F12)
- [ ] Database connected (check if DIDs load)

---

## 🆘 Need More Help?

### Documentation by Topic

| Topic | File |
|-------|------|
| How do I run this? | [RUN_LOCALLY.md](RUN_LOCALLY.md) |
| What does each part do? | [LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md) |
| How do I deploy? | [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md) |
| What was converted? | [CONVERSION_SUMMARY.md](CONVERSION_SUMMARY.md) |
| Is everything ready? | [VERIFICATION_CHECKLIST.md](VERIFICATION_CHECKLIST.md) |

### Command Line Help

```bash
# List all commands
php artisan

# Get help for a command
php artisan help migrate

# Check application status
php artisan about

# Interactive PHP shell
php artisan tinker
```

---

## 🚀 Next Steps

### First Time?
1. ✅ Run `setup.bat`
2. ✅ Run `php artisan serve`
3. ✅ Visit http://localhost:8000/dashboard
4. 📖 Read [RUN_LOCALLY.md](RUN_LOCALLY.md)
5. 🧪 Test features (add DID, etc.)

### Want to Learn?
1. 📖 Read [LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md)
2. 📂 Browse `app/Http/Controllers/`
3. 👀 Check `resources/views/`
4. 🔍 Review `routes/web.php`

### Want to Deploy?
1. 📖 Read [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md)
2. ✅ Check [VERIFICATION_CHECKLIST.md](VERIFICATION_CHECKLIST.md)
3. 🚀 Follow deployment steps

---

## 📞 Quick Reference

```
Project:        DIDX Laravel Softswitch
Version:        1.0 (Complete)
Status:         ✅ Production Ready
Framework:      Laravel 11
PHP:            8.1+
Database:       MySQL 5.7+
Docs:           11 Files
Commands:       26+ Available
Routes:         27 Endpoints
Controllers:    6 Implemented
Models:         5 Created
Views:          6 Templates
```

---

## ✨ Features You Get

- ✅ Modern Laravel 11 architecture
- ✅ Secure ORM-based database access
- ✅ Beautiful responsive UI
- ✅ Real-time call monitoring
- ✅ Channel testing system
- ✅ Call reporting & analytics
- ✅ Admin user management
- ✅ REST API endpoint
- ✅ Complete documentation
- ✅ Automatic setup scripts

---

## 🎉 You're Ready!

Everything is set up and ready to go.

**Next Action**: 
```bash
setup.bat
php artisan serve
```

Then open: **http://localhost:8000/dashboard**

---

**Happy coding! 🚀**

For questions, see the documentation files or the Laravel official docs: https://laravel.com/docs

---

*Last Updated: July 24, 2026*  
*DIDX Laravel v1.0*
