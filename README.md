# 🎯 DIDX - Laravel Softswitch Console

> A modern Laravel application for managing VoIP DIDs, live calls, and channel testing with Asterisk integration.

## 📋 Overview

DIDX is a complete refactoring of a legacy PHP VoIP management system into a modern, secure Laravel 11 application. It provides a comprehensive dashboard for managing Direct Inward Dial (DID) numbers, monitoring live calls, executing channel tests, and generating call reports.

**Status**: ✅ **Production Ready**  
**Last Updated**: July 24, 2026

---

## 🚀 Quick Start

### 5-Minute Setup

```bash
cd c:\xampp\htdocs\didx-laravel

# Automated setup (Windows)
setup.bat

# OR Manual setup
composer install
php artisan key:generate
php artisan migrate

# Start server
php artisan serve
```

**Access**: http://localhost:8000/dashboard

For detailed instructions, see [RUN_LOCALLY.md](RUN_LOCALLY.md)

---

## 📁 What's Included

### Controllers (6)
- **DidRouteController** - DID management & dashboard
- **ReportController** - CDR reports & filtering
- **LiveCallController** - Active call monitoring
- **ChannelTestController** - Channel testing
- **SettingsController** - Admin user management
- **Api/StatusController** - JSON status endpoint

### Models (5)
- CallLog, ChannelTestLog, ChannelTestCdr, Cdr, AdminUser

### Views (6)
- Dashboard, Reports, Live Calls, Channel Tests, Settings

### Database (5 Migrations)
- call_logs, channel_test_logs, channel_test_cdrs, admin_users, cdr

### Routes (27)
- 26 web routes + 1 API endpoint

---

## 🌟 Key Features

### 📊 Dashboard
- View all provisioned DIDs
- Track DID status (pending, pass, fail, route)
- Provision new DIDs
- Real-time system statistics
- One-click hangup operations

### 📞 Live Calls Monitor
- Real-time active call display
- Channel information (name, context, extension)
- Individual & bulk hangup operations
- Call state tracking

### 🧪 Channel Testing
- Execute 1-100 concurrent calls
- Configurable test parameters
- Channel detection & counting
- Automatic cleanup
- Test history audit log

### 📋 Call Reports
- CDR search & filtering
- Date/hour/DID filtering
- Pagination support
- Call status tracking

### ⚙️ Admin Settings
- Admin user management
- Role-based access (admin, operator)
- Bcrypt password hashing
- User deletion & updates

### 📡 REST API
- `/api/status` - JSON status endpoint
- Real-time call data
- Frontend polling support

---

## 🔧 Technology Stack

| Component | Version |
|-----------|---------|
| Laravel | 11.x |
| PHP | 8.1+ |
| MySQL | 5.7+ |
| Blade | Templates |
| Eloquent | ORM |

---

## 📚 Documentation

| Document | Purpose |
|----------|---------|
| [RUN_LOCALLY.md](RUN_LOCALLY.md) | **Start here** - Local setup guide |
| [LOCAL_SETUP.md](LOCAL_SETUP.md) | Detailed setup with all options |
| [QUICK_START.txt](QUICK_START.txt) | Quick reference card |
| [LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md) | Technical architecture |
| [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md) | Deployment guide |
| [CONVERSION_SUMMARY.md](CONVERSION_SUMMARY.md) | Project overview |
| [VERIFICATION_CHECKLIST.md](VERIFICATION_CHECKLIST.md) | Pre-deployment checks |

---

## 📍 Application URLs

| Feature | URL |
|---------|-----|
| Dashboard | http://localhost:8000/dashboard |
| Live Calls | http://localhost:8000/live-calls |
| Channel Tests | http://localhost:8000/channel-tests |
| Reports | http://localhost:8000/reports/cdr |
| Settings | http://localhost:8000/settings |
| API Status | http://localhost:8000/api/status |

---

## 🗄️ Database Configuration

**Current Configuration** (.env):
```env
DB_HOST=192.241.212.5
DB_PORT=3306
DB_DATABASE=telecom_db
DB_USERNAME=root
DB_PASSWORD=personal123
```

**Tables Created**:
- call_logs - DID routes & status
- channel_test_logs - Test history
- channel_test_cdrs - Test call records
- admin_users - User accounts
- cdr - Call detail records

---

## 🛠️ Essential Commands

```bash
# Development
php artisan serve                    # Start dev server
php artisan serve --port=8001       # Use different port

# Database
php artisan migrate                  # Run migrations
php artisan migrate:reset            # Reset database
php artisan migrate:refresh          # Refresh database

# Cache/Debug
php artisan cache:clear              # Clear all caches
php artisan view:clear               # Clear views
php artisan route:list               # List all routes
php artisan tinker                   # Interactive shell

# Deployment
php artisan config:cache             # Cache config
php artisan route:cache              # Cache routes
php artisan optimize                 # Optimize app
```

---

## 🔐 Security Features

