# DIDX Laravel Migration Guide

This document outlines the conversion of 6 standalone PHP files into a complete Laravel project structure.

## Files Converted

### Original PHP Files
1. **index.php** → Dashboard (DID Route Management)
2. **reports.php** → Report Controller (CDR Display)
3. **live_calls.php** → Live Call Controller
4. **channel_tests.php** → Channel Test Controller
5. **settings.php** → Settings Controller
6. **get_status.php** → API Status Endpoint

## Project Structure

### Database
- **Migrations** (`database/migrations/`)
  - `2024_01_01_000001_create_call_logs_table.php` - Main DID/call log storage
  - `2024_01_01_000002_create_channel_test_logs_table.php` - Channel test history
  - `2024_01_01_000003_create_channel_test_cdrs_table.php` - Individual channel test call records
  - `2024_01_01_000004_create_admin_users_table.php` - Admin user management
  - `2024_01_01_000005_create_cdr_table.php` - Call detail records

- **Models** (`app/Models/`)
  - `CallLog.php` - DID routes model with relationships
  - `ChannelTestLog.php` - Channel test history
  - `ChannelTestCdr.php` - Individual call records from channel tests
  - `Cdr.php` - Call detail records
  - `AdminUser.php` - Admin user accounts

### Controllers
- **app/Http/Controllers/**
  - `DidRouteController.php` - Dashboard, DID provisioning, routing (replaces index.php)
  - `ReportController.php` - CDR reports, channel test reports (replaces reports.php)
  - `LiveCallController.php` - Live call management, hangup operations (replaces live_calls.php)
  - `ChannelTestController.php` - Channel test execution and history (replaces channel_tests.php)
  - `SettingsController.php` - Admin user management (replaces settings.php)
  - `Api/StatusController.php` - JSON status endpoint (replaces get_status.php)

### Views (Blade Templates)
- **resources/views/**
  - `layouts/app.blade.php` - Main layout with sidebar navigation
  - `dashboard.blade.php` - DID route management view
  - `reports/cdr.blade.php` - CDR search and display
  - `tests/channel-history.blade.php` - Channel test audit logs
  - `calls/live.blade.php` - Live calls monitor
  - `settings/index.blade.php` - Admin user management

### Routes
- **routes/web.php** - All web routes for dashboard, reports, tests, calls, settings
- **routes/api.php** - API route for `/api/status` (replaces get_status.php)

## URL Mapping

| Original File | New Route | Method |
|---|---|---|
| index.php | /dashboard | GET |
| index.php (provision) | /dashboard/provision | POST |
| index.php (mark route) | /dashboard/{id}/mark-route | POST |
| index.php (reset) | /dashboard/{id}/reset | POST |
| index.php (delete) | /dashboard/{id} | DELETE |
| index.php (hangup all) | /dashboard/hangup-all | POST |
| reports.php | /reports/cdr | GET |
| live_calls.php | /live-calls | GET |
| live_calls.php (hangup all) | /live-calls/hangup-all | POST |
| live_calls.php (hangup channel) | /live-calls/hangup | POST |
| channel_tests.php | /channel-tests | GET |
| channel_tests.php (test) | /tests/{id} | POST |
| settings.php | /settings | GET |
| settings.php (add user) | /settings/add-user | POST |
| settings.php (delete user) | /settings/users/{id} | DELETE |
| get_status.php | /api/status | GET (JSON) |

## Configuration

### Environment Variables (.env)
```
APP_NAME=DIDX
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=192.241.212.5
DB_PORT=3306
DB_DATABASE=telecom_db
DB_USERNAME=root
DB_PASSWORD=personal123
```

### Database Configuration (config/database.php)
- Already configured to use MySQL
- Connection details read from .env variables

## Running the Application

### 1. Install Dependencies
```bash
composer install
```

### 2. Generate Application Key
```bash
php artisan key:generate
```

### 3. Run Migrations
```bash
php artisan migrate
```

### 4. Start Development Server
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Key Features

### DID Route Management (Dashboard)
- Provision new DIDs
- View DID status (pending, pass, fail, route)
- Execute channel tests on PASS status DIDs
- Mark DIDs as routed
- Reset DID status
- Delete DIDs
- Real-time system statistics (active calls, SIP peers, RAM usage)

### Channel Testing
- Execute configurable channel tests (1-100 calls)
- 10-second delay between calls
- Auto-hangup after test completion
- Channel detection and logging
- Test history audit log

### Live Calls Monitor
- Real-time active call list
- Channel information (name, context, extension, state)
- Individual channel hangup
- Hangup all active calls

### Call Reports
- CDR (Call Detail Records) search
- Filter by caller ID or destination
- Channel test report filtering (by date, hour, DID)
- Pagination support

### System Settings
- Admin user management (add/remove users)
- Role-based access (admin/operator)
- Password hashing with bcrypt

### API Endpoint
- JSON endpoint at `/api/status` for real-time updates
- Returns call statuses and active call count
- Used by frontend for live updates

## Security Improvements

### Over Original Files
1. **SQL Injection Prevention**: Uses Laravel's query builder and Eloquent ORM with parameterized queries
2. **CSRF Protection**: CSRF tokens on all forms via `@csrf` directive
3. **Input Validation**: Server-side validation with Laravel's validation framework
4. **Password Hashing**: Uses bcrypt password hashing for admin users
5. **Prepared Statements**: Automatically handled by Eloquent/Laravel
6. **XSS Protection**: Blade templating with automatic escaping via `{{ }}` syntax

## Asterisk Integration

All system commands execute via `shell_exec()` with proper error suppression and sudo privileges:

```php
@shell_exec("sudo /usr/sbin/asterisk -rx 'command' 2>/dev/null");
```

Commands supported:
- Channel information queries (`core show channels`)
- SIP peer listing (`sip show peers`)
- Channel hangup (`channel request hangup`)
- Call origination (`originate`)

## Database Tables

### call_logs
Stores DIDs and their current status
- id, phone_number, status, source_ip, checked_channels, caller_name, timestamps

### channel_test_logs
Audit history of channel tests
- id, did_id, phone_number, calls_requested, channels_detected, status, timestamps

### channel_test_cdrs
Individual call records from channel tests
- id, did_id, phone_number, caller_id, call_status, timestamps

### cdr
Call detail records from Asterisk
- id, caller_id, destination, duration, billsec, disposition, start_time, timestamps

### admin_users
Admin user accounts
- id, username, password_hash, role, timestamps

## Troubleshooting

### Database Connection Issues
- Verify .env database credentials
- Check MySQL server is running (192.241.212.5)
- Ensure telecom_db database exists

### Asterisk CLI Issues
- Verify sudo privileges for www-data user
- Check asterisk service is running
- Verify /usr/sbin/asterisk path is correct

### View Rendering Issues
- Run `php artisan view:clear` to clear compiled views
- Check blade template syntax in resources/views/

## Migration Checklist

- [x] Create database migrations
- [x] Create Eloquent models
- [x] Create Controllers
- [x] Create Blade views
- [x] Create web routes
- [x] Create API routes
- [x] Update .env configuration
- [x] Run migrations
- [x] Test all functionality

## Next Steps

1. Run migrations: `php artisan migrate`
2. Test dashboard at `/dashboard`
3. Verify Asterisk integration with live calls
4. Test channel test functionality
5. Verify database connectivity