✅ **SQL Injection Prevention** - Eloquent ORM with parameterized queries  
✅ **CSRF Protection** - Automatic token validation  
✅ **XSS Protection** - Blade template auto-escaping  
✅ **Password Hashing** - Bcrypt algorithm  
✅ **Input Validation** - Server-side framework validation  
✅ **Prepared Statements** - Built-in query escaping  

---

## 🐛 Troubleshooting

### Connection Refused
```bash
# Edit .env to use local MySQL
DB_HOST=localhost
php artisan migrate
```

### Port Already in Use
```bash
php artisan serve --port=8001
```

### Class Not Found
```bash
composer dump-autoload
```

### Route Not Found
```bash
php artisan route:clear
```

### Views Won't Render
```bash
php artisan view:clear
```

See [RUN_LOCALLY.md](RUN_LOCALLY.md) for more solutions.

---

## 📦 Project Structure

```
didx-laravel/
├── app/
│   ├── Http/Controllers/      ← Business logic
│   ├── Models/                ← Database models
│   └── Providers/
├── database/
│   ├── migrations/            ← Schema
│   └── factories/
├── resources/
│   └── views/                 ← Blade templates
├── routes/
│   ├── web.php                ← Web routes
│   └── api.php                ← API routes
├── config/                    ← Configuration
├── storage/                   ← Logs, cache
├── public/index.php           ← Entry point
├── .env                       ← Configuration
└── composer.json              ← Dependencies
```

---

## 🚀 Deployment

### Development
```bash
php artisan serve
```

### Production
```bash
# Build environment
APP_ENV=production
APP_DEBUG=false

# Optimize
php artisan config:cache
php artisan route:cache
php artisan optimize

# Deploy to web server
# Point DocumentRoot to /public
```

See [SETUP_INSTRUCTIONS.md](SETUP_INSTRUCTIONS.md) for full deployment guide.

---

## 📊 Project Stats

```
Files Created:      29
Controllers:        6
Models:             5
Views:              6
Migrations:         5
Routes:             27
Lines of Code:      ~2,500
Documentation:      7 files
```

---

## 🔄 Conversion Details

This project was successfully converted from 6 standalone PHP files into a modern Laravel application:

| Original | → | New Component |
|----------|---|--------------|
| index.php | → | DidRouteController + dashboard.blade |
| reports.php | → | ReportController + cdr.blade |
| live_calls.php | → | LiveCallController + live.blade |
| channel_tests.php | → | ChannelTestController + channel-history.blade |
| settings.php | → | SettingsController + settings/index.blade |
| get_status.php | → | Api/StatusController |

See [LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md) for technical details.

---

## 🎯 Features from Original Implementation

✅ DID provisioning & management  
✅ Live call monitoring & hangup  
✅ Channel testing (1-100 calls)  
✅ CDR search & reporting  
✅ Admin user management  
✅ System statistics  
✅ JSON API endpoint  
✅ Real-time status updates  

---

## 💻 System Requirements

- **PHP**: 8.1 or higher
- **MySQL**: 5.7 or higher
- **Composer**: Latest version
- **Operating System**: Windows, Linux, or macOS
- **Memory**: 512MB minimum (1GB recommended)
- **Disk Space**: 500MB for application + dependencies

---

## 📞 Support

### Documentation
1. **[RUN_LOCALLY.md](RUN_LOCALLY.md)** - Start here for setup
2. **[LOCAL_SETUP.md](LOCAL_SETUP.md)** - Detailed instructions
3. **[LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md)** - Technical architecture
4. **[QUICK_START.txt](QUICK_START.txt)** - Quick reference

### Learning Resources
- [Laravel Documentation](https://laravel.com/docs)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Blade Templating](https://laravel.com/docs/blade)

### Getting Help
1. Check documentation files
2. Review error logs: `storage/logs/laravel.log`
3. Run diagnostics: `php artisan about`
4. Test database: `php artisan tinker`

---

## 📝 License

This project is part of the DIDX VoIP Management System.

---

## 🎓 Learning Path

1. **Setup**: Follow [RUN_LOCALLY.md](RUN_LOCALLY.md)
2. **Explore**: Browse the dashboard
3. **Understand**: Read [LARAVEL_MIGRATION.md](LARAVEL_MIGRATION.md)
4. **Code**: Review `app/Http/Controllers/`
5. **Extend**: Build new features

---

## 🏆 Key Achievements

✅ Converted legacy PHP to modern Laravel  
✅ Implemented proper MVC architecture  
✅ Enhanced security throughout  
✅ Added comprehensive documentation  
✅ Maintained all original functionality  
✅ Ready for production deployment  

---

## 🎉 Ready to Get Started?

1. **Quick Start**: 5 minutes with `setup.bat`
2. **Detailed Setup**: See [RUN_LOCALLY.md](RUN_LOCALLY.md)
3. **Full Documentation**: Check other markdown files

**Access the dashboard**: http://localhost:8000/dashboard

---

**Happy coding! 🚀**

---

*Project: DIDX Laravel Softswitch Console*  
*Version: 1.0 (Complete)*  
*Status: ✅ Production Ready*  
*Last Updated: July 24, 2026*
#   Q A - s e r v e r  
 